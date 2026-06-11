<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../includes/config.php';
require_once '../../includes/Database.php';

// Chargement de PHPMailer depuis vendor
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../../vendor/autoload.php';

// Configuration email (identiques à celles utilisées dans payer_frais.php)
$email_admin_sender = 'raphaeltshomba3@gmail.com';
$email_password = 'thgh mqyb njms enfx 
';
$nom_expediteur = 'ISTAM Paiement';

// Récupération des paramètres GET
$gateway_ref = trim($_GET['ref'] ?? '');
$force = isset($_GET['force']) && $_GET['force'] == '1';
$notre_ref = $_GET['notre_ref'] ?? '';
$id_paiement = (int)($_GET['id_paiement'] ?? 0);

if (empty($gateway_ref)) {
    echo json_encode(['status' => 'error', 'message' => 'Référence manquante']);
    exit();
}

// Appel à l'API PayLedger pour connaître le statut
$api_key = PAYMENT_API_KEY;
$ch = curl_init('https://pay-ledger.b-manage.net/api/v1/gateway/status/' . urlencode($gateway_ref));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_key, 'Accept: application/json'],
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $statut_api = $data['status'] ?? 'pending';
    
    if ($statut_api === 'successful') {
        $db = Database::getInstance();
        
        // Mise à jour du paiement et de la transaction mobile money
        if ($id_paiement > 0) {
            $stmt = $db->prepare("UPDATE paiements SET statut = 'succes' WHERE id_paiement = :id AND statut = 'en_attente'");
            $stmt->execute(['id' => $id_paiement]);
            $stmt = $db->prepare("UPDATE transaction_mobile_money SET statut_api = 'successful' WHERE id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
        }
        
        // Récupération de toutes les informations nécessaires pour les emails
        $stmt = $db->prepare("
            SELECT p.*, u.nom, u.email, u.id_utilisateur, f.type_frais, e.matricule, e.telephone
            FROM paiements p 
            JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
            JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
            JOIN frais f ON p.id_frais = f.id_frais 
            WHERE " . ($id_paiement > 0 ? "p.id_paiement = :id" : "p.reference_transaction = :ref")
        );
        if ($id_paiement > 0) {
            $stmt->execute(['id' => $id_paiement]);
        } else {
            $stmt->execute(['ref' => $notre_ref]);
        }
        $paiement = $stmt->fetch();
        
        if ($paiement) {
            // --- 1. Notification interne (base de données) ---
            // Étudiant
            $msg_etudiant = "✅ Paiement CONFIRMÉ : {$paiement['type_frais']} - \${$paiement['montant_paye']}. Reçu disponible dans votre espace.";
            $stmt = $db->prepare("INSERT INTO notifications (id_utilisateur, message, statut) VALUES (:uid, :msg, 'non_lu')");
            $stmt->execute(['uid' => $paiement['id_utilisateur'], 'msg' => $msg_etudiant]);
            
            // Administrateur et secrétaires
            $stmt_roles = $db->query("SELECT id_utilisateur FROM utilisateurs WHERE role IN ('admin', 'secretaire') AND statut_compte = 'actif'");
            $admins_secs = $stmt_roles->fetchAll();
            foreach ($admins_secs as $user) {
                $msg_admin = "💰 Paiement réussi : {$paiement['nom']} ({$paiement['matricule']}) - {$paiement['type_frais']} - \${$paiement['montant_paye']} via Mobile Money";
                $stmt = $db->prepare("INSERT INTO notifications (id_utilisateur, message, statut) VALUES (:uid, :msg, 'non_lu')");
                $stmt->execute(['uid' => $user['id_utilisateur'], 'msg' => $msg_admin]);
            }
            
            // --- 2. Envoi des emails avec PHPMailer ---
            
            // 2.1 Email à l'étudiant (confirmation personnalisée)
            if (!empty($paiement['email'])) {
                $sujet_etudiant = "✅ Félicitations ! Votre paiement à l'ISTAM est confirmé";
                $corps_etudiant = genererEmailEtudiant(
                    $paiement['nom'],
                    $paiement['type_frais'],
                    $paiement['montant_paye'],
                    $paiement['reference_transaction']
                );
                envoyerEmail($paiement['email'], $paiement['nom'], $sujet_etudiant, $corps_etudiant, $email_admin_sender, $email_password, $nom_expediteur);
            }
            
            // 2.2 Email à l'administrateur (très détaillé, personnel)
            $sujet_admin = "📢 ISTAM - Nouveau paiement reçu de {$paiement['nom']}";
            $corps_admin = genererEmailAdmin(
                $paiement['nom'],
                $paiement['matricule'],
                $paiement['type_frais'],
                $paiement['montant_paye'],
                $paiement['reference_transaction'],
                $paiement['telephone']
            );
            envoyerEmail($email_admin_sender, 'Administrateur ISTAM', $sujet_admin, $corps_admin, $email_admin_sender, $email_password, $nom_expediteur);
            
            // 2.3 Email à tous les gestionnaires (secrétaires)
            $stmt_sec = $db->query("SELECT email, nom FROM utilisateurs WHERE role = 'secretaire' AND statut_compte = 'actif' AND email IS NOT NULL AND email != ''");
            $secretaires = $stmt_sec->fetchAll();
            foreach ($secretaires as $sec) {
                $sujet_sec = "ISTAM - Paiement confirmé : {$paiement['nom']} ({$paiement['matricule']})";
                $corps_sec = genererEmailSecretaire(
                    $sec['nom'],
                    $paiement['nom'],
                    $paiement['matricule'],
                    $paiement['type_frais'],
                    $paiement['montant_paye'],
                    $paiement['reference_transaction']
                );
                envoyerEmail($sec['email'], $sec['nom'], $sujet_sec, $corps_sec, $email_admin_sender, $email_password, $nom_expediteur);
            }
        }
        
        echo json_encode([
            'status' => 'successful',
            'montant' => $paiement['montant_paye'] ?? 0,
            'notre_ref' => $notre_ref
        ]);
        
    } else {
        echo json_encode([
            'status' => $statut_api,
            'message' => 'Paiement en attente ou échoué'
        ]);
    }
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur API (HTTP ' . $httpCode . ')'
    ]);
}

/**
 * Envoie un email via PHPMailer (SMTP Gmail)
 */
function envoyerEmail($destinataire, $nom_destinataire, $sujet, $corps_html, $email_admin, $email_password, $nom_expediteur) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $email_admin;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($email_admin, $nom_expediteur);
        $mail->addAddress($destinataire, $nom_destinataire);
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body = $corps_html;
        $mail->AltBody = strip_tags($corps_html);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi email à {$destinataire}: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Email pour l'étudiant (félicitations, détails du paiement, pas de lien)
 */
function genererEmailEtudiant($nom, $type_frais, $montant_usd, $reference) {
    $montant_fc = $montant_usd * 2300; // taux par défaut
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 16px;'>
        <div style='background: #1e40af; padding: 20px; text-align: center; border-radius: 12px 12px 0 0;'>
            <h2 style='color: white; margin: 0;'>🏛️ Institut Supérieur ISTAM</h2>
            <p style='color: #93c5fd; margin: 5px 0 0;'>Confirmation de paiement</p>
        </div>
        <div style='padding: 25px;'>
            <h3 style='color: #1e293b;'>Bonjour {$nom},</h3>
            <p>Nous avons le plaisir de vous confirmer que votre paiement a été <strong style='color: #10b981;'>validé avec succès</strong>.</p>
            
            <div style='background: #f0fdf4; padding: 15px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #10b981;'>
                <p><strong>📌 Détail du règlement :</strong></p>
                <p>• Frais : <strong>{$type_frais}</strong></p>
                <p>• Montant payé : <strong style='color: #10b981;'>\$" . number_format($montant_usd, 2) . "</strong> (environ " . number_format($montant_fc, 0, ',', ' ') . " FC)</p>
                <p>• Référence transaction : <code>{$reference}</code></p>
                <p>• Date : " . date('d/m/Y à H:i') . "</p>
            </div>
            
            <p>Votre reçu officiel est accessible dans votre <strong>espace étudiant</strong> (menu « Mes Reçus »). Nous vous recommandons de vous connecter pour consulter l'ensemble de vos justificatifs.</p>
            
            <p style='margin-top: 25px;'>Merci de votre confiance et bonne continuation dans vos études !</p>
            <hr style='margin: 20px 0; border-color: #e2e8f0;'>
            <p style='color: #64748b; font-size: 0.8em;'>ISTAM – Service de la scolarité. Ce message est généré automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>";
}

/**
 * Email pour l'administrateur (très détaillé, personnel)
 */
function genererEmailAdmin($nom_etudiant, $matricule, $type_frais, $montant_usd, $reference, $telephone) {
    $montant_fc = $montant_usd * 2300;
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 16px;'>
        <div style='background: #0d7d73; padding: 20px; text-align: center; border-radius: 12px 12px 0 0;'>
            <h2 style='color: white; margin: 0;'>🏛️ ISTAM – Notification Paiement</h2>
            <p style='color: #a7f3d0; margin: 5px 0 0;'>Nouveau règlement enregistré</p>
        </div>
        <div style='padding: 25px;'>
            <h3 style='color: #1e293b;'>Bonjour Administrateur,</h3>
            <p>Un paiement vient d'être <strong style='color: #10b981;'>confirmé avec succès</strong> par un étudiant. Voici les détails complets :</p>
            
            <div style='background: #f8fafc; padding: 15px; border-radius: 12px; margin: 20px 0;'>
                <p><strong>👤 Étudiant :</strong> {$nom_etudiant}</p>
                <p><strong>🆔 Matricule :</strong> {$matricule}</p>
                <p><strong>📞 Téléphone :</strong> {$telephone}</p>
                <p><strong>📚 Frais réglé :</strong> {$type_frais}</p>
                <p><strong>💰 Montant (USD) :</strong> <span style='color: #10b981; font-weight: bold;'>\$" . number_format($montant_usd, 2) . "</span></p>
                <p><strong>💵 Équivalent FC :</strong> " . number_format($montant_fc, 0, ',', ' ') . " FC</p>
                <p><strong>🔖 Référence transaction :</strong> <code>{$reference}</code></p>
                <p><strong>📅 Date et heure :</strong> " . date('d/m/Y à H:i:s') . "</p>
            </div>
            
            <p>Pour toute vérification complémentaire, veuillez vous connecter au tableau de bord administrateur. Aucune action immédiate n'est requise, ce message est purement informatif.</p>
            <hr style='margin: 20px 0; border-color: #e2e8f0;'>
            <p style='color: #64748b; font-size: 0.8em;'>ISTAM – Système de paiement automatisé.</p>
        </div>
    </div>";
}

/**
 * Email pour les secrétaires / gestionnaires (détails opérationnels)
 */
function genererEmailSecretaire($nom_sec, $nom_etudiant, $matricule, $type_frais, $montant_usd, $reference) {
    $montant_fc = $montant_usd * 2300;
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 16px;'>
        <div style='background: #1e40af; padding: 20px; text-align: center; border-radius: 12px 12px 0 0;'>
            <h2 style='color: white; margin: 0;'>📋 ISTAM – Secrétariat</h2>
            <p style='color: #93c5fd; margin: 5px 0 0;'>Notification de paiement étudiant</p>
        </div>
        <div style='padding: 25px;'>
            <h3 style='color: #1e293b;'>Bonjour {$nom_sec},</h3>
            <p>Un paiement a été enregistré et validé automatiquement pour l'étudiant suivant :</p>
            
            <div style='background: #eff6ff; padding: 15px; border-radius: 12px; margin: 20px 0;'>
                <p><strong>Nom :</strong> {$nom_etudiant}</p>
                <p><strong>Matricule :</strong> {$matricule}</p>
                <p><strong>Frais :</strong> {$type_frais}</p>
                <p><strong>Montant réglé :</strong> <span style='color: #10b981;'>\$" . number_format($montant_usd, 2) . "</span> (≈ " . number_format($montant_fc, 0, ',', ' ') . " FC)</p>
                <p><strong>Référence :</strong> <code>{$reference}</code></p>
            </div>
            
            <p>Connectez-vous au portail de gestion pour consulter l'historique complet et générer les rapports si nécessaire.</p>
            <p style='margin-top: 20px;'>Ceci est un message automatique, aucune réponse n'est attendue.</p>
            <hr style='margin: 20px 0; border-color: #e2e8f0;'>
            <p style='color: #64748b; font-size: 0.8em;'>ISTAM – Service informatique.</p>
        </div>
    </div>";
}