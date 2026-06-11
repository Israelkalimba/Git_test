<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('secretaire');

$db = Database::getInstance();
$secretaire_nom = $_SESSION['user_nom'] ?? 'Secrétaire';
$secretaire_id = $_SESSION['user_id'] ?? 1;

// ========== FILTRES ==========
$filtre_faculte = isset($_GET['faculte']) ? (int)$_GET['faculte'] : 0;
$filtre_filiere = isset($_GET['filiere']) ? (int)$_GET['filiere'] : 0;
$filtre_promotion = isset($_GET['promotion']) ? (int)$_GET['promotion'] : 0;
$filtre_recherche = trim($_GET['recherche'] ?? '');
$filtre_statut_paiement = $_GET['statut_paiement'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$par_page = 25;
$offset = ($page - 1) * $par_page;

// ========== DONNÉES POUR LES FILTRES ==========
$stmt = $db->query("SELECT * FROM facultes ORDER BY nom_faculte");
$facultes = $stmt->fetchAll();

if ($filtre_faculte > 0) {
    $stmt = $db->prepare("SELECT * FROM filieres WHERE id_faculte = :id_faculte ORDER BY nom_filiere");
    $stmt->execute(['id_faculte' => $filtre_faculte]);
} else {
    $stmt = $db->query("SELECT * FROM filieres ORDER BY nom_filiere");
}
$filieres = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM promotions ORDER BY id_promotion");
$promotions = $stmt->fetchAll();

// ========== CONSTRUCTION REQUÊTE ==========
$where = "WHERE u.role = 'etudiant'";
$params = [];

if ($filtre_faculte > 0) {
    $where .= " AND fa.id_faculte = :faculte";
    $params['faculte'] = $filtre_faculte;
}
if ($filtre_filiere > 0) {
    $where .= " AND fi.id_filiere = :filiere";
    $params['filiere'] = $filtre_filiere;
}
if ($filtre_promotion > 0) {
    $where .= " AND e.id_promotion = :promotion";
    $params['promotion'] = $filtre_promotion;
}
if (!empty($filtre_recherche)) {
    $where .= " AND (u.nom LIKE :r1 OR e.matricule LIKE :r2 OR u.email LIKE :r3 OR e.telephone LIKE :r4)";
    $params['r1'] = "%{$filtre_recherche}%";
    $params['r2'] = "%{$filtre_recherche}%";
    $params['r3'] = "%{$filtre_recherche}%";
    $params['r4'] = "%{$filtre_recherche}%";
}

// Filtre par statut de paiement
$having = '';
if ($filtre_statut_paiement === 'paye') {
    $having = " HAVING total_paye > 0";
} elseif ($filtre_statut_paiement === 'non_paye') {
    $having = " HAVING total_paye = 0";
}

// Total
$sql_count = "SELECT COUNT(*) as total FROM (
    SELECT e.id_etudiant
    FROM utilisateurs u 
    JOIN etudiants e ON u.id_utilisateur = e.id_utilisateur 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    JOIN promotions pr ON e.id_promotion = pr.id_promotion 
    LEFT JOIN paiements p ON e.id_etudiant = p.id_etudiant AND p.statut = 'succes'
    {$where}
    GROUP BY e.id_etudiant
    {$having}
) as sub";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_etudiants = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total_etudiants / $par_page);

// Étudiants avec stats de paiement
$sql = "SELECT u.nom, u.email, u.created_at, e.id_etudiant, e.matricule, e.telephone,
               fi.nom_filiere, fa.nom_faculte, fa.id_faculte,
               pr.nom_promotion, pr.id_promotion,
               COUNT(p.id_paiement) as nb_paiements,
               COALESCE(SUM(p.montant_paye), 0) as total_paye
        FROM utilisateurs u 
        JOIN etudiants e ON u.id_utilisateur = e.id_utilisateur 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        JOIN promotions pr ON e.id_promotion = pr.id_promotion 
        LEFT JOIN paiements p ON e.id_etudiant = p.id_etudiant AND p.statut = 'succes'
        {$where}
        GROUP BY e.id_etudiant
        {$having}
        ORDER BY u.nom
        LIMIT {$par_page} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$etudiants = $stmt->fetchAll();

// Stats globales
$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant'");
$total_tous = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(DISTINCT e.id_etudiant) as total FROM etudiants e JOIN paiements p ON e.id_etudiant = p.id_etudiant WHERE p.statut = 'succes'");
$total_ayant_paye = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'etudiant' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$nouveaux_30j = $stmt->fetch()['total'] ?? 0;

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
    <title>Registre Étudiants - Secrétaire ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/secretaire/dashboard_secretaire.css">
    <link rel="stylesheet" href="../assets/css/secretaire/registre_etudiants.css">
</head>
<body>
    <div class="secretaire-layout">
        <?php include 'includes/sidebar_secretaire.php'; ?>
        <div class="main-content">
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_secretaire.php'; 
            ?>
            <main class="dashboard-content">
                
                <!-- En-tête -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-users"></i> Registre des Étudiants
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Consultez la liste complète des étudiants et leur statut de paiement.
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-outline-success btn-sm me-2" onclick="exportToCSV()">
                                <i class="fas fa-file-csv"></i> Exporter CSV
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats rapides -->
                <div class="registre-stats">
                    <div class="reg-stat-card">
                        <div class="reg-stat-icon bg-blue"><i class="fas fa-users"></i></div>
                        <div class="reg-stat-info">
                            <h4><?= number_format($total_tous) ?></h4>
                            <p>Total étudiants</p>
                        </div>
                    </div>
                    <div class="reg-stat-card">
                        <div class="reg-stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
                        <div class="reg-stat-info">
                            <h4><?= number_format($total_ayant_paye) ?></h4>
                            <p>Ayant payé</p>
                        </div>
                    </div>
                    <div class="reg-stat-card">
                        <div class="reg-stat-icon bg-orange"><i class="fas fa-clock"></i></div>
                        <div class="reg-stat-info">
                            <h4><?= number_format($total_tous - $total_ayant_paye) ?></h4>
                            <p>N'ayant pas payé</p>
                        </div>
                    </div>
                    <div class="reg-stat-card">
                        <div class="reg-stat-icon bg-purple"><i class="fas fa-user-plus"></i></div>
                        <div class="reg-stat-info">
                            <h4><?= $nouveaux_30j ?></h4>
                            <p>Nouveaux (30 jours)</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres avancés -->
                <div class="filtres-card">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-university"></i> Faculté</label>
                                <select name="faculte" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>" <?= $filtre_faculte === (int)$fac['id_faculte'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fac['nom_faculte']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-layer-group"></i> Filière</label>
                                <select name="filiere" class="form-select form-select-sm" onchange="this.form.submit()">
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
                                <select name="promotion" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes</option>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>" <?= $filtre_promotion === (int)$promo['id_promotion'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($promo['nom_promotion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-credit-card"></i> Statut paiement</label>
                                <select name="statut_paiement" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Tous</option>
                                    <option value="paye" <?= $filtre_statut_paiement === 'paye' ? 'selected' : '' ?>>Ayant payé</option>
                                    <option value="non_paye" <?= $filtre_statut_paiement === 'non_paye' ? 'selected' : '' ?>>N'ayant pas payé</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-8 mb-2">
                                <label class="filtre-label"><i class="fas fa-search"></i> Recherche</label>
                                <input type="text" name="recherche" class="form-control form-control-sm" 
                                       placeholder="Nom, matricule, email, téléphone..." 
                                       value="<?= htmlspecialchars($filtre_recherche) ?>">
                            </div>
                            <div class="col-lg-1 col-md-2 col-sm-4 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <?php if ($filtre_faculte || $filtre_filiere || $filtre_promotion || $filtre_statut_paiement || $filtre_recherche): ?>
                                    <a href="registre_etudiants.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tableau des étudiants -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Liste des étudiants</h3>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge-count"><?= number_format($total_etudiants) ?> étudiant(s)</span>
                            <small class="text-muted">
                                Taux de paiement : 
                                <strong><?= $total_tous > 0 ? round(($total_ayant_paye / $total_tous) * 100, 1) : 0 ?>%</strong>
                            </small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tableEtudiants">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Faculté</th>
                                    <th>Filière</th>
                                    <th>Promotion</th>
                                    <th>Nb Paiements</th>
                                    <th>Total Payé</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($etudiants)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-user-graduate fa-3x"></i>
                                                <h4 class="mt-3">Aucun étudiant trouvé</h4>
                                                <p class="text-muted">Essayez de modifier les filtres.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etudiants as $etu): ?>
                                        <tr class="etudiant-row <?= $etu['total_paye'] > 0 ? 'row-paye' : 'row-non-paye' ?>">
                                            <td><code class="matr-code"><?= htmlspecialchars($etu['matricule']) ?></code></td>
                                            <td>
                                                <div class="etudiant-info">
                                                    <div class="etudiant-avatar-sm">
                                                        <?= strtoupper(substr($etu['nom'], 0, 2)) ?>
                                                    </div>
                                                    <strong><?= htmlspecialchars($etu['nom']) ?></strong>
                                                </div>
                                            </td>
                                            <td><small><?= htmlspecialchars($etu['email']) ?></small></td>
                                            <td><small><?= htmlspecialchars($etu['telephone'] ?? 'N/A') ?></small></td>
                                            <td><small><?= htmlspecialchars($etu['nom_faculte']) ?></small></td>
                                            <td><small><?= htmlspecialchars($etu['nom_filiere']) ?></small></td>
                                            <td><span class="promo-badge"><?= htmlspecialchars($etu['nom_promotion']) ?></span></td>
                                            <td>
                                                <span class="nb-paiements-badge <?= $etu['nb_paiements'] > 0 ? 'has-paiements' : '' ?>">
                                                    <?= $etu['nb_paiements'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="<?= $etu['total_paye'] > 0 ? 'text-success' : 'text-muted' ?>">
                                                    $<?= number_format($etu['total_paye'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php if ($etu['total_paye'] > 0): ?>
                                                    <span class="status-pill status-succes">
                                                        <i class="fas fa-check-circle"></i> En règle
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-pill status-non-paye">
                                                        <i class="fas fa-exclamation-circle"></i> Non payé
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <nav>
                                <ul class="pagination pagination-sm justify-content-center">
                                    <?php 
                                    $url_params = $_GET;
                                    unset($url_params['page']);
                                    $base_url = '?' . http_build_query($url_params);
                                    if (!empty($url_params)) $base_url .= '&';
                                    else $base_url = '?';
                                    
                                    // Première page
                                    echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . $base_url . 'page=1"><i class="fas fa-angle-double-left"></i></a></li>';
                                    echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . $base_url . 'page=' . ($page - 1) . '"><i class="fas fa-angle-left"></i></a></li>';
                                    
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    for ($i = $start; $i <= $end; $i++) {
                                        echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="' . $base_url . 'page=' . $i . '">' . $i . '</a></li>';
                                    }
                                    
                                    echo '<li class="page-item ' . ($page >= $total_pages ? 'disabled' : '') . '"><a class="page-link" href="' . $base_url . 'page=' . ($page + 1) . '"><i class="fas fa-angle-right"></i></a></li>';
                                    echo '<li class="page-item ' . ($page >= $total_pages ? 'disabled' : '') . '"><a class="page-link" href="' . $base_url . 'page=' . $total_pages . '"><i class="fas fa-angle-double-right"></i></a></li>';
                                    ?>
                                </ul>
                            </nav>
                            <p class="text-center text-muted small mt-2">
                                Page <?= $page ?> sur <?= $total_pages ?> (<?= number_format($total_etudiants) ?> étudiants)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/secretaire/dashboard_secretaire.js"></script>
    <script src="../assets/js/secretaire/registre_etudiants.js"></script>
</body>
</html>