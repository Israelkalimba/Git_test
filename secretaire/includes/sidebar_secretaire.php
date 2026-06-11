<?php
$current = basename($_SERVER['PHP_SELF']);
$secretaire_nom = $_SESSION['user_nom'] ?? 'Secrétaire';
?>
<aside class="secretaire-sidebar" id="secretaireSidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="brand-link">
            <img src="../assets/images/logo-istam.png" alt="ISTAM" class="brand-logo">
            <span class="brand-text">Secrétariat</span>
        </a>
        <button class="btn-sidebar-toggle" id="btnSidebarToggle" title="Menu">
            <i class="fas fa-outdent"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="sidebar-user-info">
            <h5><?= htmlspecialchars($secretaire_nom) ?></h5>
            <span>Secrétaire</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <ul class="menu-list">
            <li class="menu-item">
                <a href="dashboard.php" class="menu-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de Bord</span>
                </a>
            </li>

            <li class="menu-label"><span>OPÉRATIONS</span></li>
            
            <li class="menu-item">
                <a href="suivi_paiements.php" class="menu-link <?= $current === 'suivi_paiements.php' ? 'active' : '' ?>">
                    <i class="fas fa-search"></i>
                    <span>Suivi Paiements</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="validation_paiements.php" class="menu-link <?= $current === 'validation_paiements.php' ? 'active' : '' ?>">
                    <i class="fas fa-check-double"></i>
                    <span>Validation</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="registre_etudiants.php" class="menu-link <?= $current === 'registre_etudiants.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Registre Étudiants</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="voir_configuration_frais.php" class="menu-link <?= $current === 'voir_configuration_frais.php' ? 'active' : '' ?>">
                    <i class="fas fa-list-alt"></i>
                    <span>Consulter les Frais</span>
                </a>
            </li>

            <li class="menu-label"><span>RAPPORTS</span></li>
            
            <li class="menu-item">
                <a href="rapports_journaliers.php" class="menu-link <?= $current === 'rapports_journaliers.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Rapports Journaliers</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="anomalies.php" class="menu-link <?= $current === 'anomalies.php' ? 'active' : '' ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Anomalies</span>
                </a>
            </li>

            <li class="menu-label"><span>COMPTE</span></li>
            
            <li class="menu-item">
                <a href="profil.php" class="menu-link <?= $current === 'profil.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>Mon Profil</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="system-status">
            <span class="indicator online"></span>
            <span>Système opérationnel</span>
        </div>
        <a href="../logout.php?role=secretaire" class="btn-logout-side">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>