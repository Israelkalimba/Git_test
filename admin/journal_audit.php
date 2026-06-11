<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== FILTRES ==========
$filtre_type = $_GET['type'] ?? '';
$filtre_action = $_GET['action_filtre'] ?? '';
$filtre_date_debut = $_GET['date_debut'] ?? '';
$filtre_date_fin = $_GET['date_fin'] ?? '';
$filtre_recherche = trim($_GET['recherche'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$par_page = 25;
$offset = ($page - 1) * $par_page;

// ========== CONSTRUCTION DE LA REQUÊTE ==========
$where = "WHERE 1=1";
$params = [];

if (!empty($filtre_type)) {
    $where .= " AND al.type_action = :type";
    $params['type'] = $filtre_type;
}
if (!empty($filtre_action)) {
    $where .= " AND al.action = :action";
    $params['action'] = $filtre_action;
}
if (!empty($filtre_date_debut)) {
    $where .= " AND DATE(al.date_action) >= :date_debut";
    $params['date_debut'] = $filtre_date_debut;
}
if (!empty($filtre_date_fin)) {
    $where .= " AND DATE(al.date_action) <= :date_fin";
    $params['date_fin'] = $filtre_date_fin;
}
if (!empty($filtre_recherche)) {
    $where .= " AND (al.description LIKE :recherche OR u.nom LIKE :recherche2 OR al.adresse_ip LIKE :recherche3)";
    $params['recherche'] = "%{$filtre_recherche}%";
    $params['recherche2'] = "%{$filtre_recherche}%";
    $params['recherche3'] = "%{$filtre_recherche}%";
}

// ========== VÉRIFIER/CREER LA TABLE AUDIT_LOG ==========
try {
    $db->query("SELECT 1 FROM audit_log LIMIT 1");
} catch (PDOException $e) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id_log INT PRIMARY KEY AUTO_INCREMENT,
            id_utilisateur INT DEFAULT NULL,
            type_action VARCHAR(50) NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            adresse_ip VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_date (date_action),
            INDEX idx_type (type_action),
            INDEX idx_utilisateur (id_utilisateur)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Insérer une première entrée
    $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) 
                          VALUES (:uid, 'system', 'initialisation', 'Création du journal d\'audit', :ip)");
    $stmt->execute(['uid' => $admin_id, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
}

// ========== TOTAL POUR PAGINATION ==========
$sql_count = "SELECT COUNT(*) as total FROM audit_log al LEFT JOIN utilisateurs u ON al.id_utilisateur = u.id_utilisateur {$where}";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_logs = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total_logs / $par_page);

// ========== LOGS ==========
$sql = "SELECT al.*, u.nom as nom_utilisateur, u.email, u.role 
        FROM audit_log al 
        LEFT JOIN utilisateurs u ON al.id_utilisateur = u.id_utilisateur 
        {$where} 
        ORDER BY al.date_action DESC 
        LIMIT {$par_page} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// ========== STATISTIQUES DU JOUR ==========
$stmt = $db->query("SELECT COUNT(*) as total FROM audit_log WHERE DATE(date_action) = CURDATE()");
$logs_aujourdhui = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(DISTINCT adresse_ip) as total FROM audit_log WHERE DATE(date_action) = CURDATE()");
$ips_uniques = $stmt->fetch()['total'] ?? 0;

// Types d'actions disponibles
$stmt = $db->query("SELECT DISTINCT type_action FROM audit_log ORDER BY type_action");
$types_actions = $stmt->fetchAll();

// Actions disponibles
$stmt = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action");
$actions_disponibles = $stmt->fetchAll();

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();

// Fonction helper pour afficher l'icône selon le type
function getTypeIcon($type) {
    $icons = [
        'connexion' => 'fa-sign-in-alt text-success',
        'deconnexion' => 'fa-sign-out-alt text-secondary',
        'paiement' => 'fa-credit-card text-primary',
        'creation' => 'fa-plus-circle text-info',
        'modification' => 'fa-edit text-warning',
        'suppression' => 'fa-trash-alt text-danger',
        'configuration' => 'fa-cogs text-purple',
        'system' => 'fa-server text-dark',
        'erreur' => 'fa-exclamation-triangle text-danger',
        'securite' => 'fa-shield-alt text-orange'
    ];
    return $icons[$type] ?? 'fa-circle text-muted';
}

function getTypeBadge($type) {
    $badges = [
        'connexion' => 'badge-success',
        'deconnexion' => 'badge-secondary',
        'paiement' => 'badge-primary',
        'creation' => 'badge-info',
        'modification' => 'badge-warning',
        'suppression' => 'badge-danger',
        'configuration' => 'badge-purple',
        'system' => 'badge-dark',
        'erreur' => 'badge-danger',
        'securite' => 'badge-orange'
    ];
    return $badges[$type] ?? 'badge-light';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'Audit - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/journal_audit.css">
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
                        <div class="col-lg-6">
                            <h1 class="page-title">
                                <i class="fas fa-history"></i> Journal d'Audit
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-shield-alt"></i> 
                                Traçabilité complète de toutes les actions effectuées dans le système.
                                <span class="text-success"><?= $logs_aujourdhui ?> actions aujourd'hui</span>
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-outline-danger btn-sm me-2" onclick="viderLogs()">
                                <i class="fas fa-eraser"></i> Purger les logs
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats rapides -->
                <div class="audit-stats">
                    <div class="audit-stat-item">
                        <div class="audit-stat-icon bg-dark">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="audit-stat-info">
                            <h4><?= number_format($total_logs, 0, ',', ' ') ?></h4>
                            <p>Entrées totales</p>
                        </div>
                    </div>
                    <div class="audit-stat-item">
                        <div class="audit-stat-icon bg-green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="audit-stat-info">
                            <h4><?= $logs_aujourdhui ?></h4>
                            <p>Aujourd'hui</p>
                        </div>
                    </div>
                    <div class="audit-stat-item">
                        <div class="audit-stat-icon bg-blue">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <div class="audit-stat-info">
                            <h4><?= $ips_uniques ?></h4>
                            <p>IPs uniques (24h)</p>
                        </div>
                    </div>
                    <div class="audit-stat-item">
                        <div class="audit-stat-icon bg-purple">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="audit-stat-info">
                            <h4><?= date('H:i') ?></h4>
                            <p>Dernière vérification</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-section">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Type</label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <?php foreach ($types_actions as $ta): ?>
                                        <option value="<?= htmlspecialchars($ta['type_action']) ?>" <?= $filtre_type === $ta['type_action'] ? 'selected' : '' ?>>
                                            <?= ucfirst(htmlspecialchars($ta['type_action'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-bolt"></i> Action</label>
                                <select name="action_filtre" class="form-select form-select-sm">
                                    <option value="">Toutes</option>
                                    <?php foreach ($actions_disponibles as $ad): ?>
                                        <option value="<?= htmlspecialchars($ad['action']) ?>" <?= $filtre_action === $ad['action'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ad['action']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-calendar"></i> Du</label>
                                <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= $filtre_date_debut ?>">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-calendar"></i> Au</label>
                                <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= $filtre_date_fin ?>">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-12 mb-2">
                                <label class="filtre-label"><i class="fas fa-search"></i> Recherche</label>
                                <input type="text" name="recherche" class="form-control form-control-sm" placeholder="Mot-clé..." value="<?= htmlspecialchars($filtre_recherche) ?>">
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-12 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if (!empty($filtre_type) || !empty($filtre_date_debut) || !empty($filtre_recherche)): ?>
                                    <a href="journal_audit.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                        <i class="fas fa-times"></i> Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tableau des logs -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Logs d'activité (<?= number_format($total_logs) ?> entrées)</h3>
                        <span class="text-muted small">Page <?= $page ?>/<?= max(1, $total_pages) ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table table-sm" id="tableLogs">
                            <thead>
                                <tr>
                                    <th width="50">#ID</th>
                                    <th width="150">Date</th>
                                    <th width="100">Type</th>
                                    <th>Utilisateur</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th width="120">Adresse IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-history fa-3x"></i>
                                                <h4 class="mt-3">Aucun log trouvé</h4>
                                                <p class="text-muted">Essayez de modifier les filtres.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="log-row log-type-<?= htmlspecialchars($log['type_action']) ?>">
                                            <td><code class="log-id">#<?= $log['id_log'] ?></code></td>
                                            <td>
                                                <div class="log-date">
                                                    <i class="far fa-clock"></i>
                                                    <span><?= date('d/m/Y H:i:s', strtotime($log['date_action'])) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="log-type-badge <?= getTypeBadge($log['type_action']) ?>">
                                                    <i class="fas <?= getTypeIcon($log['type_action']) ?>"></i>
                                                    <?= ucfirst(htmlspecialchars($log['type_action'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['nom_utilisateur']): ?>
                                                    <div class="log-user">
                                                        <div class="log-user-avatar <?= $log['role'] === 'admin' ? 'avatar-admin' : ($log['role'] === 'secretaire' ? 'avatar-secretaire' : 'avatar-etudiant') ?>">
                                                            <i class="fas fa-<?= $log['role'] === 'admin' ? 'user-shield' : ($log['role'] === 'secretaire' ? 'user-tie' : 'user-graduate') ?>"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($log['nom_utilisateur']) ?></strong>
                                                            <?php if ($log['email']): ?>
                                                                <small class="d-block text-muted"><?= htmlspecialchars($log['email']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-ghost"></i> Système</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="log-action"><?= htmlspecialchars($log['action']) ?></span>
                                            </td>
                                            <td>
                                                <span class="log-description"><?= htmlspecialchars($log['description'] ?? '—') ?></span>
                                            </td>
                                            <td>
                                                <code class="log-ip" title="User Agent: <?= htmlspecialchars($log['user_agent'] ?? 'N/A') ?>">
                                                    <i class="fas fa-globe"></i> <?= htmlspecialchars($log['adresse_ip'] ?? 'N/A') ?>
                                                </code>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center">
                                    <?php 
                                    $url_params = $_GET;
                                    unset($url_params['page']);
                                    $base_url = '?' . http_build_query($url_params);
                                    if (!empty($url_params)) $base_url .= '&';
                                    else $base_url = '?';
                                    ?>
                                    
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $base_url ?>page=1"><i class="fas fa-angle-double-left"></i></a>
                                    </li>
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $base_url ?>page=<?= $page - 1 ?>"><i class="fas fa-angle-left"></i></a>
                                    </li>
                                    
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= $base_url ?>page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $base_url ?>page=<?= $page + 1 ?>"><i class="fas fa-angle-right"></i></a>
                                    </li>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $base_url ?>page=<?= $total_pages ?>"><i class="fas fa-angle-double-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                            <p class="text-center text-muted small mt-2">
                                Affichage de <?= count($logs) ?> logs sur <?= number_format($total_logs) ?> 
                                (Page <?= $page ?> sur <?= $total_pages ?>)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/journal_audit.js"></script>
</body>
</html>