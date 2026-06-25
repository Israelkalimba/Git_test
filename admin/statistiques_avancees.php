<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== FILTRES ==========
$filtre_annee = $_GET['annee'] ?? date('Y');
$filtre_faculte = isset($_GET['faculte']) ? (int)$_GET['faculte'] : 0;

// ========== DONNÉES STATISTIQUES ==========

// Années disponibles
$stmt = $db->query("SELECT DISTINCT YEAR(date_paiement) as annee FROM paiements UNION SELECT DISTINCT YEAR(created_at) as annee FROM utilisateurs WHERE role = 'etudiant' ORDER BY annee DESC");
$annees_disponibles = $stmt->fetchAll();
if (empty($annees_disponibles)) {
    $annees_disponibles = [['annee' => date('Y')]];
}

// Facultés pour filtre
$stmt = $db->query("SELECT * FROM facultes ORDER BY nom_faculte");
$facultes = $stmt->fetchAll();

// Clause WHERE
$where = "WHERE 1=1";
$params = [];
if (!empty($filtre_annee)) {
    $where .= " AND YEAR(p.date_paiement) = :annee";
    $params['annee'] = $filtre_annee;
}
if ($filtre_faculte > 0) {
    $where .= " AND fa.id_faculte = :faculte";
    $params['faculte'] = $filtre_faculte;
}

// ========== 1. ÉVOLUTION MENSUELLE (LINE CHART) ==========
$sql = "SELECT 
            DATE_FORMAT(p.date_paiement, '%b') as mois_label,
            DATE_FORMAT(p.date_paiement, '%m') as mois_num,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as montant,
            COUNT(CASE WHEN p.statut = 'succes' THEN 1 END) as nb_succes,
            COUNT(CASE WHEN p.statut = 'echec' THEN 1 END) as nb_echec
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        {$where}
        GROUP BY mois_num, mois_label 
        ORDER BY mois_num";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$evolution_mensuelle = $stmt->fetchAll();

// ========== 2. COMPARAISON ANNUELLE ==========
$sql = "SELECT 
            YEAR(p.date_paiement) as annee,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total,
            COUNT(CASE WHEN p.statut = 'succes' THEN 1 END) as nb_transactions
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        GROUP BY annee 
        ORDER BY annee";
$stmt = $db->query($sql);
$comparaison_annuelle = $stmt->fetchAll();

// ========== 3. RÉPARTITION PAR FACULTÉ ==========
$sql = "SELECT 
            fa.nom_faculte,
            COUNT(DISTINCT e.id_etudiant) as nb_etudiants,
            COUNT(p.id_paiement) as nb_paiements,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total
        FROM facultes fa 
        LEFT JOIN filieres fi ON fa.id_faculte = fi.id_faculte 
        LEFT JOIN etudiants e ON fi.id_filiere = e.id_filiere 
        LEFT JOIN paiements p ON e.id_etudiant = p.id_etudiant 
        {$where}
        GROUP BY fa.id_faculte 
        ORDER BY total DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$repartition_facultes = $stmt->fetchAll();

// ========== 4. TOP ÉTUDIANTS ==========
$sql = "SELECT 
            u.nom, e.matricule, fi.nom_filiere, pr.nom_promotion,
            COUNT(p.id_paiement) as nb_paiements,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total_paye
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        JOIN promotions pr ON e.id_promotion = pr.id_promotion 
        {$where} AND p.statut = 'succes'
        GROUP BY e.id_etudiant 
        ORDER BY total_paye DESC 
        LIMIT 15";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$top_etudiants = $stmt->fetchAll();

// ========== 5. TAUX DE PAIEMENT PAR PROMOTION ==========
$sql = "SELECT 
            pr.nom_promotion,
            COUNT(DISTINCT e.id_etudiant) as total_etudiants,
            COUNT(DISTINCT CASE WHEN p.id_paiement IS NOT NULL AND p.statut = 'succes' THEN e.id_etudiant END) as etudiants_ayant_paye,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total
        FROM promotions pr 
        LEFT JOIN etudiants e ON pr.id_promotion = e.id_promotion 
        LEFT JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        LEFT JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        LEFT JOIN paiements p ON e.id_etudiant = p.id_etudiant 
        {$where}
        GROUP BY pr.id_promotion 
        ORDER BY total DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiement_par_promotion = $stmt->fetchAll();

// ========== 6. TAUX DE PAIEMENT PAR FILIÈRE ==========
$sql = "SELECT 
            fi.nom_filiere, fa.nom_faculte,
            COUNT(DISTINCT e.id_etudiant) as total_etudiants,
            COUNT(DISTINCT CASE WHEN p.id_paiement IS NOT NULL AND p.statut = 'succes' THEN e.id_etudiant END) as etudiants_ayant_paye,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total
        FROM filieres fi 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        LEFT JOIN etudiants e ON fi.id_filiere = e.id_filiere 
        LEFT JOIN paiements p ON e.id_etudiant = p.id_etudiant 
        {$where}
        GROUP BY fi.id_filiere 
        ORDER BY total DESC 
        LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiement_par_filiere = $stmt->fetchAll();

// ========== 7. RÉPARTITION PAR TYPE DE FRAIS ==========
$sql = "SELECT 
            f.type_frais,
            COUNT(p.id_paiement) as nb_paiements,
            COALESCE(SUM(CASE WHEN p.statut = 'succes' THEN p.montant_paye ELSE 0 END), 0) as total
        FROM frais f 
        LEFT JOIN paiements p ON f.id_frais = p.id_frais 
        LEFT JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        LEFT JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        LEFT JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        {$where}
        GROUP BY f.type_frais 
        ORDER BY total DESC 
        LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$repartition_frais = $stmt->fetchAll();

// ========== 8. TAUX D'ÉCHEC PAR OPÉRATEUR ==========
$sql = "SELECT 
            tmm.operateur,
            COUNT(tmm.id_transaction) as nb_transactions,
            COUNT(CASE WHEN tmm.statut_api = 'succes' THEN 1 END) as succes,
            COUNT(CASE WHEN tmm.statut_api = 'echec' THEN 1 END) as echec
        FROM transaction_mobile_money tmm 
        JOIN paiements p ON tmm.id_paiement = p.id_paiement 
        {$where}
        GROUP BY tmm.operateur 
        ORDER BY nb_transactions DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$taux_echec_operateur = $stmt->fetchAll();

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();

// Période actuelle
$periode = $filtre_annee ? "Année {$filtre_annee}" : "Toutes les années";
if ($filtre_faculte > 0) {
    foreach ($facultes as $f) {
        if ($f['id_faculte'] == $filtre_faculte) {
            $periode .= " - " . $f['nom_faculte'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Avancées - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/statistiques_avancees.css">
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
                                <i class="fas fa-chart-pie"></i> Statistiques Avancées
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-calendar-alt"></i> Période : <strong><?= $periode ?></strong>
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <form method="GET" class="d-inline-flex gap-2">
                                <select name="annee" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    <option value="">Toutes années</option>
                                    <?php foreach ($annees_disponibles as $an): ?>
                                        <option value="<?= $an['annee'] ?>" <?= $filtre_annee == $an['annee'] ? 'selected' : '' ?>>
                                            <?= $an['annee'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="faculte" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    <option value="">Tous Départements</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>" <?= $filtre_faculte === (int)$fac['id_faculte'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fac['nom_faculte']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <?php if (!empty($filtre_annee) || $filtre_faculte > 0): ?>
                                    <a href="statistiques_avancees.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- GRAPHIQUE 1 : Évolution mensuelle -->
                <div class="chart-card chart-full mb-4">
                    <div class="chart-card-header">
                        <h3><i class="fas fa-chart-line"></i> Évolution Mensuelle des Revenus</h3>
                        <span class="chart-badge"><?= $periode ?></span>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="chartEvolution"></canvas>
                    </div>
                </div>

                <!-- GRAPHIQUES 2 & 3 : Comparaison annuelle + Répartition facultés -->
                <div class="charts-grid-2">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-chart-bar"></i> Comparaison Annuelle</h3>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartComparaison"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-university"></i> Répartition par Departement</h3>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartFacultes"></canvas>
                        </div>
                    </div>
                </div>

                <!-- GRAPHIQUES 4 & 5 : Frais + Opérateurs -->
                <div class="charts-grid-2">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-tags"></i> Top Types de Frais</h3>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartFrais"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h3><i class="fas fa-mobile-alt"></i> Performance Opérateurs</h3>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartOperateurs"></canvas>
                        </div>
                    </div>
                </div>

                <!-- TOP ÉTUDIANTS -->
                <div class="table-card mb-4">
                    <div class="table-card-header">
                        <h3><i class="fas fa-trophy text-warning"></i> Top 15 Étudiants (Montant payé)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr><th>#</th><th>Nom</th><th>Matricule</th><th>Filière</th><th>Promotion</th><th>Nb Paiements</th><th>Total</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_etudiants)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune donnée</td></tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($top_etudiants as $te): ?>
                                        <tr>
                                            <td><?= $rank <= 3 ? '<span class="medal medal-' . $rank . '">' . $rank . '</span>' : $rank ?></td>
                                            <td><strong><?= htmlspecialchars($te['nom']) ?></strong></td>
                                            <td><code><?= htmlspecialchars($te['matricule']) ?></code></td>
                                            <td><small><?= htmlspecialchars($te['nom_filiere']) ?></small></td>
                                            <td><small><?= htmlspecialchars($te['nom_promotion']) ?></small></td>
                                            <td><?= $te['nb_paiements'] ?></td>
                                            <td><strong class="text-success">$<?= number_format($te['total_paye'], 2) ?></strong></td>
                                        </tr>
                                    <?php $rank++; endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAUX DE PAIEMENT PAR PROMOTION & FILIÈRE -->
                <div class="charts-grid-2">
                    <div class="table-card">
                        <div class="table-card-header">
                            <h3><i class="fas fa-graduation-cap"></i> Taux de Paiement par Promotion</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table admin-table table-sm">
                                <thead>
                                    <tr><th>Promotion</th><th>Total Étudiants</th><th>Ayant Payé</th><th>Taux</th><th>Total $</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paiement_par_promotion as $pp): 
                                        $taux = $pp['total_etudiants'] > 0 ? round(($pp['etudiants_ayant_paye'] / $pp['total_etudiants']) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($pp['nom_promotion']) ?></strong></td>
                                            <td><?= $pp['total_etudiants'] ?></td>
                                            <td><?= $pp['etudiants_ayant_paye'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height:6px;">
                                                        <div class="progress-bar bg-<?= $taux >= 70 ? 'success' : ($taux >= 40 ? 'warning' : 'danger') ?>" 
                                                             style="width:<?= $taux ?>%"></div>
                                                    </div>
                                                    <small><?= $taux ?>%</small>
                                                </div>
                                            </td>
                                            <td><strong>$<?= number_format($pp['total'], 2) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="table-card">
                        <div class="table-card-header">
                            <h3><i class="fas fa-layer-group"></i> Taux de Paiement par Filière</h3>
                        </div>
                        <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                            <table class="table admin-table table-sm">
                                <thead>
                                    <tr><th>Filière</th><th>Étudiants</th><th>Payé</th><th>Taux</th><th>Total $</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paiement_par_filiere as $pf): 
                                        $taux = $pf['total_etudiants'] > 0 ? round(($pf['etudiants_ayant_paye'] / $pf['total_etudiants']) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($pf['nom_filiere']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($pf['nom_faculte']) ?></small></td>
                                            <td><?= $pf['total_etudiants'] ?></td>
                                            <td><?= $pf['etudiants_ayant_paye'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height:5px;">
                                                        <div class="progress-bar bg-<?= $taux >= 70 ? 'success' : ($taux >= 40 ? 'warning' : 'danger') ?>" 
                                                             style="width:<?= $taux ?>%"></div>
                                                    </div>
                                                    <small><?= $taux ?>%</small>
                                                </div>
                                            </td>
                                            <td><strong class="text-success">$<?= number_format($pf['total'], 2) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Données pour les graphiques -->
    <script>
        // Évolution mensuelle
        const dataEvolutionLabels = <?= json_encode(array_column($evolution_mensuelle, 'mois_label')) ?>;
        const dataEvolutionMontants = <?= json_encode(array_map('floatval', array_column($evolution_mensuelle, 'montant'))) ?>;
        const dataEvolutionSucces = <?= json_encode(array_map('intval', array_column($evolution_mensuelle, 'nb_succes'))) ?>;
        const dataEvolutionEchec = <?= json_encode(array_map('intval', array_column($evolution_mensuelle, 'nb_echec'))) ?>;
        
        // Comparaison annuelle
        const dataComparaisonLabels = <?= json_encode(array_column($comparaison_annuelle, 'annee')) ?>;
        const dataComparaisonValues = <?= json_encode(array_map('floatval', array_column($comparaison_annuelle, 'total'))) ?>;
        
        // Facultés
        const dataFacultesLabels = <?= json_encode(array_column($repartition_facultes, 'nom_faculte')) ?>;
        const dataFacultesValues = <?= json_encode(array_map('floatval', array_column($repartition_facultes, 'total'))) ?>;
        const dataFacultesEtudiants = <?= json_encode(array_map('intval', array_column($repartition_facultes, 'nb_etudiants'))) ?>;
        
        // Types de frais
        const dataFraisLabels = <?= json_encode(array_column($repartition_frais, 'type_frais')) ?>;
        const dataFraisValues = <?= json_encode(array_map('floatval', array_column($repartition_frais, 'total'))) ?>;
        
        // Opérateurs
        const dataOperateursLabels = <?= json_encode(array_column($taux_echec_operateur, 'operateur')) ?>;
        const dataOperateursSucces = <?= json_encode(array_map('intval', array_column($taux_echec_operateur, 'succes'))) ?>;
        const dataOperateursEchec = <?= json_encode(array_map('intval', array_column($taux_echec_operateur, 'echec'))) ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/statistiques_avancees.js"></script>
</body>
</html>