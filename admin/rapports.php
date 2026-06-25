<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== FILTRES ==========
$filtre_faculte = isset($_GET['faculte']) ? (int)$_GET['faculte'] : 0;
$filtre_filiere = isset($_GET['filiere']) ? (int)$_GET['filiere'] : 0;
$filtre_promotion = isset($_GET['promotion']) ? (int)$_GET['promotion'] : 0;
$filtre_annee = $_GET['annee'] ?? date('Y');
$filtre_mois = $_GET['mois'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';

// ========== RÉCUPÉRATION DES DONNÉES DE BASE ==========

// Facultés
$stmt = $db->query("SELECT * FROM facultes ORDER BY nom_faculte");
$facultes = $stmt->fetchAll();

// Filières
if ($filtre_faculte > 0) {
    $stmt = $db->prepare("SELECT * FROM filieres WHERE id_faculte = :id_faculte ORDER BY nom_filiere");
    $stmt->execute(['id_faculte' => $filtre_faculte]);
} else {
    $stmt = $db->query("SELECT fi.*, fa.nom_faculte FROM filieres fi JOIN facultes fa ON fi.id_faculte = fa.id_faculte ORDER BY fa.nom_faculte, fi.nom_filiere");
}
$filieres = $stmt->fetchAll();

// Promotions
$stmt = $db->query("SELECT * FROM promotions ORDER BY id_promotion");
$promotions = $stmt->fetchAll();

// Années disponibles
$stmt = $db->query("SELECT DISTINCT YEAR(date_paiement) as annee FROM paiements ORDER BY annee DESC");
$annees_disponibles = $stmt->fetchAll();
if (empty($annees_disponibles)) {
    $annees_disponibles = [['annee' => date('Y')]];
}

// ========== STATISTIQUES GLOBALES ==========

// Construction de la clause WHERE pour les filtres
$where = "WHERE 1=1";
$params_where = [];

if ($filtre_faculte > 0) {
    $where .= " AND fa.id_faculte = :faculte";
    $params_where['faculte'] = $filtre_faculte;
}
if ($filtre_filiere > 0) {
    $where .= " AND fi.id_filiere = :filiere";
    $params_where['filiere'] = $filtre_filiere;
}
if ($filtre_promotion > 0) {
    $where .= " AND e.id_promotion = :promotion";
    $params_where['promotion'] = $filtre_promotion;
}
if (!empty($filtre_annee)) {
    $where .= " AND YEAR(p.date_paiement) = :annee";
    $params_where['annee'] = $filtre_annee;
}
if (!empty($filtre_mois)) {
    $where .= " AND MONTH(p.date_paiement) = :mois";
    $params_where['mois'] = $filtre_mois;
}
if (!empty($filtre_statut)) {
    $where .= " AND p.statut = :statut";
    $params_where['statut'] = $filtre_statut;
}

// 1. Total des paiements
$sql = "SELECT 
            COUNT(p.id_paiement) as total_transactions,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total_collecte,
            COUNT(CASE WHEN p.statut = 'succes' THEN 1 END) as total_succes,
            COUNT(CASE WHEN p.statut = 'echec' THEN 1 END) as total_echec,
            COUNT(CASE WHEN p.statut = 'en_attente' THEN 1 END) as total_attente,
            COALESCE(AVG(CASE WHEN p.statut = 'succes' THEN p.montant_paye END), 0) as panier_moyen
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        {$where}";
$stmt = $db->prepare($sql);
$stmt->execute($params_where);
$stats_globales = $stmt->fetch();

$total_transactions = $stats_globales['total_transactions'] ?? 0;
$total_collecte = $stats_globales['total_collecte'] ?? 0;
$total_succes = $stats_globales['total_succes'] ?? 0;
$total_echec = $stats_globales['total_echec'] ?? 0;
$total_attente = $stats_globales['total_attente'] ?? 0;
$panier_moyen = $stats_globales['panier_moyen'] ?? 0;
$taux_conversion = $total_transactions > 0 ? round(($total_succes / $total_transactions) * 100, 1) : 0;

// 2. Paiements par mois (pour le graphique)
$sql = "SELECT 
            DATE_FORMAT(p.date_paiement, '%Y-%m') as mois,
            COUNT(p.id_paiement) as nb_transactions,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as montant
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        {$where}
        GROUP BY mois 
        ORDER BY mois DESC 
        LIMIT 12";
$stmt = $db->prepare($sql);
$stmt->execute($params_where);
$paiements_mensuels = array_reverse($stmt->fetchAll());

// 3. Top filières par montant collecté
$sql = "SELECT 
            fi.nom_filiere, fa.nom_faculte,
            COUNT(p.id_paiement) as nb_paiements,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        {$where}
        GROUP BY fi.id_filiere 
        ORDER BY total DESC 
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute($params_where);
$top_filieres = $stmt->fetchAll();

// 4. Top promotions
$sql = "SELECT 
            pr.nom_promotion,
            COUNT(p.id_paiement) as nb_paiements,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN promotions pr ON e.id_promotion = pr.id_promotion 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        {$where}
        GROUP BY pr.id_promotion 
        ORDER BY total DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params_where);
$top_promotions = $stmt->fetchAll();

// 5. Répartition par statut (pour camembert)
$sql = "SELECT 
            p.statut,
            COUNT(p.id_paiement) as nb
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        {$where}
        GROUP BY p.statut";
$stmt = $db->prepare($sql);
$stmt->execute($params_where);
$repartition_statuts = $stmt->fetchAll();

// 6. Dernières transactions
$sql = "SELECT 
            p.*, u.nom as nom_etudiant, e.matricule, f.type_frais,
            fi.nom_filiere, fa.nom_faculte, pr.nom_promotion
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
        JOIN frais f ON p.id_frais = f.id_frais 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        JOIN promotions pr ON e.id_promotion = pr.id_promotion 
        {$where}
        ORDER BY p.date_paiement DESC 
        LIMIT 50";
$stmt = $db->prepare($sql);
$stmt->execute($params_where);
$transactions = $stmt->fetchAll();

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();

// Mois pour le filtre
$mois_labels = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
    '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
    '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
    '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/rapports.css">
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
                                <i class="fas fa-chart-bar"></i> Rapports & Statistiques
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-filter"></i> 
                                Analysez les performances financières avec des filtres avancés
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimer
                            </button>
                            <button class="btn btn-success ms-2" onclick="exportToCSV()">
                                <i class="fas fa-file-csv"></i> Exporter CSV
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtres avancés -->
                <div class="filtres-section">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-university"></i> Département</label>
                                <select name="faculte" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>" <?= $filtre_faculte === (int)$fac['id_faculte'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fac['nom_faculte']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-layer-group"></i> Filière</label>
                                <select name="filiere" class="form-select form-select-sm">
                                    <option value="">Toutes</option>
                                    <?php foreach ($filieres as $fil): ?>
                                        <option value="<?= $fil['id_filiere'] ?>" <?= $filtre_filiere === (int)$fil['id_filiere'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fil['nom_filiere']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-graduation-cap"></i> Promotion</label>
                                <select name="promotion" class="form-select form-select-sm">
                                    <option value="">Toutes</option>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>" <?= $filtre_promotion === (int)$promo['id_promotion'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($promo['nom_promotion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-1 col-md-2 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-calendar"></i> Année</label>
                                <select name="annee" class="form-select form-select-sm">
                                    <option value="">Toutes</option>
                                    <?php foreach ($annees_disponibles as $an): ?>
                                        <option value="<?= $an['annee'] ?>" <?= $filtre_annee == $an['annee'] ? 'selected' : '' ?>>
                                            <?= $an['annee'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-month"></i> Mois</label>
                                <select name="mois" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <?php foreach ($mois_labels as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $filtre_mois === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Statut</label>
                                <select name="statut" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="succes" <?= $filtre_statut === 'succes' ? 'selected' : '' ?>>Réussi</option>
                                    <option value="echec" <?= $filtre_statut === 'echec' ? 'selected' : '' ?>>Échec</option>
                                    <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                </select>
                            </div>
                            <div class="col-lg-1 col-md-2 col-sm-12 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if ($filtre_faculte > 0 || $filtre_filiere > 0 || !empty($filtre_mois) || !empty($filtre_statut)): ?>
                                    <a href="rapports.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                        <i class="fas fa-times"></i> Reset
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- KPIs -->
                <div class="kpi-grid">
                    <div class="kpi-card kpi-blue">
                        <div class="kpi-icon"><i class="fas fa-exchange-alt"></i></div>
                        <div class="kpi-info">
                            <h3><?= number_format($total_transactions, 0, ',', ' ') ?></h3>
                            <p>Transactions</p>
                        </div>
                    </div>
                    <div class="kpi-card kpi-green">
                        <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="kpi-info">
                            <h3><?= number_format($total_succes, 0, ',', ' ') ?></h3>
                            <p>Réussies</p>
                        </div>
                    </div>
                    <div class="kpi-card kpi-red">
                        <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="kpi-info">
                            <h3><?= number_format($total_echec, 0, ',', ' ') ?></h3>
                            <p>Échouées</p>
                        </div>
                    </div>
                    <div class="kpi-card kpi-yellow">
                        <div class="kpi-icon"><i class="fas fa-clock"></i></div>
                        <div class="kpi-info">
                            <h3><?= number_format($total_attente, 0, ',', ' ') ?></h3>
                            <p>En attente</p>
                        </div>
                    </div>
                    <div class="kpi-card kpi-purple">
                        <div class="kpi-icon"><i class="fas fa-coins"></i></div>
                        <div class="kpi-info">
                            <h3>$<?= number_format($total_collecte, 2, ',', ' ') ?></h3>
                            <p>Total collecté</p>
                        </div>
                    </div>
                    <div class="kpi-card kpi-teal">
                        <div class="kpi-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="kpi-info">
                            <h3>$<?= number_format($panier_moyen, 2) ?></h3>
                            <p>Panier moyen</p>
                        </div>
                    </div>
                    <div class="kpi-card kpi-indigo">
                        <div class="kpi-icon"><i class="fas fa-percent"></i></div>
                        <div class="kpi-info">
                            <h3><?= $taux_conversion ?>%</h3>
                            <p>Taux de succès</p>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="charts-grid">
                    <!-- Évolution mensuelle -->
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-chart-line"></i> Évolution mensuelle</h3>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartEvolution"></canvas>
                        </div>
                    </div>
                    
                    <!-- Répartition par statut -->
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-chart-pie"></i> Répartition par statut</h3>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartStatuts"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top filières & promotions -->
                <div class="tops-grid">
                    <div class="table-card">
                        <div class="table-card-header">
                            <h3><i class="fas fa-trophy text-warning"></i> Top 10 Filières</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table admin-table">
                                <thead>
                                    <tr><th>#</th><th>Filière</th><th>Département</th><th>Paiements</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_filieres)): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-3">Aucune donnée</td></tr>
                                    <?php else: ?>
                                        <?php $rank = 1; foreach ($top_filieres as $tf): ?>
                                            <tr>
                                                <td><?= $rank <= 3 ? '<span class="medal medal-' . $rank . '">' . $rank . '</span>' : $rank ?></td>
                                                <td><strong><?= htmlspecialchars($tf['nom_filiere']) ?></strong></td>
                                                <td><small><?= htmlspecialchars($tf['nom_faculte']) ?></small></td>
                                                <td><?= $tf['nb_paiements'] ?></td>
                                                <td><strong class="text-success">$<?= number_format($tf['total'], 2) ?></strong></td>
                                            </tr>
                                        <?php $rank++; endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="table-card">
                        <div class="table-card-header">
                            <h3><i class="fas fa-graduation-cap text-info"></i> Par Promotion</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table admin-table">
                                <thead>
                                    <tr><th>Promotion</th><th>Paiements</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_promotions)): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">Aucune donnée</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($top_promotions as $tp): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($tp['nom_promotion']) ?></strong></td>
                                                <td><?= $tp['nb_paiements'] ?></td>
                                                <td><strong class="text-success">$<?= number_format($tp['total'], 2) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tableau détaillé des transactions -->
                <div class="table-card mt-4">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list-alt"></i> Détail des transactions (<?= count($transactions) ?>)</h3>
                        <span class="badge-count"><?= number_format($total_collecte, 2) ?> $ collectés</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table table-sm" id="tableTransactions">
                            <thead>
                                <tr>
                                    <th>Réf.</th>
                                    <th>Étudiant</th>
                                    <th>Frais</th>
                                    <th>Filière</th>
                                    <th>Promo.</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune transaction trouvée avec ces filtres.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $tr): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars(substr($tr['reference_transaction'], 0, 15)) ?></code></td>
                                            <td>
                                                <strong><?= htmlspecialchars($tr['nom_etudiant']) ?></strong>
                                                <small class="d-block text-muted"><?= htmlspecialchars($tr['matricule']) ?></small>
                                            </td>
                                            <td><small><?= htmlspecialchars($tr['type_frais']) ?></small></td>
                                            <td><small><?= htmlspecialchars($tr['nom_filiere']) ?></small></td>
                                            <td><small><?= htmlspecialchars($tr['nom_promotion']) ?></small></td>
                                            <td><strong>$<?= number_format($tr['montant_paye'], 2) ?></strong></td>
                                            <td><small><?= date('d/m/Y H:i', strtotime($tr['date_paiement'])) ?></small></td>
                                            <td>
                                                <span class="status-pill pill-<?= $tr['statut'] === 'succes' ? 'success' : ($tr['statut'] === 'echec' ? 'danger' : 'warning') ?>">
                                                    <?= $tr['statut'] === 'succes' ? 'Réussi' : ($tr['statut'] === 'echec' ? 'Échec' : 'En attente') ?>
                                                </span>
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

    <script>
        const chartEvolutionLabels = <?= json_encode(array_column($paiements_mensuels, 'mois')) ?>;
        const chartEvolutionValues = <?= json_encode(array_map('floatval', array_column($paiements_mensuels, 'montant'))) ?>;
        const chartStatutsLabels = <?= json_encode(array_column($repartition_statuts, 'statut')) ?>;
        const chartStatutsValues = <?= json_encode(array_map('intval', array_column($repartition_statuts, 'nb'))) ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/rapports.js"></script>
</body>
</html>