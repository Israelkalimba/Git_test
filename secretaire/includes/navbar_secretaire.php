<?php
$secretaire_nom_nav = $_SESSION['user_nom'] ?? 'Secrétaire';
$secretaire_id_nav = $_SESSION['user_id'] ?? 1;

$page_actuelle = basename($_SERVER['PHP_SELF']);
$titres_pages = [
    'dashboard.php' => 'Tableau de Bord',
    'suivi_paiements.php' => 'Suivi des Paiements',
    'validation_paiements.php' => 'Validation Paiements',
    'registre_etudiants.php' => 'Registre Étudiants',
    'rapports_journaliers.php' => 'Rapports Journaliers',
    'anomalies.php' => 'Gestion des Anomalies',
    'profil.php' => 'Mon Profil'
];
$titre_page = $titres_pages[$page_actuelle] ?? 'Tableau de Bord';
?>
<nav class="secretaire-navbar">
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
        <!-- Recherche rapide -->
        <div class="search-box">
            <i class="fas fa-search search-box-icon"></i>
            <input type="text" class="search-input" id="globalSearch" 
                   placeholder="Rechercher un étudiant..." autocomplete="off">
            <div class="search-results" id="searchResults" style="display:none;"></div>
        </div>

        <!-- Notifications -->
        <div class="dropdown-notif">
            <button class="btn-icon-nav" id="btnNotif" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if (($navbar_notif_non_lues ?? 0) > 0): ?>
                    <span class="badge-notif" id="badgeNotif">
                        <?= ($navbar_notif_non_lues ?? 0) > 99 ? '99+' : ($navbar_notif_non_lues ?? 0) ?>
                    </span>
                <?php endif; ?>
            </button>
            <!-- Dropdown notifications -->
            <div class="dropdown-menu-notif" id="menuNotif" style="display:none;">
                <div class="notif-menu-header">
                    <h6>Notifications</h6>
                    <?php if (($navbar_notif_non_lues ?? 0) > 0): ?>
                        <a href="#" class="mark-all-read" id="btnMarkAllRead">
                            <i class="fas fa-check-double"></i> Tout lu
                        </a>
                    <?php endif; ?>
                </div>
                <div class="notif-menu-list" id="notifList">
                    <?php if (empty($navbar_notifications ?? [])): ?>
                        <div class="notif-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Aucune notification</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (($navbar_notifications ?? []) as $notif): ?>
                            <div class="notif-item <?= ($notif['statut'] ?? '') === 'non_lu' ? 'notif-unread' : '' ?>">
                                <div class="notif-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="notif-content">
                                    <p class="notif-text"><?= htmlspecialchars($notif['message'] ?? '') ?></p>
                                    <small class="notif-date">
                                        <?= isset($notif['date_envoi']) ? date('d/m/Y H:i', strtotime($notif['date_envoi'])) : '' ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="notif-menu-footer">
                    <a href="notifications.php">Voir toutes les notifications</a>
                </div>
            </div>
        </div>

        <!-- Profil Utilisateur -->
        <div class="dropdown-user">
            <button class="btn-user-nav" id="btnUser" title="Mon compte">
                <div class="user-avatar-sm">
                    <i class="fas fa-user-tie"></i>
                </div>
                <span class="user-name-nav"><?= htmlspecialchars($secretaire_nom_nav) ?></span>
                <i class="fas fa-chevron-down user-chevron"></i>
            </button>
            <!-- Dropdown menu utilisateur -->
            <div class="dropdown-menu-user" id="menuUser" style="display:none;">
                <div class="user-menu-header">
                    <div class="user-menu-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($secretaire_nom_nav) ?></strong>
                        <small>Secrétaire</small>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profil.php" class="user-menu-link">
                    <i class="fas fa-user-circle"></i> Mon Profil
                </a>
                <div class="dropdown-divider"></div>
                <a href="../logout.php?role=secretaire" class="user-menu-link link-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// Initialisation des dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Toggle menu notifications
    const btnNotif = document.getElementById('btnNotif');
    const menuNotif = document.getElementById('menuNotif');
    const btnUser = document.getElementById('btnUser');
    const menuUser = document.getElementById('menuUser');
    
    if (btnNotif && menuNotif) {
        btnNotif.addEventListener('click', function(e) {
            e.stopPropagation();
            menuNotif.style.display = menuNotif.style.display === 'none' ? 'block' : 'none';
            if (menuUser) menuUser.style.display = 'none';
        });
    }
    
    // Toggle menu utilisateur
    if (btnUser && menuUser) {
        btnUser.addEventListener('click', function(e) {
            e.stopPropagation();
            menuUser.style.display = menuUser.style.display === 'none' ? 'block' : 'none';
            if (menuNotif) menuNotif.style.display = 'none';
        });
    }
    
    // Fermer les menus en cliquant ailleurs
    document.addEventListener('click', function() {
        if (menuNotif) menuNotif.style.display = 'none';
        if (menuUser) menuUser.style.display = 'none';
    });
    
    // Empêcher la fermeture en cliquant dans le menu
    if (menuNotif) {
        menuNotif.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    if (menuUser) {
        menuUser.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Marquer tout comme lu
    const btnMarkAll = document.getElementById('btnMarkAllRead');
    if (btnMarkAll) {
        btnMarkAll.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('../api/admin/mark_notifications_read.php', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('badgeNotif');
                        if (badge) badge.remove();
                        document.querySelectorAll('.notif-unread').forEach(el => el.classList.remove('notif-unread'));
                        btnMarkAll.remove();
                    }
                });
        });
    }
});
</script>