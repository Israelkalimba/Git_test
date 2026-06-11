<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('secretaire');

$db = Database::getInstance();
$secretaire_nom = $_SESSION['user_nom'] ?? 'Secrétaire';
$secretaire_id = $_SESSION['user_id'] ?? 1;

// ========== STATISTIQUES RÉELLES ==========

// Paiements du jour
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE DATE(date_paiement) = CURDATE() AND statut = 'succes'");
$paiements_jour = $stmt->fetch()['total'] ?? 0;

// Paiements en attente
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente'");
$paiements_attente = $stmt->fetch()['total'] ?? 0;

// Anomalies
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'echec'");
$anomalies = $stmt->fetch()['total'] ?? 0;

// Total étudiants
$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant'");
$total_etudiants = $stmt->fetch()['total'] ?? 0;

// Nouveaux étudiants cette semaine
$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$nouveaux_etudiants = $stmt->fetch()['total'] ?? 0;

// Total paiements réussis
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'succes'");
$total_paiements = $stmt->fetch()['total'] ?? 0;

// Montant collecté aujourd'hui
$stmt = $db->query("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE DATE(date_paiement) = CURDATE() AND statut = 'succes'");
$montant_jour = $stmt->fetch()['total'] ?? 0;

// ========== PAIEMENTS RÉCENTS ==========
$stmt = $db->query("
    SELECT p.*, u.nom as nom_etudiant, e.matricule, f.type_frais,
           fi.nom_filiere, pr.nom_promotion
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN frais f ON p.id_frais = f.id_frais 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN promotions pr ON e.id_promotion = pr.id_promotion 
    ORDER BY p.date_paiement DESC 
    LIMIT 10
");
$paiements_recents = $stmt->fetchAll();

// ========== ANOMALIES RÉCENTES ==========
$stmt = $db->query("
    SELECT p.*, u.nom as nom_etudiant, e.matricule
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    WHERE p.statut = 'echec' 
    ORDER BY p.date_paiement DESC 
    LIMIT 5
");
$anomalies_recentes = $stmt->fetchAll();

// ========== ÉTUDIANTS RÉCEMMENT INSCRITS ==========
$stmt = $db->query("
    SELECT u.nom, u.email, e.matricule, u.created_at, fi.nom_filiere, pr.nom_promotion
    FROM utilisateurs u 
    JOIN etudiants e ON u.id_utilisateur = e.id_utilisateur 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN promotions pr ON e.id_promotion = pr.id_promotion 
    WHERE u.role = 'etudiant' 
    ORDER BY u.created_at DESC 
    LIMIT 10
");
$nouveaux_inscrits = $stmt->fetchAll();

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
    <title>Dashboard Secrétaire - ISTAM Paiement</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/secretaire/dashboard_secretaire.css">
</head>
<body>
    <div class="secretaire-layout">
        <!-- SIDEBAR -->
        <?php include 'includes/sidebar_secretaire.php'; ?>

        <!-- CONTENU PRINCIPAL -->
        <div class="main-content">
            <!-- NAVBAR -->
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_secretaire.php'; 
            ?>

            <!-- DASHBOARD -->
            <main class="dashboard-content">
                <!-- En-tête -->
                <header class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <h1 class="dashboard-title">
                                <i class="fas fa-tachometer-alt"></i> Tableau de Bord Secrétaire
                            </h1>
                            <p class="dashboard-subtitle">
                                Bienvenue, <strong><?= htmlspecialchars($secretaire_nom) ?></strong> | 
                                <?= date('d/m/Y') ?> - <?= date('H:i') ?>
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <a href="suivi_paiements.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Consulter les paiements
                            </a>
                            <a href="registre_etudiants.php" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-users"></i> Registre étudiants
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Cartes statistiques -->
                <div class="stats-grid">
                    <div class="stat-card stat-card-green">
                        <div class="stat-card-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-card-body">
                            <h3><?= $paiements_jour ?></h3>
                            <p>Paiements aujourd'hui</p>
                        </div>
                    </div>
                    <div class="stat-card stat-card-orange">
                        <div class="stat-card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-card-body">
                            <h3><?= $paiements_attente ?></h3>
                            <p>En attente</p>
                        </div>
                    </div>
                    <div class="stat-card stat-card-red">
                        <div class="stat-card-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-card-body">
                            <h3><?= $anomalies ?></h3>
                            <p>Anomalies</p>
                        </div>
                    </div>
                    <div class="stat-card stat-card-blue">
                        <div class="stat-card-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-card-body">
                            <h3><?= number_format($total_etudiants) ?></h3>
                            <p>Étudiants</p>
                        </div>
                    </div>
                </div>

                <!-- Deuxième ligne stats -->
                <div class="stats-grid-3">
                    <div class="stat-card stat-card-teal">
                        <div class="stat-card-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-card-body">
                            <h3><?= $nouveaux_etudiants ?></h3>
                            <p>Nouveaux cette semaine</p>
                        </div>
                    </div>
                    <div class="stat-card stat-card-purple">
                        <div class="stat-card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-card-body">
                            <h3>$<?= number_format($montant_jour, 2) ?></h3>
                            <p>Collecté aujourd'hui</p>
                        </div>
                    </div>
                    <div class="stat-card stat-card-indigo">
                        <div class="stat-card-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stat-card-body">
                            <h3><?= number_format($total_paiements) ?></h3>
                            <p>Total transactions</p>
                        </div>
                    </div>
                </div>

                <!-- Paiements récents & Anomalies -->
                <div class="info-grid">
                    <!-- Paiements récents -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <h3><i class="fas fa-history"></i> Paiements Récents</h3>
                            <a href="suivi_paiements.php" class="see-all">Voir tout <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="info-card-body">
                            <?php if (empty($paiements_recents)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>Aucun paiement récent</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($paiements_recents as $paiement): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon activity-payment">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p class="activity-text">
                                                <strong><?= htmlspecialchars($paiement['nom_etudiant']) ?></strong>
                                                <small class="text-muted">(<?= htmlspecialchars($paiement['matricule']) ?>)</small>
                                            </p>
                                            <p class="activity-detail">
                                                <?= htmlspecialchars($paiement['type_frais']) ?> | 
                                                <span class="text-success fw-bold">$<?= number_format($paiement['montant_paye'], 2) ?></span> |
                                                <span class="badge-status status-<?= $paiement['statut'] ?>">
                                                    <?= $paiement['statut'] === 'succes' ? 'Réussi' : ($paiement['statut'] === 'echec' ? 'Échec' : 'En attente') ?>
                                                </span>
                                            </p>
                                            <small class="text-muted">
                                                <i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($paiement['date_paiement'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Anomalies -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Anomalies Récentes</h3>
                            <span class="badge-count badge-danger"><?= $anomalies ?></span>
                        </div>
                        <div class="info-card-body">
                            <?php if (empty($anomalies_recentes)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <p>Aucune anomalie</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($anomalies_recentes as $anomalie): ?>
                                    <div class="anomaly-item">
                                        <div class="anomaly-icon">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                        <div class="anomaly-content">
                                            <p class="anomaly-text">
                                                <strong><?= htmlspecialchars($anomalie['nom_etudiant']) ?></strong>
                                            </p>
                                            <p class="anomaly-detail">
                                                Matricule: <?= htmlspecialchars($anomalie['matricule']) ?> | 
                                                $<?= number_format($anomalie['montant_paye'], 2) ?>
                                            </p>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($anomalie['date_paiement'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Nouveaux inscrits -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-user-plus"></i> Étudiants Récemment Inscrits</h3>
                        <a href="registre_etudiants.php" class="see-all">Tout le registre <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Filière</th>
                                    <th>Promotion</th>
                                    <th>Date inscription</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($nouveaux_inscrits)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-3">Aucune inscription récente</td></tr>
                                <?php else: ?>
                                    <?php foreach ($nouveaux_inscrits as $inscrit): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($inscrit['matricule']) ?></code></td>
                                            <td><strong><?= htmlspecialchars($inscrit['nom']) ?></strong></td>
                                            <td><small><?= htmlspecialchars($inscrit['email']) ?></small></td>
                                            <td><small><?= htmlspecialchars($inscrit['nom_filiere']) ?></small></td>
                                            <td><small><?= htmlspecialchars($inscrit['nom_promotion']) ?></small></td>
                                            <td><small><?= date('d/m/Y', strtotime($inscrit['created_at'])) ?></small></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/secretaire/dashboard_secretaire.js"></script>
</body>
</html>