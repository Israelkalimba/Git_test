<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('secretaire');

$db = Database::getInstance();
$secretaire_nom = $_SESSION['user_nom'] ?? 'Secrétaire';
$secretaire_id = $_SESSION['user_id'] ?? 1;

// Clé API PayLedger
$api_key = PAYMENT_API_KEY;
$api_base_url = 'https://pay-ledger.b-manage.net/api/v1/gateway';

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// VÉRIFIER LE STATUT RÉEL AUPRÈS DE PAYLEDGER
if (isset($_GET['action']) && $_GET['action'] === 'verifier_statut' && isset($_GET['ref'])) {
    $reference = trim($_GET['ref']);

    // Appel à l'API PayLedger pour vérifier le statut réel
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
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $statut_api = $data['status'] ?? 'unknown';

        // Mettre à jour dans notre base
        if (in_array($statut_api, ['successful', 'failed', 'cancelled', 'expired'])) {
            $notre_statut = ($statut_api === 'successful') ? 'succes' : 'echec';

            $stmt = $db->prepare("UPDATE paiements SET statut = :statut WHERE reference_transaction = :ref AND statut = 'en_attente'");
            $stmt->execute(['statut' => $notre_statut, 'ref' => $reference]);

            // Mettre à jour aussi la table transaction_mobile_money
            $stmt = $db->prepare("UPDATE transaction_mobile_money tmm JOIN paiements p ON tmm.id_paiement = p.id_paiement SET tmm.statut_api = :statut_api WHERE p.reference_transaction = :ref");
            $stmt->execute(['statut_api' => $statut_api, 'ref' => $reference]);

            if ($stmt->rowCount() > 0) {
                $message = "✅ Statut mis à jour depuis PayLedger : <strong>" . ucfirst($statut_api) . "</strong>. Le paiement est maintenant marqué comme <strong>" . ($notre_statut === 'succes' ? 'Réussi' : 'Échec') . "</strong>.";
                $message_type = 'success';
            } else {
                $message = "ℹ️ Le statut PayLedger est <strong>" . ucfirst($statut_api) . "</strong> mais le paiement n'était pas en attente (déjà traité).";
                $message_type = 'info';
            }
        } else {
            $message = "⏳ Le statut PayLedger est <strong>" . ucfirst($statut_api) . "</strong>. Le paiement est toujours en cours de traitement. Réessayez dans quelques minutes.";
            $message_type = 'warning';
        }
    } elseif ($httpCode === 404) {
        $message = "❌ Référence introuvable chez PayLedger. Vérifiez la référence ou contactez le support.";
        $message_type = 'danger';
    } else {
        $message = "⚠️ Erreur de communication avec PayLedger (HTTP {$httpCode}). Vérifiez votre connexion internet.";
        $message_type = 'warning';
    }
}

// FORCER LA VALIDATION MANUELLE (avec justificatif)
if (isset($_GET['action']) && $_GET['action'] === 'forcer_succes' && isset($_GET['id'])) {
    $id_paiement = (int)$_GET['id'];
    $justificatif = trim($_GET['justificatif'] ?? '');

    if (empty($justificatif)) {
        $message = "❌ Veuillez fournir un justificatif (SMS, référence, motif).";
        $message_type = 'danger';
    } else {
        try {
            $db->beginTransaction();

            // Récupérer les infos
            $stmt = $db->prepare("SELECT p.*, u.nom, u.email, e.matricule FROM paiements p JOIN etudiants e ON p.id_etudiant = e.id_etudiant JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur WHERE p.id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
            $paiement = $stmt->fetch();

            if ($paiement) {
                // Mettre à jour le statut
                $stmt = $db->prepare("UPDATE paiements SET statut = 'succes' WHERE id_paiement = :id AND statut IN ('en_attente', 'echec')");
                $stmt->execute(['id' => $id_paiement]);

                // Mettre à jour la transaction mobile money
                $stmt = $db->prepare("UPDATE transaction_mobile_money SET statut_api = 'succes' WHERE id_paiement = :id");
                $stmt->execute(['id' => $id_paiement]);

                // Ajouter au journal d'audit
                $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'validation', 'validation_manuelle', :desc, :ip)");
                $stmt->execute([
                    'uid' => $secretaire_id,
                    'desc' => "Validation manuelle par {$secretaire_nom} - Paiement #{$id_paiement} ({$paiement['nom']} - {$paiement['matricule']} - \${$paiement['montant_paye']}) - Justificatif: {$justificatif}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);

                $db->commit();
                $message = "✅ Paiement #{$id_paiement} validé manuellement avec succès pour <strong>{$paiement['nom']}</strong>.";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "❌ Erreur : " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// REJETER UN PAIEMENT
if (isset($_GET['action']) && $_GET['action'] === 'rejeter' && isset($_GET['id'])) {
    $id_paiement = (int)$_GET['id'];
    $motif = trim($_GET['motif'] ?? '');

    if (empty($motif)) {
        $message = "❌ Veuillez fournir un motif de rejet.";
        $message_type = 'danger';
    } else {
        $stmt = $db->prepare("UPDATE paiements SET statut = 'echec' WHERE id_paiement = :id AND statut = 'en_attente'");
        $stmt->execute(['id' => $id_paiement]);

        $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'validation', 'rejet_manuel', :desc, :ip)");
        $stmt->execute([
            'uid' => $secretaire_id,
            'desc' => "Rejet manuel par {$secretaire_nom} - Paiement #{$id_paiement} - Motif: {$motif}",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        $message = "⚠️ Paiement #{$id_paiement} rejeté. Motif : {$motif}";
        $message_type = 'warning';
    }
}

// VÉRIFIER TOUTES LES TRANSACTIONS EN ATTENTE
if (isset($_GET['action']) && $_GET['action'] === 'verifier_tout') {
    $compteur_succes = 0;
    $compteur_echec = 0;

    $stmt = $db->query("SELECT p.id_paiement, p.reference_transaction FROM paiements p WHERE p.statut = 'en_attente' AND p.date_paiement < DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 20");
    $transactions_en_attente = $stmt->fetchAll();

    foreach ($transactions_en_attente as $tr) {
        // Vérifier chaque transaction auprès de PayLedger
        $ch = curl_init($api_base_url . '/status/' . urlencode($tr['reference_transaction']));
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
            $statut_api = $data['status'] ?? '';

            if ($statut_api === 'successful') {
                $stmt = $db->prepare("UPDATE paiements SET statut = 'succes' WHERE id_paiement = :id");
                $stmt->execute(['id' => $tr['id_paiement']]);
                $compteur_succes++;
            } elseif (in_array($statut_api, ['failed', 'cancelled', 'expired'])) {
                $stmt = $db->prepare("UPDATE paiements SET statut = 'echec' WHERE id_paiement = :id");
                $stmt->execute(['id' => $tr['id_paiement']]);
                $compteur_echec++;
            }
        }
    }

    $total = $compteur_succes + $compteur_echec;
    $message = "🔍 Vérification terminée : <strong>{$total}</strong> transaction(s) traitée(s). <br>✅ {$compteur_succes} succès | ❌ {$compteur_echec} échecs";
    $message_type = $total > 0 ? 'success' : 'info';
}

// ========== RÉCUPÉRATION DES PAIEMENTS EN ATTENTE ==========
$filtre_anciennete = $_GET['anciennete'] ?? 'tous';

$where_anciennete = '';
if ($filtre_anciennete === 'recent') {
    $where_anciennete = " AND p.date_paiement >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
} elseif ($filtre_anciennete === 'ancien') {
    $where_anciennete = " AND p.date_paiement < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
}

$stmt = $db->query("
    SELECT p.*, u.nom as nom_etudiant, u.email, e.matricule, e.telephone,
           fi.nom_filiere, pr.nom_promotion, f.type_frais,
           tmm.operateur, tmm.numero_telephone, tmm.statut_api
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN promotions pr ON e.id_promotion = pr.id_promotion 
    JOIN frais f ON p.id_frais = f.id_frais 
    LEFT JOIN transaction_mobile_money tmm ON p.id_paiement = tmm.id_paiement
    WHERE p.statut = 'en_attente' {$where_anciennete}
    ORDER BY p.date_paiement DESC 
    LIMIT 50
");
$paiements_attente = $stmt->fetchAll();

// Stats
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente'");
$total_attente = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente' AND date_paiement < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
$attente_anciens = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente' AND date_paiement >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
$attente_recents = $stmt->fetch()['total'] ?? 0;

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt->execute(['id' => $secretaire_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['id' => $secretaire_id]);
$navbar_notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation des Paiements - Secrétaire ISTAM</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/secretaire/dashboard_secretaire.css">
    <link rel="stylesheet" href="../assets/css/secretaire/validation_paiements.css">
</head>

<body>
    <div class="secretaire-layout">
        <?php include 'includes/sidebar_secretaire.php'; ?>
        <div class="main-content">
            <?php
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_secretaire.php';
            ?>
            <main class="dashboard-content">

                <!-- En-tête -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-check-double"></i> Validation des Paiements
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i>
                                Panneau de contrôle manuel pour les transactions bloquées.
                                <span class="text-warning fw-bold"><?= $total_attente ?> en attente</span>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <a href="?action=verifier_tout" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-sync-alt"></i> Vérifier tout avec PayLedger
                            </a>
                            <a href="suivi_paiements.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-list"></i> Tous les paiements
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                        <div><?= $message ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="validation-stats">
                    <div class="val-stat-card stat-total">
                        <div class="val-stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="val-stat-info">
                            <h4><?= $total_attente ?></h4>
                            <p>Total en attente</p>
                        </div>
                    </div>
                    <div class="val-stat-card stat-recent">
                        <div class="val-stat-icon"><i class="fas fa-hourglass-start"></i></div>
                        <div class="val-stat-info">
                            <h4><?= $attente_recents ?></h4>
                            <p>Récents (< 30 min)</p>
                        </div>
                    </div>
                    <div class="val-stat-card stat-ancien">
                        <div class="val-stat-icon"><i class="fas fa-hourglass-end"></i></div>
                        <div class="val-stat-info">
                            <h4><?= $attente_anciens ?></h4>
                            <p>Anciens (> 30 min)</p>
                        </div>
                    </div>
                    <div class="val-stat-card stat-action">
                        <div class="val-stat-icon"><i class="fas fa-hand-pointer"></i></div>
                        <div class="val-stat-info">
                            <h4>À traiter</h4>
                            <p>Vérification requise</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-rapides">
                    <span class="filtre-label-text"><i class="fas fa-filter"></i> Ancienneté :</span>
                    <a href="?anciennete=tous" class="filtre-pill <?= $filtre_anciennete === 'tous' ? 'active' : '' ?>">Tous</a>
                    <a href="?anciennete=recent" class="filtre-pill <?= $filtre_anciennete === 'recent' ? 'active' : '' ?>">
                        <i class="fas fa-circle text-success"></i> Récents (< 30 min)
                            </a>
                            <a href="?anciennete=ancien" class="filtre-pill <?= $filtre_anciennete === 'ancien' ? 'active' : '' ?>">
                                <i class="fas fa-circle text-danger"></i> Anciens (> 30 min) ⚠️
                            </a>
                </div>

                <!-- Tableau des paiements en attente -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-clock"></i> Paiements en attente de validation</h3>
                        <span class="badge-count badge-warning"><?= count($paiements_attente) ?> en attente</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tableValidation">
                            <thead>
                                <tr>
                                    <th width="50">#ID</th>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Frais</th>
                                    <th>Montant</th>
                                    <th>Opérateur</th>
                                    <th>Date</th>
                                    <th>Statut API</th>
                                    <th width="280">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paiements_attente)): ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-check-circle fa-3x text-success"></i>
                                                <h4 class="mt-3">Aucun paiement en attente !</h4>
                                                <p class="text-muted">Toutes les transactions ont été traitées.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paiements_attente as $p):
                                        $estAncien = strtotime($p['date_paiement']) < strtotime('-30 minutes');
                                    ?>
                                        <tr class="val-row <?= $estAncien ? 'row-ancien' : '' ?>">
                                            <td><code>#<?= $p['id_paiement'] ?></code></td>
                                            <td>
                                                <code class="ref-code" title="<?= htmlspecialchars($p['reference_transaction']) ?>">
                                                    <?= htmlspecialchars(substr($p['reference_transaction'], 0, 15)) ?>...
                                                </code>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($p['nom_etudiant']) ?></strong>
                                                <small class="d-block text-muted"><?= htmlspecialchars($p['matricule']) ?></small>
                                            </td>
                                            <td><span class="frais-badge"><?= htmlspecialchars($p['type_frais']) ?></span></td>
                                            <td><strong class="text-warning">$<?= number_format($p['montant_paye'], 2) ?></strong></td>
                                            <td>
                                                <?php if (!empty($p['operateur'])): ?>
                                                    <span class="operateur-badge"><?= htmlspecialchars($p['operateur']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($p['date_paiement'])) ?></small>
                                                <?php if ($estAncien): ?>
                                                    <span class="badge-ancien">⚠️ Ancien</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-pill status-en_attente">
                                                    <i class="fas fa-clock"></i> En attente
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-group">
                                                    <!-- Vérifier auprès de PayLedger 
                                                    <a href="?action=verifier_statut&ref=<?= urlencode($p['reference_transaction']) ?>" 
                                                       class="btn btn-sm btn-info" title="Vérifier le statut réel auprès de PayLedger">
                                                        <i class="fas fa-sync-alt"></i> Vérifier API
                                                    </a> -->
                                                    <!-- Forcer succès (avec justificatif) -->
                                                    <button class="btn btn-sm btn-success"
                                                        onclick="forcerSucces(<?= $p['id_paiement'] ?>, '<?= htmlspecialchars(addslashes($p['nom_etudiant'])) ?>', '<?= htmlspecialchars(addslashes($p['matricule'])) ?>')"
                                                        title="Forcer la validation (justificatif requis)">
                                                        <i class="fas fa-check"></i> Valider
                                                    </button>

                                                    <!-- Rejeter -->
                                                    <button class="btn btn-sm btn-danger"
                                                        onclick="rejeterPaiement(<?= $p['id_paiement'] ?>, '<?= htmlspecialchars(addslashes($p['nom_etudiant'])) ?>')"
                                                        title="Rejeter ce paiement">
                                                        <i class="fas fa-times"></i> Rejeter
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Légende -->
                <div class="legend-card mt-3">
                    <h6><i class="fas fa-info-circle"></i> Guide des actions</h6>
                    <div class="legend-items">
                        <div class="legend-item">
                            <!--
                            <span class="legend-icon bg-info"><i class="fas fa-sync-alt"></i></span>
                            <div>
                                
                                <strong>Vérifier API</strong>
                                <p>Interroge PayLedger pour connaître le statut réel de la transaction.</p> -->
                        </div>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon bg-success"><i class="fas fa-check"></i></span>
                        <div>
                            <strong>Valider (forcer succès)</strong>
                            <p>Confirme manuellement le paiement. <strong>Un justificatif est obligatoire</strong> (SMS, référence, motif).</p>
                        </div>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon bg-danger"><i class="fas fa-times"></i></span>
                        <div>
                            <strong>Rejeter</strong>
                            <p>Annule la transaction. Un motif de rejet est requis pour l'audit.</p>
                        </div>
                    </div>
                </div>
        </div>
        </main>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/secretaire/dashboard_secretaire.js"></script>
    <script src="../assets/js/secretaire/validation_paiements.js"></script>
</body>

</html>