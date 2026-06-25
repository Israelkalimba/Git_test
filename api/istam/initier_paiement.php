<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'etudiant') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

require_once '../../includes/config.php';
require_once '../../includes/Database.php';

$db = Database::getInstance();
$etudiant_id_user = $_SESSION['user_id'];

// Récupérer id_etudiant
$stmt = $db->prepare("SELECT id_etudiant, matricule FROM etudiants WHERE id_utilisateur = :id");
$stmt->execute(['id' => $etudiant_id_user]);
$etu = $stmt->fetch();
if (!$etu) {
    echo json_encode(['success' => false, 'message' => 'Étudiant introuvable']);
    exit();
}

$id_frais = (int)($_POST['id_frais'] ?? 0);
$devise = $_POST['devise'] ?? 'USD';
$telephone = trim($_POST['telephone'] ?? '');
$montant_usd = (float)($_POST['montant_usd'] ?? 0);
$taux_change = (float)($_POST['taux_change'] ?? 2300);

if ($id_frais <= 0 || $montant_usd <= 0 || empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

// Vérifier que le frais n'est pas déjà payé
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_frais = :f AND id_etudiant = :e AND statut = 'succes'");
$stmt->execute(['f' => $id_frais, 'e' => $etu['id_etudiant']]);
if ($stmt->fetch()['total'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Ce frais est déjà payé']);
    exit();
}

$montant_fc = $montant_usd * $taux_change;
$montant_api = ($devise === 'CDF') ? $montant_fc : $montant_usd;

if (($devise === 'CDF' && $montant_api < 1000) || ($devise === 'USD' && $montant_fc < 1000)) {
    echo json_encode([
        'success' => false,
        'message' => 'Montant insuffisant pour le paiement mobile. Le minimum autorisé est 1 000 FC.'
    ]);
    exit();
}

$reference = 'ISTAM-' . strtoupper(substr($etu['matricule'], -6)) . '-' . date('YmdHis') . '-' . rand(100, 999);

// Créer le paiement en BDD
$stmt = $db->prepare("INSERT INTO paiements (id_etudiant, id_frais, montant_paye, statut, reference_transaction) VALUES (:e,:f,:m,'en_attente',:r)");
$stmt->execute(['e' => $etu['id_etudiant'], 'f' => $id_frais, 'm' => $montant_usd, 'r' => $reference]);
$id_paiement = $db->lastInsertId();

// Appeler PayLedger
$api_key = PAYMENT_API_KEY;
$ch = curl_init('https://pay-ledger.b-manage.net/api/v1/gateway/initiate/mobile');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => (string)$montant_api,
        'currency' => $devise,
        'phone' => $telephone,
        'external_reference' => $reference,
        'description' => 'Frais académique ISTAM',
        'customer_name' => $_SESSION['user_nom'] ?? 'Étudiant'
    ]),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if (in_array($httpCode, [200, 201, 202], true) && $response !== false) {
    $data = json_decode($response, true);
    $gateway_ref = $data['reference'] ?? $data['gateway_reference'] ?? '';
    if (!empty($gateway_ref)) {
        // Enregistrer transaction
        $op = 'Mobile Money';
        $tc = preg_replace('/[^0-9]/', '', $telephone);
        if (strlen($tc) >= 9) {
            $p = substr($tc, -9, 2);
            if (in_array($p, ['81', '82', '83', '84', '85'])) $op = 'Orange Money';
            elseif (in_array($p, ['97', '98', '99'])) $op = 'Airtel Money';
            elseif (in_array($p, ['80', '90', '91'])) $op = 'Vodacom M-Pesa';
        }
        $stmt = $db->prepare("INSERT INTO transaction_mobile_money (id_paiement, numero_telephone, operateur, statut_api) VALUES (:pid,:tel,:op,'initiated')");
        $stmt->execute(['pid' => $id_paiement, 'tel' => $telephone, 'op' => $op]);

        // Stocker en session
        $_SESSION['istam_paiement'] = ['gateway_ref' => $gateway_ref, 'notre_ref' => $reference, 'id_paiement' => $id_paiement, 'montant' => $montant_usd];

        echo json_encode(['success' => true, 'gateway_ref' => $gateway_ref, 'notre_ref' => $reference, 'id_paiement' => $id_paiement]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Réponse API invalide : référence absente']);
    }
} else {
    $stmt = $db->prepare("UPDATE paiements SET statut = 'echec' WHERE id_paiement = :id");
    $stmt->execute(['id' => $id_paiement]);
    $err = json_decode($response, true);
    $message = $err['error'] ?? $err['message'] ?? '';
    if (empty($message) && !empty($curlError)) {
        $message = 'Impossible de contacter l\'API de paiement. Détail : ' . $curlError;
    } elseif (empty($message)) {
        $message = 'Erreur API (' . $httpCode . ')';
    }
    echo json_encode(['success' => false, 'message' => $message]);
}
