<?php
$admin_nom_nav = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id_nav = $_SESSION['user_id'] ?? 1;

// Notifications non lues
$notif_count = $navbar_notif_non_lues ?? 0;
$notifications_list = $navbar_notifications ?? [];

$page_actuelle = basename($_SERVER['PHP_SELF']);
$titres_pages = [
    'dashboard.php' => 'Tableau de Bord',
    'gestion_etudiants.php' => 'Gestion des Étudiants',
    'gestion_utilisateurs.php' => 'Gestion des Utilisateurs',
    'configuration_frais.php' => 'Configuration des Frais',
    'rapports.php' => 'Rapports',
    'statistiques_avancees.php' => 'Statistiques Avancées',
    'journal_audit.php' => 'Journal d\'Audit',
    'parametres.php' => 'Paramètres',
    'api_paiement.php' => 'API Paiement',
    'notifications.php' => 'Notifications',
    'profil.php' => 'Mon Profil',
    'gestion_anomalies.php' => 'Gestion des Anomalies'
];
$titre_page = $titres_pages[$page_actuelle] ?? 'Tableau de Bord';
?>
<nav class="admin-navbar">
    <div class="navbar-left">
        <button class="btn-sidebar-mobile" id="sidebarMobileToggle" title="Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="navbar-breadcrumb">
            <a href="dashboard.php" class="breadcrumb-link" title="Accueil">
                <i class="fas fa-home"></i>
            </a>
            <i class="fas fa-chevron-right breadcrumb-arrow"></i>
            <span class="breadcrumb-current"><?= $titre_page ?></span>
        </div>
    </div>

    <div class="navbar-right">
        <!-- Recherche -->
        <div class="search-box">
            <i class="fas fa-search search-box-icon"></i>
            <input type="text" class="search-input" id="globalSearch" placeholder="Rechercher..." autocomplete="off">
            <kbd class="search-kbd">Ctrl+K</kbd>
            <div class="search-results" id="searchResults"></div>
        </div>

        <!-- Notifications -->
        <div class="dropdown-notif">
            <button class="btn-icon-nav" id="btnNotif" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="badge-notif" id="badgeNotif"><?= $notif_count > 99 ? '99+' : $notif_count ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu-notif" id="menuNotif">
                <div class="notif-menu-header">
                    <h6>Notifications</h6>
                    <?php if ($notif_count > 0): ?>
                        <button class="btn-mark-all" id="btnMarkAllRead">
                            <i class="fas fa-check-double"></i> Tout lu
                        </button>
                    <?php endif; ?>
                </div>
                <div class="notif-menu-list">
                    <?php if (empty($notifications_list)): ?>
                        <div class="notif-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Aucune notification</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications_list as $n): ?>
                            <?php
                            $msg = strtolower($n['message']);
                            if (strpos($msg, 'succès') !== false || strpos($msg, 'réussi') !== false) {
                                $n_type = 'success'; $n_icon = 'fa-check-circle';
                            } elseif (strpos($msg, 'échec') !== false || strpos($msg, 'erreur') !== false) {
                                $n_type = 'warning'; $n_icon = 'fa-exclamation-triangle';
                            } elseif (strpos($msg, 'paiement') !== false) {
                                $n_type = 'info'; $n_icon = 'fa-credit-card';
                            } else {
                                $n_type = 'info'; $n_icon = 'fa-info-circle';
                            }
                            ?>
                            <div class="notif-item <?= $n['statut'] === 'non_lu' ? 'notif-unread' : '' ?>" 
                                 data-notif-id="<?= $n['id_notification'] ?>">
                                <div class="notif-icon notif-icon-<?= $n_type ?>">
                                    <i class="fas <?= $n_icon ?>"></i>
                                </div>
                                <div class="notif-content">
                                    <p class="notif-text"><?= htmlspecialchars($n['message']) ?></p>
                                    <small class="notif-date"><?= date('d/m/Y H:i', strtotime($n['date_envoi'])) ?></small>
                                </div>
                                <?php if ($n['statut'] === 'non_lu'): ?>
                                    <span class="notif-dot"></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="notif-menu-footer">
                    <a href="notifications.php">Voir toutes les notifications <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Profil -->
        <div class="dropdown-user">
            <button class="btn-user-nav" id="btnUser" title="Mon compte">
                <div class="user-avatar-sm">
                    <i class="fas fa-user-shield"></i>
                </div>
                <span class="user-name-nav"><?= htmlspecialchars($admin_nom_nav) ?></span>
                <i class="fas fa-chevron-down user-chevron"></i>
            </button>
            <div class="dropdown-menu-user" id="menuUser">
                <div class="user-menu-header">
                    <div class="user-avatar-lg">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($admin_nom_nav) ?></strong>
                        <small>Super Administrateur</small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profil.php" class="user-menu-link"><i class="fas fa-user-circle"></i> Mon Profil</a>
                <a href="parametres.php" class="user-menu-link"><i class="fas fa-cog"></i> Paramètres</a>
                <a href="journal_audit.php" class="user-menu-link"><i class="fas fa-history"></i> Journal d'Activité</a>
                <div class="dropdown-divider"></div>
                <a href="../logout.php?role=admin" class="user-menu-link link-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>

        <!-- Thème -->
        <button class="btn-icon-nav" id="btnTheme" title="Changer le thème">
            <i class="fas fa-moon"></i>
        </button>
    </div>
</nav>