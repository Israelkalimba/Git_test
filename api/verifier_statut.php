<?php
/**
 * API de vérification du statut d'un paiement auprès de PayLedger
 * Endpoint: GET /api/verifier_statut.php?reference=xxx&id_paiement=xxx
 */
require_once '../includes/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

// Vérifier les paramètres
if (!isset($_GET['reference']) || !isset($_GET['id_paiement'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

$reference = trim($_GET['reference']);
$id_paiement = (int)$_GET['id_paiement'];
$api_key = PAYMENT_API_KEY;
$api_base_url = 'https://pay-ledger.b-manage.net/api/v1/gateway';

// Appeler l'API PayLedger pour vérifier le statut
$ch = curl_init($api_base_url . '/status/' . urlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $api_key,
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// En cas d'erreur curl
if ($curlError) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion : ' . $curlError
    ]);
    exit();
}

// Réponse de PayLedger
if ($httpCode === 200) {
    $data = json_decode($response, true);
    $statut_api = $data['status'] ?? 'pending';
    
    // Si le statut est final, mettre à jour la BDD
    if (in_array($statut_api, ['successful', 'failed', 'cancelled', 'expired'])) {
        $db = Database::getInstance();
        
        $notre_statut = ($statut_api === 'successful') ? 'succes' : 'echec';
        
        try {
            $db->beginTransaction();
            
            // Mettre à jour le paiement
            $stmt = $db->prepare("UPDATE paiements SET statut = :statut WHERE id_paiement = :id AND statut = 'en_attente'");
            $stmt->execute(['statut' => $notre_statut, 'id' => $id_paiement]);
            $updated = $stmt->rowCount();
            
            // Mettre à jour la transaction mobile money
            $stmt = $db->prepare("UPDATE transaction_mobile_money SET statut_api = :statut_api WHERE id_paiement = :id");
            $stmt->execute(['statut_api' => $statut_api, 'id' => $id_paiement]);
            
            // Récupérer les infos pour la notification
            if ($updated > 0) {
                $stmt = $db->prepare("
                    SELECT p.*, u.nom, u.id_utilisateur, f.type_frais
                    FROM paiements p 
                    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
                    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
                    JOIN frais f ON p.id_frais = f.id_frais 
                    WHERE p.id_paiement = :id
                ");
                $stmt->execute(['id' => $id_paiement]);
                $paiement = $stmt->fetch();
                
                if ($paiement && $notre_statut === 'succes') {
                    // Notifier l'étudiant
                    $msg = "✅ Paiement CONFIRMÉ : {$paiement['type_frais']} - \${$paiement['montant_paye']}. Votre reçu est disponible.";
                    $stmt = $db->prepare("INSERT INTO notifications (id_utilisateur, message, statut) VALUES (:uid, :msg, 'non_lu')");
                    $stmt->execute(['uid' => $paiement['id_utilisateur'], 'msg' => $msg]);
                }
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'status' => $statut_api,
                'notre_statut' => $notre_statut,
                'mis_a_jour' => true,
                'message' => $notre_statut === 'succes' ? '✅ Paiement confirmé avec succès !' : '❌ Paiement échoué.'
            ]);
            
        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Erreur BDD : ' . $e->getMessage()
            ]);
        }
        
    } else {
        // Statut non final (pending, processing...)
        echo json_encode([
            'success' => true,
            'status' => $statut_api,
            'notre_statut' => 'en_attente',
            'mis_a_jour' => false,
            'message' => '⏳ Paiement en cours... Statut PayLedger : ' . ucfirst($statut_api)
        ]);
    }
    
} elseif ($httpCode === 404) {
    echo json_encode([
        'success' => false,
        'message' => 'Référence introuvable chez PayLedger.'
    ]);
} elseif ($httpCode === 401) {
    echo json_encode([
        'success' => false,
        'message' => 'Clé API invalide ou expirée.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur PayLedger (HTTP ' . $httpCode . ')'
    ]);
}