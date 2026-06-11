<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// Clé API officielle PayLedger
$api_key_officielle = 'pl_htSEOb8G7VojrKRHKNHEcQySHqHKYxzldZkLsBU3';
$api_base_url = 'https://pay-ledger.b-manage.net/api/v1/gateway';
$api_expiration = '05/06/2026 08:42';

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// Ajouter une configuration API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom_api = trim($_POST['nom_api'] ?? '');
    $api_key = trim($_POST['api_key'] ?? $api_key_officielle);
    $endpoint = trim($_POST['endpoint'] ?? $api_base_url . '/initiate/mobile');
    
    if (!empty($nom_api)) {
        $stmt = $db->prepare("INSERT INTO api_paiement (nom_api, api_key, endpoint) VALUES (:nom, :key, :endpoint)");
        $stmt->execute(['nom' => $nom_api, 'key' => $api_key, 'endpoint' => $endpoint]);
        $message = "Configuration API ajoutée avec succès !";
        $message_type = 'success';
    }
}

// Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier' && isset($_POST['id_api'])) {
    $id_api = (int)$_POST['id_api'];
    $nom_api = trim($_POST['nom_api'] ?? '');
    $api_key = trim($_POST['api_key'] ?? '');
    $endpoint = trim($_POST['endpoint'] ?? '');
    
    $stmt = $db->prepare("UPDATE api_paiement SET nom_api = :nom, api_key = :key, endpoint = :endpoint WHERE id_api = :id");
    $stmt->execute(['nom' => $nom_api, 'key' => $api_key, 'endpoint' => $endpoint, 'id' => $id_api]);
    $message = "Configuration API mise à jour !";
    $message_type = 'success';
}

// Supprimer
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM api_paiement WHERE id_api = :id");
    $stmt->execute(['id' => (int)$_GET['id']]);
    $message = "Configuration API supprimée.";
    $message_type = 'warning';
}

// Tester une API
if (isset($_GET['action']) && $_GET['action'] === 'tester' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM api_paiement WHERE id_api = :id");
    $stmt->execute(['id' => (int)$_GET['id']]);
    $api = $stmt->fetch();
    
    if ($api) {
        $test_result = testerAPI($api);
        $message = $test_result['message'];
        $message_type = $test_result['success'] ? 'success' : 'danger';
    }
}

// Tester la connexion PayLedger
if (isset($_GET['action']) && $_GET['action'] === 'tester_payledger') {
    $test_result = testerConnexionPayLedger($api_key_officielle);
    $message = $test_result['message'];
    $message_type = $test_result['success'] ? 'success' : 'danger';
}

// ========== INSÉRER L'API OFFICIELLE SI ELLE N'EXISTE PAS ==========
$stmt = $db->query("SELECT COUNT(*) as total FROM api_paiement");
if ($stmt->fetch()['total'] == 0) {
    $stmt = $db->prepare("INSERT INTO api_paiement (nom_api, api_key, endpoint) VALUES (:nom, :key, :endpoint)");
    $stmt->execute([
        'nom' => 'PayLedger - Mobile Money',
        'key' => $api_key_officielle,
        'endpoint' => $api_base_url . '/initiate/mobile'
    ]);
}

// ========== RÉCUPÉRATION DES DONNÉES ==========

// Configurations API
$stmt = $db->query("SELECT * FROM api_paiement ORDER BY nom_api");
$apis = $stmt->fetchAll();

// Statistiques
$stmt = $db->query("SELECT COUNT(*) as total FROM transaction_mobile_money");
$total_transactions_api = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM transaction_mobile_money WHERE statut_api = 'succes'");
$transactions_succes = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM transaction_mobile_money WHERE statut_api = 'echec'");
$transactions_echec = $stmt->fetch()['total'] ?? 0;

// Dernières transactions
$stmt = $db->query("
    SELECT tmm.*, p.reference_transaction, p.montant_paye, p.statut as statut_paiement,
           u.nom as nom_etudiant
    FROM transaction_mobile_money tmm 
    JOIN paiements p ON tmm.id_paiement = p.id_paiement 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    ORDER BY tmm.id_transaction DESC 
    LIMIT 20
");
$transactions = $stmt->fetchAll();

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();

// ========== FONCTIONS DE TEST ==========

function testerAPI($api) {
    $endpoint = $api['endpoint'];
    $api_key = $api['api_key'];
    
    // Vérifier si l'URL est valide
    if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
        return [
            'success' => false,
            'message' => "❌ URL d'endpoint invalide : {$endpoint}"
        ];
    }
    
    // Test réel avec cURL
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'amount' => '1',
            'currency' => 'CDF',
            'phone' => '+243970000000',
            'external_reference' => 'TEST-' . date('YmdHis'),
            'description' => 'Test de connexion ISTAM'
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'message' => "❌ Erreur de connexion : {$error}"
        ];
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 201) {
        return [
            'success' => true,
            'message' => "✅ Connexion réussie à {$api['nom_api']} !<br>
                          Code HTTP: {$httpCode}<br>
                          Référence PayLedger: " . ($data['reference'] ?? 'N/A') . "<br>
                          Statut: " . ($data['status'] ?? 'N/A') . "<br>
                          Message: " . ($data['message'] ?? 'N/A')
        ];
    } elseif ($httpCode === 401) {
        return [
            'success' => false,
            'message' => "❌ Erreur 401 : Clé API invalide, inactive ou expirée.<br>
                          Vérifiez que votre clé est valide et n'a pas expiré.<br>
                          Expiration : {$GLOBALS['api_expiration']}"
        ];
    } elseif ($httpCode === 400) {
        return [
            'success' => false,
            'message' => "⚠️ Erreur 400 : " . json_encode($data['errors'] ?? $data) . "<br>
                          L'endpoint est valide mais les paramètres de test sont rejetés."
        ];
    } else {
        return [
            'success' => false,
            'message' => "⚠️ Code HTTP {$httpCode} : " . ($data['error'] ?? $data['message'] ?? 'Erreur inconnue')
        ];
    }
}

function testerConnexionPayLedger($api_key) {
    $url = 'https://pay-ledger.b-manage.net/api/v1/gateway/initiate/mobile';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'amount' => '100',
            'currency' => 'CDF',
            'phone' => '+243970000000',
            'external_reference' => 'ISTAM-TEST-' . date('YmdHis'),
            'description' => 'Test de connexion ISTAM Paiement',
            'customer_name' => 'Admin Test'
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $responseData = json_decode($response, true);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'message' => "❌ Erreur de connexion au serveur PayLedger : {$error}"
        ];
    }
    
    if ($httpCode === 201) {
        return [
            'success' => true,
            'message' => "✅ Connexion PayLedger réussie !<br><br>
                          <strong>Code HTTP:</strong> {$httpCode}<br>
                          <strong>Référence:</strong> " . ($responseData['reference'] ?? 'N/A') . "<br>
                          <strong>Statut:</strong> " . ($responseData['status'] ?? 'N/A') . "<br>
                          <strong>Gateway Ref:</strong> " . ($responseData['gateway_reference'] ?? 'N/A') . "<br>
                          <strong>Message:</strong> " . ($responseData['message'] ?? 'N/A')
        ];
    } elseif ($httpCode === 401) {
        return [
            'success' => false,
            'message' => "❌ Erreur 401 : Clé API invalide, inactive ou expirée.<br>
                          Votre clé : <code>pl_htSEOb8G7VojrKRHKNHEcQySHqHKYxzldZkLsBU3</code><br>
                          Expire le : <strong>{$GLOBALS['api_expiration']}</strong><br>
                          Vérifiez auprès de PayLedger si la clé est toujours active."
        ];
    } elseif ($httpCode === 400) {
        return [
            'success' => true,
            'message' => "⚠️ L'API PayLedger est accessible (HTTP {$httpCode}).<br>
                          Les paramètres de test sont rejetés mais l'authentification fonctionne.<br>
                          Détails : " . json_encode($responseData)
        ];
    } else {
        return [
            'success' => false,
            'message' => "⚠️ Code HTTP {$httpCode}<br>
                          Réponse : " . htmlspecialchars(substr($response, 0, 500))
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Paiement - PayLedger - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/api_paiement.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar_admin.php'; ?>
        <div class="main-content">
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_admin.php'; 
            ?>
            <main class="dashboard-content">
                
                <!-- En-tête -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-plug"></i> API de Paiement - PayLedger
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-shield-alt"></i> 
                                Intégration officielle PayLedger | 
                                <span class="text-warning">Expire le <?= $api_expiration ?></span>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <a href="?action=tester_payledger" class="btn btn-success btn-sm me-2">
                                <i class="fas fa-play-circle"></i> Tester la connexion PayLedger
                            </a>
                            <button class="btn btn-primary btn-sm" onclick="ouvrirModalAjouter()">
                                <i class="fas fa-plus-circle"></i> Configurer un endpoint
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <div><?= $message ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Carte Info API Officielle -->
                <div class="api-official-card mb-4">
                    <div class="api-official-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="api-official-icon">
                                <i class="fas fa-shield-haltered"></i>
                            </div>
                            <div>
                                <h3>PayLedger - API Officielle</h3>
                                <p>Passerelle de paiement unifiée Mobile Money, Carte et Payout</p>
                            </div>
                        </div>
                        <span class="api-status-badge <?= strtotime($api_expiration) > time() ? 'badge-active' : 'badge-expired' ?>">
                            <i class="fas fa-<?= strtotime($api_expiration) > time() ? 'check-circle' : 'times-circle' ?>"></i>
                            <?= strtotime($api_expiration) > time() ? 'Active' : 'Expirée' ?>
                        </span>
                    </div>
                    <div class="api-official-body">
                        <div class="row">
                            <div class="col-lg-4 mb-3">
                                <label class="detail-label">Clé API</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= $api_key_officielle ?>" readonly id="officialKey" style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;">
                                    <button class="btn btn-outline-secondary" onclick="copierCle('officialKey')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <label class="detail-label">URL de base</label>
                                <input type="text" class="form-control" value="<?= $api_base_url ?>" readonly style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;">
                            </div>
                            <div class="col-lg-4 mb-3">
                                <label class="detail-label">Expiration</label>
                                <input type="text" class="form-control" value="<?= $api_expiration ?>" readonly style="color:#f59e0b;font-weight:600;">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <label class="detail-label">Header HTTP</label>
                                <code class="header-code">Authorization: Bearer <?= $api_key_officielle ?></code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endpoints disponibles -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="endpoint-card">
                            <div class="endpoint-method post">POST</div>
                            <div class="endpoint-icon bg-blue">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h5>Mobile Money</h5>
                            <code class="endpoint-url">/initiate/mobile</code>
                            <p class="endpoint-desc">Orange Money, Airtel, M-Pesa</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="endpoint-card">
                            <div class="endpoint-method post">POST</div>
                            <div class="endpoint-icon bg-purple">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h5>Carte Bancaire</h5>
                            <code class="endpoint-url">/initiate/card</code>
                            <p class="endpoint-desc">Paiement par carte</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="endpoint-card">
                            <div class="endpoint-method post">POST</div>
                            <div class="endpoint-icon bg-green">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <h5>Payout</h5>
                            <code class="endpoint-url">/initiate/payout</code>
                            <p class="endpoint-desc">Transfert d'argent</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="endpoint-card">
                            <div class="endpoint-method get">GET</div>
                            <div class="endpoint-icon bg-orange">
                                <i class="fas fa-search"></i>
                            </div>
                            <h5>Statut</h5>
                            <code class="endpoint-url">/status/{reference}</code>
                            <p class="endpoint-desc">Vérifier transaction</p>
                        </div>
                    </div>
                </div>

                <!-- Documentation rapide -->
                <div class="info-doc-card mb-4">
                    <div class="doc-header">
                        <h4><i class="fas fa-book"></i> Exemple d'appel : Initier un paiement Mobile Money</h4>
                    </div>
                    <div class="doc-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <h5><i class="fas fa-terminal"></i> Requête cURL</h5>
                                <div class="code-block">
                                    <pre><code>curl -X POST "<?= $api_base_url ?>/initiate/mobile" \
  -H "Authorization: Bearer <?= $api_key_officielle ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 200.00,
    "currency": "USD",
    "phone": "+243812345678",
    "external_reference": "ISTAM-FRAIS-001",
    "description": "Minerval Tranche 1 - Licence 3 Informatique",
    "customer_name": "Jean Dupont"
  }'</code></pre>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h5><i class="fas fa-reply"></i> Réponse attendue (201 Created)</h5>
                                <div class="code-block code-success">
                                    <pre><code>{
  "reference": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "gateway_reference": "FLX-20260508-ABC123",
  "message": "Paiement initié avec succès",
  "payment_type": "mobile_money",
  "amount": "200.00",
  "currency": "USD"
}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="api-stats mb-4">
                    <div class="api-stat-card">
                        <div class="api-stat-icon bg-blue"><i class="fas fa-exchange-alt"></i></div>
                        <div class="api-stat-info"><h4><?= number_format($total_transactions_api) ?></h4><p>Transactions</p></div>
                    </div>
                    <div class="api-stat-card">
                        <div class="api-stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
                        <div class="api-stat-info"><h4><?= number_format($transactions_succes) ?></h4><p>Succès</p></div>
                    </div>
                    <div class="api-stat-card">
                        <div class="api-stat-icon bg-red"><i class="fas fa-times-circle"></i></div>
                        <div class="api-stat-info"><h4><?= number_format($transactions_echec) ?></h4><p>Échecs</p></div>
                    </div>
                    <div class="api-stat-card">
                        <div class="api-stat-icon bg-dark"><i class="fas fa-plug"></i></div>
                        <div class="api-stat-info"><h4><?= count($apis) ?></h4><p>Configs</p></div>
                    </div>
                </div>

                <!-- Configurations API enregistrées -->
                <div class="table-card mb-4">
                    <div class="table-card-header">
                        <h3><i class="fas fa-cogs"></i> Mes configurations d'endpoints</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr><th>Nom</th><th>Clé API</th><th>Endpoint</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apis as $api): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($api['nom_api']) ?></strong></td>
                                        <td><code><?= substr($api['api_key'], 0, 15) ?>...</code></td>
                                        <td><code><?= htmlspecialchars($api['endpoint']) ?></code></td>
                                        <td>
                                            <a href="?action=tester&id=<?= $api['id_api'] ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-play"></i> Tester</a>
                                            <button class="btn btn-sm btn-outline-primary" onclick="ouvrirModalModifier(<?= htmlspecialchars(json_encode($api)) ?>)"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmerSuppression(<?= $api['id_api'] ?>, '<?= htmlspecialchars(addslashes($api['nom_api'])) ?>')"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Dernières transactions -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-history"></i> Dernières transactions</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table table-sm">
                            <thead>
                                <tr><th>Réf.</th><th>Étudiant</th><th>Téléphone</th><th>Opérateur</th><th>Montant</th><th>Statut API</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune transaction API enregistrée.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $tr): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars(substr($tr['reference_transaction'], 0, 15)) ?></code></td>
                                            <td><?= htmlspecialchars($tr['nom_etudiant']) ?></td>
                                            <td><code><?= htmlspecialchars($tr['numero_telephone']) ?></code></td>
                                            <td><?= htmlspecialchars($tr['operateur'] ?? 'N/A') ?></td>
                                            <td><strong>$<?= number_format($tr['montant_paye'], 2) ?></strong></td>
                                            <td><span class="status-pill pill-<?= $tr['statut_api'] === 'succes' ? 'success' : 'danger' ?>"><?= ucfirst($tr['statut_api'] ?? 'N/A') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- MODALS (identiques à la version précédente) -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Ajouter un endpoint</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom_api" class="form-control" value="PayLedger - Mobile Money" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Clé API</label>
                            <input type="text" name="api_key" class="form-control" value="<?= $api_key_officielle ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Endpoint</label>
                            <input type="text" name="endpoint" class="form-control" value="<?= $api_base_url ?>/initiate/mobile" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalModifier" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_api" id="modIdApi">
                        <div class="mb-3"><label class="form-label">Nom</label><input type="text" name="nom_api" id="modNomApi" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Clé API</label><input type="text" name="api_key" id="modApiKey" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Endpoint</label><input type="text" name="endpoint" id="modEndpoint" class="form-control" required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalSupprimer" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                    <h5>Supprimer cette configuration ?</h5>
                    <p class="text-muted" id="supprimerInfo"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" class="btn btn-danger" id="btnConfirmerSuppression"><i class="fas fa-trash-alt"></i> Supprimer</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/api_paiement.js"></script>
    <script>
    function copierCle(inputId) {
        const input = document.getElementById(inputId);
        input.select();
        document.execCommand('copy');
        alert('✅ Clé API copiée !');
    }
    </script>
</body>
</html>