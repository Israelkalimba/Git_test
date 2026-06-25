<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== STATISTIQUES RÉELLES ==========

// Total étudiants
$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant'");
$total_etudiants = $stmt->fetch()['total'] ?? 0;

// Total paiements réussis
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'succes'");
$total_paiements = $stmt->fetch()['total'] ?? 0;

// Paiements aujourd'hui
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE DATE(date_paiement) = CURDATE() AND statut = 'succes'");
$paiements_aujourdhui = $stmt->fetch()['total'] ?? 0;

// Montant total collecté
$stmt = $db->query("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE statut = 'succes'");
$montant_total = $stmt->fetch()['total'] ?? 0;

// Taux de réussite
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements");
$total_transactions = $stmt->fetch()['total'] ?? 1;
$stmt = $db->query("SELECT COUNT(*) as succes FROM paiements WHERE statut = 'succes'");
$transactions_succes = $stmt->fetch()['succes'] ?? 0;
$taux_reussite = $total_transactions > 0 ? round(($transactions_succes / $total_transactions) * 100, 1) : 100;

// Anomalies
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'echec'");
$total_anomalies = $stmt->fetch()['total'] ?? 0;

// Facultés actives
$stmt = $db->query("SELECT COUNT(*) as total FROM facultes");
$facultes_actives = $stmt->fetch()['total'] ?? 0;

// Transactions en attente
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente'");
$transactions_attente = $stmt->fetch()['total'] ?? 0;

// Évolution étudiants ce mois vs mois dernier
$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$etudiants_mois = $stmt->fetch()['total'] ?? 0;
$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant' AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
$etudiants_mois_dernier = $stmt->fetch()['total'] ?? 1;
$evolution_etudiants = $etudiants_mois_dernier > 0 ? round((($etudiants_mois - $etudiants_mois_dernier) / $etudiants_mois_dernier) * 100, 1) : 0;

// Évolution paiements ce mois vs mois dernier
$stmt = $db->query("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE statut = 'succes' AND MONTH(date_paiement) = MONTH(CURRENT_DATE()) AND YEAR(date_paiement) = YEAR(CURRENT_DATE())");
$paiements_mois = $stmt->fetch()['total'] ?? 0;
$stmt = $db->query("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE statut = 'succes' AND MONTH(date_paiement) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(date_paiement) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
$paiements_mois_dernier = $stmt->fetch()['total'] ?? 1;
$evolution_paiements = $paiements_mois_dernier > 0 ? round((($paiements_mois - $paiements_mois_dernier) / $paiements_mois_dernier) * 100, 1) : 0;

// Données graphique mensuel (12 derniers mois)
$mois_labels = [];
$mois_valeurs = [];
for ($i = 11; $i >= 0; $i--) {
    $mois = date('Y-m', strtotime("-{$i} months"));
    $mois_labels[] = date('M Y', strtotime("-{$i} months"));
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE statut = 'succes' AND DATE_FORMAT(date_paiement, '%Y-%m') = :mois");
    $stmt->execute(['mois' => $mois]);
    $mois_valeurs[] = (int)($stmt->fetch()['total'] ?? 0);
}

// Données graphique par faculté
$stmt = $db->query("
    SELECT f.nom_faculte, COUNT(p.id_paiement) as total 
    FROM facultes f 
    LEFT JOIN filieres fi ON f.id_faculte = fi.id_faculte 
    LEFT JOIN etudiants e ON fi.id_filiere = e.id_filiere 
    LEFT JOIN paiements p ON e.id_etudiant = p.id_etudiant AND p.statut = 'succes' 
    GROUP BY f.id_faculte 
    ORDER BY total DESC
");
$facultes_data = $stmt->fetchAll();
$facultes_labels = array_column($facultes_data, 'nom_faculte');
$facultes_valeurs = array_column($facultes_data, 'total');

// Activités récentes (5 dernières)
$stmt = $db->query("
    SELECT p.id_paiement, u.nom as nom_etudiant, f.type_frais, p.montant_paye, p.date_paiement, p.statut, p.reference_transaction
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN frais f ON p.id_frais = f.id_frais 
    ORDER BY p.date_paiement DESC 
    LIMIT 5
");
$activites = $stmt->fetchAll();

// Anomalies détaillées (5 dernières)
$stmt = $db->query("
    SELECT p.id_paiement, u.nom as nom_etudiant, f.type_frais, p.montant_paye, p.date_paiement, p.reference_transaction
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN frais f ON p.id_frais = f.id_frais 
    WHERE p.statut = 'echec' 
    ORDER BY p.date_paiement DESC 
    LIMIT 5
");
$anomalies = $stmt->fetchAll();

// Transactions en attente (10 dernières)
$stmt = $db->query("
    SELECT p.id_paiement, u.nom as nom_etudiant, f.type_frais, p.montant_paye, p.date_paiement, p.reference_transaction
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN frais f ON p.id_frais = f.id_frais 
    WHERE p.statut = 'en_attente' 
    ORDER BY p.date_paiement DESC 
    LIMIT 10
");
$transactions = $stmt->fetchAll();

// Notifications non lues
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

// Dernières notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ISTAM Paiement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- SIDEBAR -->
        <?php include 'includes/sidebar_admin.php'; ?>

        <!-- CONTENU PRINCIPAL -->
        <div class="main-content">
            <!-- NAVBAR -->
            <?php 
            $navbar_notifications = $notifications;
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_admin.php'; 
            ?>

            <!-- DASHBOARD -->
            <main class="dashboard-content">
                <!-- En-tête -->
                <header class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <h1 class="dashboard-title">
                                <i class="fas fa-tachometer-alt"></i> Tableau de Bord
                            </h1>
                            <p class="dashboard-subtitle">
                                Bienvenue, <strong><?= htmlspecialchars($admin_nom) ?></strong> | 
                                <?= date('d/m/Y') ?> - <?= date('H:i') ?>
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-refresh" id="btnRefresh">
                                <i class="fas fa-sync-alt"></i> Actualiser
                            </button>
                            <a href="rapports.php" class="btn btn-report">
                                <i class="fas fa-file-pdf"></i> Générer rapport
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Cartes statistiques -->
                <div class="stats-grid">
                    <!-- Carte Étudiants -->
                    <div class="stat-card stat-card-blue">
                        <div class="stat-card-top">
                            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                            <div class="stat-trend <?= $evolution_etudiants >= 0 ? 'trend-up' : 'trend-down' ?>">
                                <i class="fas fa-arrow-<?= $evolution_etudiants >= 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($evolution_etudiants) ?>%
                            </div>
                        </div>
                        <div class="stat-card-body">
                            <h3 class="stat-value"><?= number_format($total_etudiants, 0, ',', ' ') ?></h3>
                            <p class="stat-label">Étudiants inscrits</p>
                        </div>
                        <div class="stat-card-footer">
                            <a href="gestion_etudiants.php" class="stat-link">Voir la liste <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="stat-card-progress" style="width: 100%"></div>
                    </div>

                    <!-- Carte Paiements -->
                    <div class="stat-card stat-card-green">
                        <div class="stat-card-top">
                            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-plus"></i> +<?= $paiements_aujourdhui ?> auj.
                            </div>
                        </div>
                        <div class="stat-card-body">
                            <h3 class="stat-value"><?= number_format($total_paiements, 0, ',', ' ') ?></h3>
                            <p class="stat-label">Transactions réussies</p>
                        </div>
                        <div class="stat-card-footer">
                            <a href="rapports.php" class="stat-link">Détails <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="stat-card-progress" style="width: 100%"></div>
                    </div>

                    <!-- Carte Montant -->
                    <div class="stat-card stat-card-purple">
                        <div class="stat-card-top">
                            <div class="stat-icon"><i class="fas fa-coins"></i></div>
                            <div class="stat-trend <?= $evolution_paiements >= 0 ? 'trend-up' : 'trend-down' ?>">
                                <i class="fas fa-arrow-<?= $evolution_paiements >= 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($evolution_paiements) ?>%
                            </div>
                        </div>
                        <div class="stat-card-body">
                            <h3 class="stat-value">$<?= number_format($montant_total, 2, ',', ' ') ?></h3>
                            <p class="stat-label">Montant total collecté</p>
                        </div>
                        <div class="stat-card-footer">
                            <span class="stat-period">Toutes périodes confondues</span>
                        </div>
                        <div class="stat-card-progress" style="width: 100%"></div>
                    </div>

                    <!-- Carte Taux -->
                    <div class="stat-card stat-card-orange">
                        <div class="stat-card-top">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-trend <?= $taux_reussite >= 90 ? 'trend-up' : 'trend-down' ?>">
                                <i class="fas fa-percent"></i> Taux
                            </div>
                        </div>
                        <div class="stat-card-body">
                            <h3 class="stat-value"><?= $taux_reussite ?>%</h3>
                            <p class="stat-label">Taux de réussite</p>
                        </div>
                        <div class="stat-card-footer">
                            <div class="stat-progress">
                                <div class="stat-progress-bar <?= $taux_reussite >= 90 ? 'bg-green' : ($taux_reussite >= 70 ? 'bg-orange' : 'bg-red') ?>" 
                                     style="width: <?= $taux_reussite ?>%"></div>
                            </div>
                        </div>
                        <div class="stat-card-progress" style="width: 100%"></div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-chart-line"></i> Paiements Mensuels</h3>
                            <small>12 derniers mois</small>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartMensuel"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-chart-pie"></i> Répartition par Département</h3>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartFacultes"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Activités & Anomalies -->
                <div class="info-grid">
                    <!-- Activités récentes -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <h3><i class="fas fa-history"></i> Activités Récentes</h3>
                            <a href="journal_audit.php" class="see-all">Voir tout <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="info-card-body">
                            <?php if (empty($activites)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>Aucune activité récente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activites as $activite): ?>
                                    <div class="activity-item">
                                        <div class="activity-dot <?= $activite['statut'] === 'succes' ? 'dot-success' : ($activite['statut'] === 'echec' ? 'dot-danger' : 'dot-warning') ?>"></div>
                                        <div class="activity-info">
                                            <p class="activity-name">
                                                <strong><?= htmlspecialchars($activite['nom_etudiant']) ?></strong>
                                                <span class="text-<?= $activite['statut'] === 'succes' ? 'success' : ($activite['statut'] === 'echec' ? 'danger' : 'warning') ?>">
                                                    $<?= number_format($activite['montant_paye'], 2) ?>
                                                </span>
                                            </p>
                                            <small class="activity-detail">
                                                <?= htmlspecialchars($activite['type_frais']) ?> | 
                                                <?= date('d/m/Y H:i', strtotime($activite['date_paiement'])) ?>
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
                            <h3><i class="fas fa-exclamation-triangle"></i> Anomalies</h3>
                            <span class="badge-count badge-danger"><?= $total_anomalies ?></span>
                        </div>
                        <div class="info-card-body">
                            <?php if (empty($anomalies)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <p>Aucune anomalie détectée</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($anomalies as $anomalie): ?>
                                    <div class="anomaly-item">
                                        <div class="anomaly-status status-failed">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                        <div class="anomaly-info">
                                            <p class="anomaly-name"><?= htmlspecialchars($anomalie['nom_etudiant']) ?></p>
                                            <small>
                                                $<?= number_format($anomalie['montant_paye'], 2) ?> | 
                                                Réf: <?= htmlspecialchars(substr($anomalie['reference_transaction'], 0, 12)) ?>
                                            </small>
                                        </div>
                                        <a href="gestion_anomalies.php?id=<?= $anomalie['id_paiement'] ?>" class="btn btn-sm btn-outline-warning">Traiter</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Transactions en attente -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-clock"></i> Transactions en attente de validation</h3>
                        <span class="badge-count badge-warning"><?= $transactions_attente ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Type Frais</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state py-4">
                                                <i class="fas fa-check-circle text-success"></i>
                                                <p>Aucune transaction en attente</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $tr): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars(substr($tr['reference_transaction'], 0, 15)) ?></code></td>
                                            <td><?= htmlspecialchars($tr['nom_etudiant']) ?></td>
                                            <td><?= htmlspecialchars($tr['type_frais'] ?? 'N/A') ?></td>
                                            <td><strong>$<?= number_format($tr['montant_paye'], 2) ?></strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($tr['date_paiement'])) ?></td>
                                            <td><span class="status-pill pill-warning">En attente</span></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="valider_transaction.php?id=<?= $tr['id_paiement'] ?>" class="btn btn-success" title="Valider">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="rejeter_transaction.php?id=<?= $tr['id_paiement'] ?>" class="btn btn-danger" title="Rejeter">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            </td>
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

    <!-- Données pour les graphiques -->
    <script>
    const chartData = {
        moisLabels: <?= json_encode($mois_labels) ?>,
        moisValeurs: <?= json_encode($mois_valeurs) ?>,
        facultesLabels: <?= json_encode($facultes_labels) ?>,
        facultesValeurs: <?= json_encode(array_map('intval', $facultes_valeurs)) ?>,
        notificationsNonLues: <?= $notifications_non_lues ?>,
        adminId: <?= $admin_id ?>
    };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
</body>
</html>