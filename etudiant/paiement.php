<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/database.php';

Auth::checkSession('etudiant');

$db = Database::getInstance();
$etudiant_nom = $_SESSION['user_nom'] ?? 'Ã‰tudiant';
$etudiant_id = $_SESSION['user_id'] ?? 1;

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// Marquer tout comme lu
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $stmt = $db->prepare("UPDATE notifications SET statut = 'lu' WHERE id_utilisateur = :id AND statut = 'non_lu'");
    $stmt->execute(['id' => $etudiant_id]);
    $nb = $stmt->rowCount();
    $message = "{$nb} notification(s) marquÃ©e(s) comme lue(s).";
    $message_type = 'success';
}

// Marquer une notification comme lue
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $stmt = $db->prepare("UPDATE notifications SET statut = 'lu' WHERE id_notification = :id AND id_utilisateur = :uid");
    $stmt->execute(['id' => (int)$_GET['id'], 'uid' => $etudiant_id]);
}

// Supprimer une notification
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM notifications WHERE id_notification = :id AND id_utilisateur = :uid");
    $stmt->execute(['id' => (int)$_GET['id'], 'uid' => $etudiant_id]);
    $message = "Notification supprimÃ©e.";
    $message_type = 'info';
}

// Supprimer toutes les notifications lues
if (isset($_GET['action']) && $_GET['action'] === 'delete_all_read') {
    $stmt = $db->prepare("DELETE FROM notifications WHERE id_utilisateur = :uid AND statut = 'lu'");
    $stmt->execute(['uid' => $etudiant_id]);
    $nb = $stmt->rowCount();
    $message = "{$nb} notification(s) lue(s) supprimÃ©e(s).";
    $message_type = 'info';
}

// Supprimer toutes les notifications
if (isset($_GET['action']) && $_GET['action'] === 'delete_all') {
    $stmt = $db->prepare("DELETE FROM notifications WHERE id_utilisateur = :uid");
    $stmt->execute(['uid' => $etudiant_id]);
    $nb = $stmt->rowCount();
    $message = "Toutes les notifications ({$nb}) ont Ã©tÃ© supprimÃ©es.";
    $message_type = 'warning';
}

// ========== FILTRES ==========
$filtre_statut = $_GET['statut'] ?? '';
$filtre_date = $_GET['date'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$par_page = 20;
$offset = ($page - 1) * $par_page;

// ========== RÃ‰CUPÃ‰RATION DES NOTIFICATIONS ==========
$where = "WHERE n.id_utilisateur = :uid";
$params = ['uid' => $etudiant_id];

if ($filtre_statut === 'non_lu') {
    $where .= " AND n.statut = 'non_lu'";
} elseif ($filtre_statut === 'lu') {
    $where .= " AND n.statut = 'lu'";
}

if ($filtre_date === 'aujourdhui') {
    $where .= " AND DATE(n.date_envoi) = CURDATE()";
} elseif ($filtre_date === 'semaine') {
    $where .= " AND n.date_envoi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filtre_date === 'mois') {
    $where .= " AND n.date_envoi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

// Total
$sql_count = "SELECT COUNT(*) as total FROM notifications n {$where}";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_notifs = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total_notifs / $par_page);

// Notifications
$sql = "SELECT n.* FROM notifications n {$where} ORDER BY n.date_envoi DESC LIMIT {$par_page} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Stats
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :uid");
$stmt->execute(['uid' => $etudiant_id]);
$total_toutes = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :uid AND statut = 'non_lu'");
$stmt->execute(['uid' => $etudiant_id]);
$total_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :uid AND statut = 'lu'");
$stmt->execute(['uid' => $etudiant_id]);
$total_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :uid AND DATE(date_envoi) = CURDATE()");
$stmt->execute(['uid' => $etudiant_id]);
$total_aujourdhui = $stmt->fetch()['total'] ?? 0;

// Pour la navbar
$notifications_non_lues = $total_non_lues;
$stmt_nav = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :uid ORDER BY date_envoi DESC LIMIT 5");
$stmt_nav->execute(['uid' => $etudiant_id]);
$navbar_notifications = $stmt_nav->fetchAll();

// Fonction helper icÃ´ne
function getNotifIconEtu($message) {
    $msg = strtolower($message);
    if (strpos($msg, 'paiement') !== false || strpos($msg, 'transaction') !== false) {
        return ['icon' => 'fa-credit-card', 'color' => 'icon-blue'];
    } elseif (strpos($msg, 'succÃ¨s') !== false || strpos($msg, 'rÃ©ussi') !== false || strpos($msg, 'confirmÃ©') !== false) {
        return ['icon' => 'fa-check-circle', 'color' => 'icon-green'];
    } elseif (strpos($msg, 'Ã©chec') !== false || strpos($msg, 'erreur') !== false || strpos($msg, 'rejetÃ©') !== false) {
        return ['icon' => 'fa-exclamation-triangle', 'color' => 'icon-red'];
    } elseif (strpos($msg, 'frais') !== false || strpos($msg, 'minerval') !== false) {
        return ['icon' => 'fa-file-invoice', 'color' => 'icon-purple'];
    } elseif (strpos($msg, 'reÃ§u') !== false || strpos($msg, 'bordereau') !== false || strpos($msg, 'pdf') !== false) {
        return ['icon' => 'fa-file-pdf', 'color' => 'icon-orange'];
    } else {
        return ['icon' => 'fa-bell', 'color' => 'icon-blue'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Ã‰tudiant ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/etudiant/dashboard_etudiant.css">
    <link rel="stylesheet" href="../assets/css/etudiant/notifications_etudiant.css">
</head>
<body>
    <div class="etudiant-layout">
        <!-- SIDEBAR -->
        <?php include 'includes/sidebar_etudiant.php'; ?>

        <!-- CONTENU PRINCIPAL -->
        <div class="main-content">
            <!-- NAVBAR -->
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_etudiant.php'; 
            ?>

            <!-- CONTENU -->
            <main class="dashboard-content">
                
                <!-- En-tÃªte -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <h1 class="page-title">
                                <i class="fas fa-bell"></i> Mes Notifications
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Consultez vos notifications. 
                                <span class="text-danger fw-bold"><?= $total_non_lues ?> non lue(s)</span>
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <div class="btn-group-actions">
                                <?php if ($total_non_lues > 0): ?>
                                    <a href="?action=mark_all_read" class="btn btn-success btn-sm">
                                        <i class="fas fa-check-double"></i> Tout marquer lu
                                    </a>
                                <?php endif; ?>
                                <?php if ($total_lues > 0): ?>
                                    <a href="?action=delete_all_read" class="btn btn-outline-warning btn-sm" 
                                       onclick="return confirm('Supprimer toutes les notifications lues (<?= $total_lues ?>) ?')">
                                        <i class="fas fa-eraser"></i> Supprimer les lues
                                    </a>
                                <?php endif; ?>
                                <?php if ($total_toutes > 0): ?>
                                    <a href="?action=delete_all" class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('âš ï¸ Supprimer TOUTES les notifications (<?= $total_toutes ?>) ?')">
                                        <i class="fas fa-trash-alt"></i> Tout supprimer
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle"></i> <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats mini -->
                <div class="notif-stats-etu">
                    <a href="notifications.php" class="notif-stat-card-etu <?= empty($filtre_statut) && empty($filtre_date) ? 'active' : '' ?>">
                        <div class="notif-stat-icon-etu bg-blue">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notif-stat-info-etu">
                            <h4><?= $total_toutes ?></h4>
                            <p>Toutes</p>
                        </div>
                    </a>
                    <a href="notifications.php?statut=non_lu" class="notif-stat-card-etu <?= $filtre_statut === 'non_lu' ? 'active' : '' ?>">
                        <div class="notif-stat-icon-etu bg-red">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="notif-stat-info-etu">
                            <h4><?= $total_non_lues ?></h4>
                            <p>Non lues</p>
                        </div>
                    </a>
                    <a href="notifications.php?statut=lu" class="notif-stat-card-etu <?= $filtre_statut === 'lu' ? 'active' : '' ?>">
                        <div class="notif-stat-icon-etu bg-green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="notif-stat-info-etu">
                            <h4><?= $total_lues ?></h4>
                            <p>Lues</p>
                        </div>
                    </a>
                    <a href="notifications.php?date=aujourdhui" class="notif-stat-card-etu <?= $filtre_date === 'aujourdhui' ? 'active' : '' ?>">
                        <div class="notif-stat-icon-etu bg-purple">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="notif-stat-info-etu">
                            <h4><?= $total_aujourdhui ?></h4>
                            <p>Aujourd'hui</p>
                        </div>
                    </a>
                </div>

                <!-- Filtres rapides -->
                <div class="filtres-rapides-etu">
                    <span class="filtre-label-text"><i class="fas fa-filter"></i> Filtrer :</span>
                    <a href="notifications.php" class="filtre-pill-etu <?= empty($filtre_statut) && empty($filtre_date) ? 'active' : '' ?>">
                        <i class="fas fa-list"></i> Tout
                    </a>
                    <a href="?statut=non_lu" class="filtre-pill-etu <?= $filtre_statut === 'non_lu' ? 'active' : '' ?>">
                        <i class="fas fa-circle"></i> Non lues (<?= $total_non_lues ?>)
                    </a>
                    <a href="?statut=lu" class="filtre-pill-etu <?= $filtre_statut === 'lu' ? 'active' : '' ?>">
                        <i class="fas fa-check"></i> Lues
                    </a>
                    <a href="?date=aujourdhui" class="filtre-pill-etu <?= $filtre_date === 'aujourdhui' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-day"></i> Aujourd'hui
                    </a>
                    <a href="?date=semaine" class="filtre-pill-etu <?= $filtre_date === 'semaine' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-week"></i> Cette semaine
                    </a>
                    <a href="?date=mois" class="filtre-pill-etu <?= $filtre_date === 'mois' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Ce mois
                    </a>
                </div>

                <!-- Liste des notifications -->
                <div class="notif-list-card-etu">
                    <div class="notif-list-header-etu">
                        <h3><i class="fas fa-envelope-open-text"></i> 
                            <?php
                            if ($filtre_statut === 'non_lu') echo "Notifications non lues";
                            elseif ($filtre_statut === 'lu') echo "Notifications lues";
                            elseif ($filtre_date === 'aujourdhui') echo "Notifications d'aujourd'hui";
                            elseif ($filtre_date === 'semaine') echo "Notifications de la semaine";
                            elseif ($filtre_date === 'mois') echo "Notifications du mois";
                            else echo "Toutes les notifications";
                            ?>
                        </h3>
                        <span class="notif-count-badge-etu"><?= $total_notifs ?> notification(s)</span>
                    </div>

                    <div class="notif-list-body-etu">
                        <?php if (empty($notifications)): ?>
                            <div class="notif-empty-etu">
                                <i class="fas fa-bell-slash"></i>
                                <h4>Aucune notification</h4>
                                <p class="text-muted">
                                    <?php if (!empty($filtre_statut) || !empty($filtre_date)): ?>
                                        Essayez de modifier les filtres.
                                    <?php else: ?>
                                        Vous recevrez des notifications pour vos paiements et reÃ§us.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): 
                                $notif_icon = getNotifIconEtu($notif['message']);
                                $is_unread = $notif['statut'] === 'non_lu';
                            ?>
                                <div class="notif-item-etu <?= $is_unread ? 'notif-unread' : '' ?>" id="notif-<?= $notif['id_notification'] ?>">
                                    <!-- IcÃ´ne -->
                                    <div class="notif-item-icon-etu <?= $notif_icon['color'] ?>">
                                        <i class="fas <?= $notif_icon['icon'] ?>"></i>
                                    </div>
                                    
                                    <!-- Contenu -->
                                    <div class="notif-item-content-etu">
                                        <p class="notif-item-message-etu"><?= htmlspecialchars($notif['message']) ?></p>
                                        <div class="notif-item-meta-etu">
                                            <span class="notif-item-date-etu">
                                                <i class="far fa-clock"></i>
                                                <?= date('d/m/Y H:i', strtotime($notif['date_envoi'])) ?>
                                            </span>
                                            <?php if ($is_unread): ?>
                                                <span class="notif-badge-etu badge-unread-etu">
                                                    <i class="fas fa-circle"></i> Non lu
                                                </span>
                                            <?php else: ?>
                                                <span class="notif-badge-etu badge-read-etu">
                                                    <i class="fas fa-check-circle"></i> Lu
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="notif-item-actions-etu">
                                        <?php if ($is_unread): ?>
                                            <a href="?action=mark_read&id=<?= $notif['id_notification'] ?>" 
                                               class="btn-action-notif-etu btn-mark-read-etu" 
                                               title="Marquer comme lu">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $notif['id_notification'] ?>" 
                                           class="btn-action-notif-etu btn-delete-notif-etu" 
                                           title="Supprimer"
                                           onclick="return confirm('Supprimer cette notification ?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="notif-pagination-etu">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center">
                                    <?php 
                                    $url_params = $_GET;
                                    unset($url_params['page']);
                                    $base_url = '?' . http_build_query($url_params);
                                    if (!empty($url_params)) $base_url .= '&';
                                    else $base_url = '?';
                                    
                                    for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= $base_url ?>page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <p class="text-center text-muted small mt-2">
                                Page <?= $page ?> sur <?= $total_pages ?> (<?= $total_notifs ?> notifications)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/etudiant/dashboard_etudiant.js"></script>
    <script src="../assets/js/etudiant/notifications_etudiant.js"></script>
</body>
</html>
