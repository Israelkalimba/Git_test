<?php
$current = basename($_SERVER['PHP_SELF']);
$etudiant_nom_side = $_SESSION['user_nom'] ?? 'Étudiant';
?>
<aside class="etudiant-sidebar" id="etudiantSidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="dashboard.php" class="brand-link">
            <img src="../assets/images/logo-istam.png" alt="ISTAM" class="brand-logo">
            <span class="brand-text">Espace Étudiant</span>
        </a>
        <button class="btn-sidebar-toggle" id="btnSidebarToggle" title="Réduire/Agrandir">
            <i class="fas fa-outdent"></i>
        </button>
    </div>

    <!-- Profil -->
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="sidebar-user-info">
            <h5><?= htmlspecialchars($etudiant_nom_side) ?></h5>
            <span>Étudiant</span>
        </div>
    </div>

    <!-- Menu -->
    <nav class="sidebar-menu">
        <ul class="menu-list">
            <!-- Dashboard -->
            <li class="menu-item">
                <a href="dashboard.php" class="menu-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de Bord</span>
                </a>
            </li>

            <li class="menu-label"><span>PAIEMENTS</span></li>
            
            <li class="menu-item">
                <a href="payer_frais.php" class="menu-link <?= $current === 'payer_frais.php' ? 'active' : '' ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Payer mes frais</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="historique_paiements.php" class="menu-link <?= $current === 'historique_paiements.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    <span>Historique</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="mes_recus.php" class="menu-link <?= $current === 'mes_recus.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-pdf"></i>
                    <span>Mes Reçus</span>
                </a>
            </li>

            <li class="menu-label"><span>COMPTE</span></li>
            
            <li class="menu-item">
                <a href="profil.php" class="menu-link <?= $current === 'profil.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>Mon Profil</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="notifications.php" class="menu-link <?= $current === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php
                    // Badge notifications non lues
                    $db_side = Database::getInstance();
                    $stmt_side = $db_side->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
                    $stmt_side->execute(['id' => $_SESSION['user_id'] ?? 1]);
                    $nb_notif_side = $stmt_side->fetch()['total'] ?? 0;
                    if ($nb_notif_side > 0):
                    ?>
                        <span class="menu-badge"><?= $nb_notif_side > 99 ? '99+' : $nb_notif_side ?></span>
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
        <a href="../logout.php?role=etudiant" class="btn-logout-side">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>