<?php
$etudiant_nom_nav = $_SESSION['user_nom'] ?? 'Étudiant';
$etudiant_id_nav = $_SESSION['user_id'] ?? 1;

// Notifications pour la navbar
$db_nav = Database::getInstance();
$stmt_nav = $db_nav->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt_nav->execute(['id' => $etudiant_id_nav]);
$navbar_notif_non_lues = $stmt_nav->fetch()['total'] ?? 0;

$stmt_nav = $db_nav->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt_nav->execute(['id' => $etudiant_id_nav]);
$navbar_notifications = $stmt_nav->fetchAll();

$page_actuelle = basename($_SERVER['PHP_SELF']);
$titres_pages = [
    'dashboard.php' => 'Tableau de Bord',
    'payer_frais.php' => 'Payer mes frais',
    'historique_paiements.php' => 'Historique des Paiements',
    'mes_recus.php' => 'Mes Reçus',
    'profil.php' => 'Mon Profil',
    'notifications.php' => 'Notifications'
];
$titre_page = $titres_pages[$page_actuelle] ?? 'Tableau de Bord';
?>
<nav class="etudiant-navbar">
    <!-- ========== PARTIE GAUCHE ========== -->
    <div class="navbar-left">
        <!-- Bouton menu mobile -->
        <button class="btn-sidebar-mobile" id="sidebarMobileToggle" title="Menu">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Fil d'Ariane -->
        <div class="navbar-breadcrumb">
            <a href="dashboard.php" class="breadcrumb-link" title="Accueil">
                <i class="fas fa-home"></i>
            </a>
            <i class="fas fa-chevron-right breadcrumb-arrow"></i>
            <span class="breadcrumb-current"><?= $titre_page ?></span>
        </div>
    </div>

    <!-- ========== PARTIE DROITE ========== -->
    <div class="navbar-right">
        <!-- Notifications -->
        <div class="dropdown-notif">
            <button class="btn-icon-nav" id="btnNotif" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($navbar_notif_non_lues > 0): ?>
                    <span class="badge-notif" id="badgeNotif">
                        <?= $navbar_notif_non_lues > 99 ? '99+' : $navbar_notif_non_lues ?>
                    </span>
                <?php endif; ?>
            </button>
            <!-- Dropdown notifications -->
            <div class="dropdown-menu-notif" id="menuNotif" style="display:none;">
                <div class="notif-menu-header">
                    <h6><i class="fas fa-bell"></i> Notifications</h6>
                    <?php if ($navbar_notif_non_lues > 0): ?>
                        <a href="notifications.php?action=mark_all_read" class="mark-all-read">
                            <i class="fas fa-check-double"></i> Tout lu
                        </a>
                    <?php endif; ?>
                </div>
                <div class="notif-menu-list" id="notifList">
                    <?php if (empty($navbar_notifications)): ?>
                        <div class="notif-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Aucune notification</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($navbar_notifications as $notif): 
                            $msg = strtolower($notif['message'] ?? '');
                            if (strpos($msg, 'paiement') !== false || strpos($msg, 'succès') !== false) {
                                $n_icon = 'fa-check-circle'; $n_color = 'icon-green';
                            } elseif (strpos($msg, 'échec') !== false || strpos($msg, 'erreur') !== false) {
                                $n_icon = 'fa-exclamation-triangle'; $n_color = 'icon-red';
                            } elseif (strpos($msg, 'frais') !== false) {
                                $n_icon = 'fa-credit-card'; $n_color = 'icon-blue';
                            } else {
                                $n_icon = 'fa-info-circle'; $n_color = 'icon-blue';
                            }
                        ?>
                            <a href="notifications.php" class="notif-item <?= ($notif['statut'] ?? '') === 'non_lu' ? 'notif-unread' : '' ?>">
                                <div class="notif-icon <?= $n_color ?>">
                                    <i class="fas <?= $n_icon ?>"></i>
                                </div>
                                <div class="notif-content">
                                    <p class="notif-text"><?= htmlspecialchars($notif['message'] ?? '') ?></p>
                                    <small class="notif-date">
                                        <i class="far fa-clock"></i> 
                                        <?= isset($notif['date_envoi']) ? date('d/m/Y H:i', strtotime($notif['date_envoi'])) : '' ?>
                                    </small>
                                </div>
                                <?php if (($notif['statut'] ?? '') === 'non_lu'): ?>
                                    <span class="notif-dot"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="notif-menu-footer">
                    <a href="notifications.php">
                        <i class="fas fa-arrow-right"></i> Voir toutes les notifications
                    </a>
                </div>
            </div>
        </div>

        <!-- Profil Utilisateur -->
        <div class="dropdown-user">
            <button class="btn-user-nav" id="btnUser" title="Mon compte">
                <div class="user-avatar-sm">
                    <?= strtoupper(substr($etudiant_nom_nav, 0, 2)) ?>
                </div>
                <span class="user-name-nav"><?= htmlspecialchars($etudiant_nom_nav) ?></span>
                <i class="fas fa-chevron-down user-chevron"></i>
            </button>
            <!-- Dropdown menu utilisateur -->
            <div class="dropdown-menu-user" id="menuUser" style="display:none;">
                <div class="user-menu-header">
                    <div class="user-menu-avatar">
                        <?= strtoupper(substr($etudiant_nom_nav, 0, 2)) ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($etudiant_nom_nav) ?></strong>
                        <small>Étudiant</small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profil.php" class="user-menu-link">
                    <i class="fas fa-user-circle"></i> Mon Profil
                </a>
                <a href="notifications.php" class="user-menu-link">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($navbar_notif_non_lues > 0): ?>
                        <span class="menu-notif-badge"><?= $navbar_notif_non_lues ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-divider"></div>
                <a href="../logout.php?role=etudiant" class="user-menu-link link-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>

        <!-- Toggle Thème -->
        <button class="btn-icon-nav" id="btnTheme" title="Changer le thème">
            <i class="fas fa-moon"></i>
        </button>
    </div>
</nav>

<style>
/* Styles navbar spécifiques */
.navbar-right { position: relative; }
.dropdown-notif, .dropdown-user { position: relative; }

.menu-badge {
    background: var(--danger); color: white;
    padding: 2px 7px; border-radius: 50px;
    font-size: 0.65rem; font-weight: 700;
    margin-left: auto;
}
.menu-notif-badge {
    background: var(--danger); color: white;
    padding: 2px 8px; border-radius: 50px;
    font-size: 0.65rem; font-weight: 700;
    margin-left: auto;
}
.icon-green { background: rgba(16,185,129,0.1) !important; color: #10b981 !important; }
.icon-red { background: rgba(239,68,68,0.1) !important; color: #ef4444 !important; }
.icon-blue { background: rgba(59,130,246,0.1) !important; color: #3b82f6 !important; }
.notif-dot {
    width: 8px; height: 8px; min-width: 8px;
    background: var(--primary); border-radius: 50%;
    margin-top: 6px; animation: pulse 2s infinite;
}
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === MENU UTILISATEUR ===
    const btnUser = document.getElementById('btnUser');
    const menuUser = document.getElementById('menuUser');
    
    if (btnUser && menuUser) {
        btnUser.addEventListener('click', function(e) {
            e.stopPropagation();
            menuUser.style.display = menuUser.style.display === 'none' ? 'block' : 'none';
            const menuNotif = document.getElementById('menuNotif');
            if (menuNotif) menuNotif.style.display = 'none';
        });
    }
    
    // === MENU NOTIFICATIONS ===
    const btnNotif = document.getElementById('btnNotif');
    const menuNotif = document.getElementById('menuNotif');
    
    if (btnNotif && menuNotif) {
        btnNotif.addEventListener('click', function(e) {
            e.stopPropagation();
            menuNotif.style.display = menuNotif.style.display === 'none' ? 'block' : 'none';
            if (menuUser) menuUser.style.display = 'none';
        });
    }
    
    // === FERMER EN CLIQUANT AILLEURS ===
    document.addEventListener('click', function() {
        if (menuUser) menuUser.style.display = 'none';
        if (menuNotif) menuNotif.style.display = 'none';
    });
    
    // === EMPÊCHER FERMETURE EN CLIQUANT DANS LE MENU ===
    if (menuUser) {
        menuUser.addEventListener('click', function(e) { e.stopPropagation(); });
    }
    if (menuNotif) {
        menuNotif.addEventListener('click', function(e) { e.stopPropagation(); });
    }
    
    // === THÈME ===
    const btnTheme = document.getElementById('btnTheme');
    if (btnTheme) {
        const savedTheme = localStorage.getItem('istam_etu_theme') || 'light';
        applyTheme(savedTheme, btnTheme);
        
        btnTheme.addEventListener('click', function() {
            const current = document.body.classList.contains('dark') ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next, btnTheme);
            localStorage.setItem('istam_etu_theme', next);
        });
    }
});

function applyTheme(theme, btn) {
    if (theme === 'dark') {
        document.body.classList.add('dark');
        document.documentElement.style.setProperty('--bg-body', '#0f172a');
        document.documentElement.style.setProperty('--bg-card', '#1e293b');
        document.documentElement.style.setProperty('--bg-navbar', '#1e293b');
        document.documentElement.style.setProperty('--text-primary', '#f1f5f9');
        document.documentElement.style.setProperty('--text-secondary', '#94a3b8');
        document.documentElement.style.setProperty('--border-color', '#334155');
        if (btn) {
            btn.querySelector('i').className = 'fas fa-sun';
            btn.querySelector('i').style.color = '#f59e0b';
        }
    } else {
        document.body.classList.remove('dark');
        document.documentElement.style.removeProperty('--bg-body');
        document.documentElement.style.removeProperty('--bg-card');
        document.documentElement.style.removeProperty('--bg-navbar');
        document.documentElement.style.removeProperty('--text-primary');
        document.documentElement.style.removeProperty('--text-secondary');
        document.documentElement.style.removeProperty('--border-color');
        if (btn) {
            btn.querySelector('i').className = 'fas fa-moon';
            btn.querySelector('i').style.color = '';
        }
    }
}
</script>