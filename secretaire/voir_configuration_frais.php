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
$filtre_type_frais = $_GET['type_frais'] ?? '';

// ========== DONNÉES POUR LES FILTRES ==========
$stmt = $db->query("SELECT * FROM facultes ORDER BY nom_faculte");
$facultes = $stmt->fetchAll();

if ($filtre_faculte > 0) {
    $stmt = $db->prepare("SELECT * FROM filieres WHERE id_faculte = :id_faculte ORDER BY nom_filiere");
    $stmt->execute(['id_faculte' => $filtre_faculte]);
} else {
    $stmt = $db->query("SELECT fi.*, fa.nom_faculte FROM filieres fi JOIN facultes fa ON fi.id_faculte = fa.id_faculte ORDER BY fa.nom_faculte, fi.nom_filiere");
}
$filieres = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM promotions ORDER BY id_promotion");
$promotions = $stmt->fetchAll();

// Types de frais disponibles
$stmt = $db->query("SELECT DISTINCT type_frais FROM frais ORDER BY type_frais");
$types_frais = $stmt->fetchAll();

// ========== CONSTRUCTION REQUÊTE ==========
$where = "WHERE 1=1";
$params = [];

if ($filtre_faculte > 0) {
    $where .= " AND fa.id_faculte = :faculte";
    $params['faculte'] = $filtre_faculte;
}
if ($filtre_filiere > 0) {
    $where .= " AND fr.id_filiere = :filiere";
    $params['filiere'] = $filtre_filiere;
}
if ($filtre_promotion > 0) {
    $where .= " AND fr.id_promotion = :promotion";
    $params['promotion'] = $filtre_promotion;
}
if (!empty($filtre_type_frais)) {
    $where .= " AND fr.type_frais = :type_frais";
    $params['type_frais'] = $filtre_type_frais;
}

// Frais configurés avec stats de paiement
$sql = "SELECT fr.*, fi.nom_filiere, fa.nom_faculte, fa.id_faculte, p.nom_promotion,
               COUNT(pa.id_paiement) as nb_paiements,
               COALESCE(SUM(CASE WHEN pa.statut = 'succes' THEN pa.montant_paye ELSE 0 END), 0) as total_paye,
               COUNT(DISTINCT CASE WHEN pa.statut = 'succes' THEN pa.id_etudiant END) as nb_etudiants_paye
        FROM frais fr 
        JOIN filieres fi ON fr.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        JOIN promotions p ON fr.id_promotion = p.id_promotion 
        LEFT JOIN paiements pa ON fr.id_frais = pa.id_frais
        {$where}
        GROUP BY fr.id_frais
        ORDER BY fa.nom_faculte, fi.nom_filiere, p.nom_promotion, fr.type_frais";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$frais_configures = $stmt->fetchAll();

// Stats
$total_configs = count($frais_configures);
$total_montant = array_sum(array_column($frais_configures, 'montant'));
$total_paye = array_sum(array_column($frais_configures, 'total_paye'));

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
    <title>Configuration des Frais - Secrétaire ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/secretaire/dashboard_secretaire.css">
    <link rel="stylesheet" href="../assets/css/secretaire/voir_configuration_frais.css">
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
                                <i class="fas fa-cogs"></i> Configuration des Frais Académiques
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-eye"></i> 
                                Consultez les frais configurés par l'administration. 
                                <strong>Lecture seule</strong> - Les modifications sont réservées aux administrateurs.
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

                <!-- Stats -->
                <div class="config-stats">
                    <div class="config-stat-card">
                        <div class="config-stat-icon bg-purple">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="config-stat-info">
                            <h4><?= $total_configs ?></h4>
                            <p>Configurations</p>
                        </div>
                    </div>
                    <div class="config-stat-card">
                        <div class="config-stat-icon bg-blue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="config-stat-info">
                            <h4>$<?= number_format($total_montant, 2) ?></h4>
                            <p>Total configuré</p>
                        </div>
                    </div>
                    <div class="config-stat-card">
                        <div class="config-stat-icon bg-green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="config-stat-info">
                            <h4>$<?= number_format($total_paye, 2) ?></h4>
                            <p>Total collecté</p>
                        </div>
                    </div>
                    <div class="config-stat-card">
                        <div class="config-stat-icon bg-teal">
                            <i class="fas fa-percent"></i>
                        </div>
                        <div class="config-stat-info">
                            <h4><?= $total_montant > 0 ? round(($total_paye / $total_montant) * 100, 1) : 0 ?>%</h4>
                            <p>Taux réalisation</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-card">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-university"></i> Faculté</label>
                                <select name="faculte" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes les facultés</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>" <?= $filtre_faculte === (int)$fac['id_faculte'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fac['nom_faculte']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-layer-group"></i> Filière</label>
                                <select name="filiere" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes les filières</option>
                                    <?php foreach ($filieres as $fil): ?>
                                        <option value="<?= $fil['id_filiere'] ?>" <?= $filtre_filiere === (int)$fil['id_filiere'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fil['nom_filiere']) ?>
                                            <?= isset($fil['nom_faculte']) ? '(' . htmlspecialchars($fil['nom_faculte']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
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
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Type frais</label>
                                <select name="type_frais" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Tous</option>
                                    <?php foreach ($types_frais as $tf): ?>
                                        <option value="<?= htmlspecialchars($tf['type_frais']) ?>" <?= $filtre_type_frais === $tf['type_frais'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tf['type_frais']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-12 mb-2">
                                <?php if ($filtre_faculte || $filtre_filiere || $filtre_promotion || $filtre_type_frais): ?>
                                    <a href="voir_configuration_frais.php" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="fas fa-times"></i> Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tableau des configurations -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list-alt"></i> Frais Académiques Configurés</h3>
                        <span class="badge-count"><?= $total_configs ?> configuration(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tableConfig">
                            <thead>
                                <tr>
                                    <th>Type de Frais</th>
                                    <th>Faculté</th>
                                    <th>Filière</th>
                                    <th>Promotion</th>
                                    <th>Montant USD</th>
                                    <th>Équiv. FC</th>
                                    <th>Taux</th>
                                    <th>Année</th>
                                    <th>Nb Paiements</th>
                                    <th>Total Collecté</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($frais_configures)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-cogs fa-3x"></i>
                                                <h4 class="mt-3">Aucune configuration trouvée</h4>
                                                <p class="text-muted">Essayez de modifier les filtres.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($frais_configures as $frais): 
                                        $taux_remplissage = $frais['montant'] > 0 ? round(($frais['total_paye'] / $frais['montant']) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="type-frais-badge">
                                                    <i class="fas fa-tag"></i>
                                                    <?= htmlspecialchars($frais['type_frais']) ?>
                                                </span>
                                            </td>
                                            <td><small><?= htmlspecialchars($frais['nom_faculte']) ?></small></td>
                                            <td><small><?= htmlspecialchars($frais['nom_filiere']) ?></small></td>
                                            <td><span class="promo-badge-sm"><?= htmlspecialchars($frais['nom_promotion']) ?></span></td>
                                            <td>
                                                <strong class="text-primary">$<?= number_format($frais['montant'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <span class="fc-badge">
                                                    <?= number_format($frais['montant_fc'] ?? ($frais['montant'] * ($frais['taux_change'] ?? 2300)), 0, ',', ' ') ?> FC
                                                </span>
                                            </td>
                                            <td><small>1$ = <?= number_format($frais['taux_change'] ?? 2300, 0) ?> FC</small></td>
                                            <td><small><?= htmlspecialchars($frais['annee_academique']) ?></small></td>
                                            <td>
                                                <span class="nb-paiements-badge <?= $frais['nb_paiements'] > 0 ? 'has-paiements' : '' ?>">
                                                    <?= $frais['nb_paiements'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong class="<?= $frais['total_paye'] > 0 ? 'text-success' : 'text-muted' ?>">
                                                        $<?= number_format($frais['total_paye'], 2) ?>
                                                    </strong>
                                                </div>
                                                <?php if ($frais['montant'] > 0): ?>
                                                    <div class="progress" style="height:4px;width:80px;margin-top:4px;">
                                                        <div class="progress-bar bg-<?= $taux_remplissage >= 80 ? 'success' : ($taux_remplissage >= 40 ? 'warning' : 'danger') ?>" 
                                                             style="width:<?= $taux_remplissage ?>%"></div>
                                                    </div>
                                                    <small class="text-muted"><?= $frais['nb_etudiants_paye'] ?> étud.</small>
                                                <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/secretaire/dashboard_secretaire.js"></script>
    <script src="../assets/js/secretaire/voir_configuration_frais.js"></script>
</body>
</html>