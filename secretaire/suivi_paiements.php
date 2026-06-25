<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('secretaire');

$db = Database::getInstance();
$secretaire_nom = $_SESSION['user_nom'] ?? 'Secrétaire';
$secretaire_id = $_SESSION['user_id'] ?? 1;

// ========== FILTRES ==========
$filtre_statut = $_GET['statut'] ?? '';
$filtre_faculte = isset($_GET['faculte']) ? (int)$_GET['faculte'] : 0;
$filtre_filiere = isset($_GET['filiere']) ? (int)$_GET['filiere'] : 0;
$filtre_promotion = isset($_GET['promotion']) ? (int)$_GET['promotion'] : 0;
$filtre_date_debut = $_GET['date_debut'] ?? '';
$filtre_date_fin = $_GET['date_fin'] ?? '';
$filtre_recherche = trim($_GET['recherche'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$par_page = 20;
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
$where = "WHERE 1=1";
$params = [];

if (!empty($filtre_statut)) {
    $where .= " AND p.statut = :statut";
    $params['statut'] = $filtre_statut;
}
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
if (!empty($filtre_date_debut)) {
    $where .= " AND DATE(p.date_paiement) >= :date_debut";
    $params['date_debut'] = $filtre_date_debut;
}
if (!empty($filtre_date_fin)) {
    $where .= " AND DATE(p.date_paiement) <= :date_fin";
    $params['date_fin'] = $filtre_date_fin;
}
if (!empty($filtre_recherche)) {
    $where .= " AND (u.nom LIKE :r1 OR e.matricule LIKE :r2 OR p.reference_transaction LIKE :r3 OR f.type_frais LIKE :r4)";
    $params['r1'] = "%{$filtre_recherche}%";
    $params['r2'] = "%{$filtre_recherche}%";
    $params['r3'] = "%{$filtre_recherche}%";
    $params['r4'] = "%{$filtre_recherche}%";
}

// Total
$sql_count = "SELECT COUNT(*) as total FROM paiements p 
              JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
              JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
              JOIN filieres fi ON e.id_filiere = fi.id_filiere 
              JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
              JOIN promotions pr ON e.id_promotion = pr.id_promotion 
              JOIN frais f ON p.id_frais = f.id_frais 
              {$where}";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_paiements = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total_paiements / $par_page);

// Paiements
$sql = "SELECT p.*, u.nom as nom_etudiant, u.email, e.matricule, e.telephone,
               fi.nom_filiere, fa.nom_faculte, pr.nom_promotion,
               f.type_frais, f.montant as montant_attendu,
               tmm.operateur, tmm.statut_api
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        JOIN promotions pr ON e.id_promotion = pr.id_promotion 
        JOIN frais f ON p.id_frais = f.id_frais 
        LEFT JOIN transaction_mobile_money tmm ON p.id_paiement = tmm.id_paiement
        {$where} 
        ORDER BY p.date_paiement DESC 
        LIMIT {$par_page} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll();

// Stats rapides
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE DATE(date_paiement) = CURDATE()");
$paiements_aujourdhui = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE statut = 'succes' AND DATE(date_paiement) = CURDATE()");
$montant_aujourdhui = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente'");
$en_attente = $stmt->fetch()['total'] ?? 0;

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
    <title>Suivi des Paiements - Secrétaire ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/secretaire/dashboard_secretaire.css">
    <link rel="stylesheet" href="../assets/css/secretaire/suivi_paiements.css">
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
                        <div class="col-lg-6">
                            <h1 class="page-title">
                                <i class="fas fa-search"></i> Suivi des Paiements
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-filter"></i> 
                                Consultez et filtrez tous les paiements. 
                                <span class="text-success"><?= $paiements_aujourdhui ?> aujourd'hui</span>
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <span class="badge-stat me-3">
                                <i class="fas fa-coins"></i> 
                                Aujourd'hui : <strong>$<?= number_format($montant_aujourdhui, 2) ?></strong>
                            </span>
                            <a href="validation_paiements.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-check-double"></i> Validation (<?= $en_attente ?>)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filtres avancés -->
                <div class="filtres-card">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Statut</label>
                                <select name="statut" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="succes" <?= $filtre_statut === 'succes' ? 'selected' : '' ?>>Réussi</option>
                                    <option value="echec" <?= $filtre_statut === 'echec' ? 'selected' : '' ?>>Échec</option>
                                    <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-university"></i> Département</label>
                                <select name="faculte" class="form-select form-select-sm" onchange="this.form.submit()">
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
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-calendar"></i> Du</label>
                                <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= $filtre_date_debut ?>">
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-calendar"></i> Au</label>
                                <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= $filtre_date_fin ?>">
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-8 mb-2">
                                <label class="filtre-label"><i class="fas fa-search"></i> Recherche</label>
                                <input type="text" name="recherche" class="form-control form-control-sm" 
                                       placeholder="Nom, matricule, référence..." value="<?= htmlspecialchars($filtre_recherche) ?>">
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-4 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if ($filtre_statut || $filtre_faculte || $filtre_filiere || $filtre_date_debut || $filtre_recherche): ?>
                                    <a href="suivi_paiements.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                        <i class="fas fa-times"></i> Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tableau des paiements -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list-alt"></i> Liste des paiements</h3>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge-count"><?= number_format($total_paiements) ?> résultat(s)</span>
                            <button class="btn btn-outline-success btn-sm" onclick="exportToCSV()">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimer
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tablePaiements">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Matricule</th>
                                    <th>Téléphone</th>
                                    <th>Frais</th>
                                    <th>Filière</th>
                                    <th>Promo.</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paiements)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-search fa-3x"></i>
                                                <h4 class="mt-3">Aucun paiement trouvé</h4>
                                                <p class="text-muted">Essayez de modifier les filtres.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paiements as $paiement): ?>
                                        <tr class="paiement-row <?= $paiement['statut'] === 'echec' ? 'row-echec' : '' ?>">
                                            <td>
                                                <code class="ref-code" title="<?= htmlspecialchars($paiement['reference_transaction']) ?>">
                                                    <?= htmlspecialchars(substr($paiement['reference_transaction'], 0, 12)) ?>...
                                                </code>
                                            </td>
                                            <td>
                                                <div class="etudiant-info-sm">
                                                    <strong><?= htmlspecialchars($paiement['nom_etudiant']) ?></strong>
                                                    <small class="d-block text-muted"><?= htmlspecialchars($paiement['email']) ?></small>
                                                </div>
                                            </td>
                                            <td><code class="matricule-code"><?= htmlspecialchars($paiement['matricule']) ?></code></td>
                                            <td><small><?= htmlspecialchars($paiement['telephone'] ?? 'N/A') ?></small></td>
                                            <td>
                                                <span class="frais-badge"><?= htmlspecialchars($paiement['type_frais']) ?></span>
                                            </td>
                                            <td><small><?= htmlspecialchars($paiement['nom_filiere']) ?></small></td>
                                            <td><span class="promo-badge-sm"><?= htmlspecialchars($paiement['nom_promotion']) ?></span></td>
                                            <td>
                                                <strong class="<?= $paiement['statut'] === 'succes' ? 'text-success' : ($paiement['statut'] === 'echec' ? 'text-danger' : 'text-warning') ?>">
                                                    $<?= number_format($paiement['montant_paye'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?></small>
                                                <small class="d-block text-muted"><?= date('H:i', strtotime($paiement['date_paiement'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="status-pill status-<?= $paiement['statut'] ?>">
                                                    <i class="fas fa-<?= $paiement['statut'] === 'succes' ? 'check-circle' : ($paiement['statut'] === 'echec' ? 'times-circle' : 'clock') ?>"></i>
                                                    <?= $paiement['statut'] === 'succes' ? 'Réussi' : ($paiement['statut'] === 'echec' ? 'Échec' : 'En attente') ?>
                                                </span>
                                                <?php if (!empty($paiement['operateur'])): ?>
                                                    <small class="d-block text-muted mt-1"><?= htmlspecialchars($paiement['operateur']) ?></small>
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
                                    
                                    for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= $base_url ?>page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <p class="text-center text-muted small mt-2">
                                Page <?= $page ?> sur <?= $total_pages ?> (<?= number_format($total_paiements) ?> paiements)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/secretaire/dashboard_secretaire.js"></script>
    <script src="../assets/js/secretaire/suivi_paiements.js"></script>
</body>
</html>