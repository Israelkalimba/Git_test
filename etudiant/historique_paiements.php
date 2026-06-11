<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('etudiant');

$db = Database::getInstance();
$etudiant_nom = $_SESSION['user_nom'] ?? 'Étudiant';
$etudiant_id_user = $_SESSION['user_id'] ?? 1;

// Récupérer l'id_etudiant
$stmt = $db->prepare("SELECT id_etudiant, matricule, telephone FROM etudiants WHERE id_utilisateur = :id_user");
$stmt->execute(['id_user' => $etudiant_id_user]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    echo "<script>alert('Erreur : Profil étudiant introuvable.'); window.location.href='../logout.php?role=etudiant';</script>";
    exit();
}

$id_etudiant = $etudiant['id_etudiant'];
$matricule = $etudiant['matricule'];

// ========== FILTRES ==========
$filtre_statut = $_GET['statut'] ?? '';
$filtre_date_debut = $_GET['date_debut'] ?? '';
$filtre_date_fin = $_GET['date_fin'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$par_page = 15;
$offset = ($page - 1) * $par_page;

// ========== CONSTRUCTION REQUÊTE ==========
$where = "WHERE p.id_etudiant = :id_etudiant";
$params = ['id_etudiant' => $id_etudiant];

if (!empty($filtre_statut) && in_array($filtre_statut, ['succes', 'echec', 'en_attente'])) {
    $where .= " AND p.statut = :statut";
    $params['statut'] = $filtre_statut;
}

if (!empty($filtre_date_debut)) {
    $where .= " AND DATE(p.date_paiement) >= :date_debut";
    $params['date_debut'] = $filtre_date_debut;
}

if (!empty($filtre_date_fin)) {
    $where .= " AND DATE(p.date_paiement) <= :date_fin";
    $params['date_fin'] = $filtre_date_fin;
}

// Total
$sql_count = "SELECT COUNT(*) as total FROM paiements p {$where}";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_paiements = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total_paiements / $par_page);

// Paiements
$sql = "SELECT p.*, f.type_frais, f.montant as montant_frais, f.taux_change, f.montant_fc,
               tmm.operateur, tmm.numero_telephone, tmm.statut_api
        FROM paiements p 
        JOIN frais f ON p.id_frais = f.id_frais 
        LEFT JOIN transaction_mobile_money tmm ON p.id_paiement = tmm.id_paiement
        {$where} 
        ORDER BY p.date_paiement DESC 
        LIMIT {$par_page} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll();

// ========== STATISTIQUES ==========
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes'");
$stmt->execute(['id' => $id_etudiant]);
$total_succes = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_etudiant = :id AND statut = 'echec'");
$stmt->execute(['id' => $id_etudiant]);
$total_echec = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_etudiant = :id AND statut = 'en_attente'");
$stmt->execute(['id' => $id_etudiant]);
$total_attente = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes'");
$stmt->execute(['id' => $id_etudiant]);
$total_paye = $stmt->fetch()['total'] ?? 0;

// ========== ÉVOLUTION MENSUELLE ==========
$evolution_mensuelle = [];
for ($i = 5; $i >= 0; $i--) {
    $mois = date('Y-m', strtotime("-{$i} months"));
    $stmt = $db->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes' AND DATE_FORMAT(date_paiement, '%Y-%m') = :mois");
    $stmt->execute(['id' => $id_etudiant, 'mois' => $mois]);
    $evolution_mensuelle[] = [
        'mois' => date('M Y', strtotime("-{$i} months")),
        'total' => (float)($stmt->fetch()['total'] ?? 0)
    ];
}

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt->execute(['id' => $etudiant_id_user]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt_nav = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt_nav->execute(['id' => $etudiant_id_user]);
$navbar_notifications = $stmt_nav->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - Étudiant ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/etudiant/dashboard_etudiant.css">
    <link rel="stylesheet" href="../assets/css/etudiant/historique_paiements.css">
</head>
<body>
    <div class="etudiant-layout">
        <?php include 'includes/sidebar_etudiant.php'; ?>
        <div class="main-content">
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_etudiant.php'; 
            ?>
            <main class="dashboard-content">
                
                <!-- En-tête -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-history"></i> Historique des Paiements
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-filter"></i> 
                                Consultez l'historique complet de vos transactions. 
                                <strong><?= $total_paiements ?> transaction(s)</strong>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <a href="mes_recus.php" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-file-pdf"></i> Mes Reçus
                            </a>
                            <a href="payer_frais.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-credit-card"></i> Payer maintenant
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="histo-stats">
                    <div class="histo-stat-card stat-succes">
                        <div class="histo-stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="histo-stat-info">
                            <h4><?= $total_succes ?></h4>
                            <p>Réussis</p>
                        </div>
                    </div>
                    <div class="histo-stat-card stat-echec">
                        <div class="histo-stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="histo-stat-info">
                            <h4><?= $total_echec ?></h4>
                            <p>Échoués</p>
                        </div>
                    </div>
                    <div class="histo-stat-card stat-attente">
                        <div class="histo-stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="histo-stat-info">
                            <h4><?= $total_attente ?></h4>
                            <p>En attente</p>
                        </div>
                    </div>
                    <div class="histo-stat-card stat-montant">
                        <div class="histo-stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="histo-stat-info">
                            <h4>$<?= number_format($total_paye, 2) ?></h4>
                            <p>Total payé</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-card">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Statut</label>
                                <select name="statut" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="succes" <?= $filtre_statut === 'succes' ? 'selected' : '' ?>>Réussi</option>
                                    <option value="echec" <?= $filtre_statut === 'echec' ? 'selected' : '' ?>>Échec</option>
                                    <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-calendar"></i> Du</label>
                                <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= $filtre_date_debut ?>">
                            </div>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-calendar"></i> Au</label>
                                <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= $filtre_date_fin ?>">
                            </div>
                            <div class="col-lg-3 col-md-12 col-sm-6 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if ($filtre_statut || $filtre_date_debut || $filtre_date_fin): ?>
                                    <a href="historique_paiements.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                        <i class="fas fa-times"></i> Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Graphique d'évolution -->
                <div class="chart-card-evo">
                    <div class="chart-card-header">
                        <h4><i class="fas fa-chart-line"></i> Évolution de mes paiements (6 derniers mois)</h4>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="chartEvolutionHisto"></canvas>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="table-card mt-4">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list-alt"></i> Toutes mes transactions</h3>
                        <span class="badge-count"><?= $total_paiements ?> transaction(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tableHistorique">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Type de Frais</th>
                                    <th>Montant Payé</th>
                                    <th>Équiv. FC</th>
                                    <th>Opérateur</th>
                                    <th>Statut API</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Reçu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paiements)): ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-history fa-3x"></i>
                                                <h4 class="mt-3">Aucune transaction</h4>
                                                <p class="text-muted">
                                                    <?= ($filtre_statut || $filtre_date_debut) ? 'Aucun résultat pour ces filtres.' : 'Vous n\'avez pas encore effectué de paiement.' ?>
                                                </p>
                                                <?php if (!$filtre_statut && !$filtre_date_debut): ?>
                                                    <a href="payer_frais.php" class="btn btn-primary mt-2">
                                                        <i class="fas fa-credit-card"></i> Payer maintenant
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paiements as $p): 
                                        $montant_fc = $p['montant_paye'] * ($p['taux_change'] ?? 2300);
                                        $est_succes = $p['statut'] === 'succes';
                                    ?>
                                        <tr class="paiement-row <?= $p['statut'] === 'echec' ? 'row-echec' : ($p['statut'] === 'en_attente' ? 'row-attente' : '') ?>">
                                            <td>
                                                <code class="ref-code" title="<?= htmlspecialchars($p['reference_transaction']) ?>">
                                                    <?= htmlspecialchars(substr($p['reference_transaction'], 0, 15)) ?>...
                                                </code>
                                            </td>
                                            <td>
                                                <span class="frais-badge"><?= htmlspecialchars($p['type_frais']) ?></span>
                                            </td>
                                            <td>
                                                <strong class="<?= $est_succes ? 'text-success' : ($p['statut'] === 'echec' ? 'text-danger' : 'text-warning') ?>">
                                                    $<?= number_format($p['montant_paye'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="fc-badge"><?= number_format($montant_fc, 0, ',', ' ') ?> FC</span>
                                            </td>
                                            <td>
                                                <?php if (!empty($p['operateur'])): ?>
                                                    <span class="operateur-badge"><?= htmlspecialchars($p['operateur']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($p['statut_api'])): ?>
                                                    <span class="api-badge api-<?= $p['statut_api'] ?>">
                                                        <?= ucfirst($p['statut_api']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></small>
                                                <small class="d-block text-muted"><?= date('H:i', strtotime($p['date_paiement'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="status-pill status-<?= $p['statut'] ?>">
                                                    <?= $p['statut'] === 'succes' ? 'Réussi' : ($p['statut'] === 'echec' ? 'Échec' : 'En attente') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($est_succes): ?>
                                                    <a href="mes_recus.php?action=telecharger&id=<?= $p['id_paiement'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank" title="Voir le reçu">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
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
                                Page <?= $page ?> sur <?= $total_pages ?> (<?= $total_paiements ?> transactions)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Légende -->
                <div class="legend-card-histo mt-3">
                    <h6><i class="fas fa-info-circle"></i> Légende</h6>
                    <div class="legend-items-histo">
                        <span class="legend-item-histo"><span class="legend-dot dot-succes"></span> Réussi</span>
                        <span class="legend-item-histo"><span class="legend-dot dot-echec"></span> Échec</span>
                        <span class="legend-item-histo"><span class="legend-dot dot-attente"></span> En attente</span>
                        <span class="legend-item-histo"><i class="fas fa-file-pdf text-primary"></i> Reçu disponible</span>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Données graphique -->
    <script>
    const evolutionLabelsHisto = <?= json_encode(array_column($evolution_mensuelle, 'mois')) ?>;
    const evolutionValuesHisto = <?= json_encode(array_column($evolution_mensuelle, 'total')) ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/etudiant/dashboard_etudiant.js"></script>
    <script src="../assets/js/etudiant/historique_paiements.js"></script>
</body>
</html>