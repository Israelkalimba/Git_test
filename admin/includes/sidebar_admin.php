<?php
$current = basename($_SERVER['PHP_SELF']);
$admin_nom_side = $_SESSION['user_nom'] ?? 'Admin';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="dashboard.php" class="brand-link">
            <img src="../assets/images/logo-istam.png" alt="ISTAM" class="brand-logo">
            <span class="brand-text">Admin Panel</span>
        </a>
        <button class="btn-sidebar-toggle" id="btnSidebarToggle" title="Menu">
            <i class="fas fa-outdent"></i>
        </button>
    </div>

    <!-- Profil -->
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="sidebar-user-info">
            <h5><?= htmlspecialchars($admin_nom_side) ?></h5>
            <span>Administrateur</span>
        </div>
    </div>

    <!-- Menu -->
    <nav class="sidebar-menu">
        <ul class="menu-list">
            <!-- ========== DASHBOARD ========== -->
            <li class="menu-item">
                <a href="dashboard.php" class="menu-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de Bord</span>
                    <?php if ($current === 'dashboard.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- ========== STRUCTURE ACADÉMIQUE ========== -->
            <li class="menu-label"><span>STRUCTURE ACADÉMIQUE</span></li>
            
            <li class="menu-item">
                <a href="gestion_facultes.php" class="menu-link <?= $current === 'gestion_facultes.php' ? 'active' : '' ?>">
                    <i class="fas fa-university"></i>
                    <span>Département</span>
                    <?php if ($current === 'gestion_facultes.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="gestion_filieres.php" class="menu-link <?= $current === 'gestion_filieres.php' ? 'active' : '' ?>">
                    <i class="fas fa-layer-group"></i>
                    <span>Filières</span>
                    <?php if ($current === 'gestion_filieres.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="gestion_promotions.php" class="menu-link <?= $current === 'gestion_promotions.php' ? 'active' : '' ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Promotions</span>
                    <?php if ($current === 'gestion_promotions.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- ========== GESTION UTILISATEURS ========== -->
            <li class="menu-label"><span>UTILISATEURS</span></li>
            
            <li class="menu-item">
                <a href="gestion_etudiants.php" class="menu-link <?= $current === 'gestion_etudiants.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Étudiants</span>
                    <?php if ($current === 'gestion_etudiants.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="gestion_utilisateurs.php" class="menu-link <?= $current === 'gestion_utilisateurs.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Personnel</span>
                    <small class="menu-badge">Admin & Secrétaires</small>
                    <?php if ($current === 'gestion_utilisateurs.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- ========== FINANCES ========== -->
            <li class="menu-label"><span>FINANCES</span></li>
            
            <li class="menu-item">
                <a href="configuration_frais.php" class="menu-link <?= $current === 'configuration_frais.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i>
                    <span>Frais Académiques</span>
                    <?php if ($current === 'configuration_frais.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="rapports.php" class="menu-link <?= $current === 'rapports.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Rapports</span>
                    <?php if ($current === 'rapports.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="statistiques_avancees.php" class="menu-link <?= $current === 'statistiques_avancees.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Statistiques</span>
                    <?php if ($current === 'statistiques_avancees.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="journal_audit.php" class="menu-link <?= $current === 'journal_audit.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    <span>Journal d'Audit</span>
                    <?php if ($current === 'journal_audit.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- ========== SYSTÈME ========== -->
            <li class="menu-label"><span>SYSTÈME</span></li>
            
            <li class="menu-item">
                <a href="notifications.php" class="menu-link <?= $current === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php if ($current === 'notifications.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="api_paiement.php" class="menu-link <?= $current === 'api_paiement.php' ? 'active' : '' ?>">
                    <i class="fas fa-plug"></i>
                    <span>API Paiement</span>
                    <?php if ($current === 'api_paiement.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="gestion_anomalies.php" class="menu-link <?= $current === 'gestion_anomalies.php' ? 'active' : '' ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Anomalies</span>
                    <?php if ($current === 'gestion_anomalies.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="parametres.php" class="menu-link <?= $current === 'parametres.php' ? 'active' : '' ?>">
                    <i class="fas fa-sliders-h"></i>
                    <span>Paramètres</span>
                    <?php if ($current === 'parametres.php'): ?>
                        <span class="active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="system-status">
            <span class="indicator online"></span>
            <span>Système opérationnel</span>
        </div>
        <a href="../logout.php?role=admin" class="btn-logout-side">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>