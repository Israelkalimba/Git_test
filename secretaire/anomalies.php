<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('secretaire');

$db = Database::getInstance();
$secretaire_nom = $_SESSION['user_nom'] ?? 'Secrétaire';
$secretaire_id = $_SESSION['user_id'] ?? 1;

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// Signaler une anomalie comme traitée
if (isset($_GET['action']) && $_GET['action'] === 'traiter' && isset($_GET['id'])) {
    $id_paiement = (int)$_GET['id'];
    $commentaire = trim($_GET['commentaire'] ?? '');
    
    if (empty($commentaire)) {
        $message = "❌ Veuillez fournir un commentaire de résolution.";
        $message_type = 'danger';
    } else {
        try {
            $db->beginTransaction();
            
            // Récupérer les infos
            $stmt = $db->prepare("SELECT p.*, u.nom, e.matricule FROM paiements p JOIN etudiants e ON p.id_etudiant = e.id_etudiant JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur WHERE p.id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
            $paiement = $stmt->fetch();
            
            if ($paiement) {
                // Ajouter au journal d'audit
                $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'anomalie', 'anomalie_traitee', :desc, :ip)");
                $stmt->execute([
                    'uid' => $secretaire_id,
                    'desc' => "Anomalie #{$id_paiement} traitée par {$secretaire_nom} - {$paiement['nom']} ({$paiement['matricule']}) - Commentaire: {$commentaire}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
                
                // Notifier l'admin
                $stmt = $db->prepare("INSERT INTO notifications (id_utilisateur, message, statut) SELECT id_utilisateur, :msg, 'non_lu' FROM utilisateurs WHERE role = 'admin' LIMIT 1");
                $stmt->execute(['msg' => "Anomalie #{$id_paiement} traitée par le secrétaire {$secretaire_nom} : {$commentaire}"]);
                
                $db->commit();
                $message = "✅ Anomalie #{$id_paiement} marquée comme traitée.";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "❌ Erreur : " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// ========== FILTRES ==========
$filtre_statut = $_GET['statut'] ?? '';
$filtre_type = $_GET['type_anomalie'] ?? '';
$filtre_recherche = trim($_GET['recherche'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$par_page = 20;
$offset = ($page - 1) * $par_page;

// ========== RÉCUPÉRATION DES ANOMALIES ==========
$where = "WHERE (p.statut = 'echec' OR p.statut = 'en_attente')";
$params = [];

if (!empty($filtre_statut)) {
    $where = "WHERE p.statut = :statut";
    $params['statut'] = $filtre_statut;
}

if (!empty($filtre_type)) {
    switch ($filtre_type) {
        case 'api_echec':
            $where .= " AND tmm.statut_api = 'echec'";
            break;
        case 'montant_incorrect':
            $where .= " AND p.montant_paye != f.montant";
            break;
        case 'double_paiement':
            $where .= " AND p.reference_transaction IN (SELECT reference_transaction FROM paiements GROUP BY reference_transaction HAVING COUNT(*) > 1)";
            break;
        case 'bloque_longtemps':
            $where .= " AND p.date_paiement < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            break;
        case 'sans_operateur':
            $where .= " AND tmm.operateur IS NULL";
            break;
    }
}

if (!empty($filtre_recherche)) {
    $where .= " AND (u.nom LIKE :r1 OR e.matricule LIKE :r2 OR p.reference_transaction LIKE :r3)";
    $params['r1'] = "%{$filtre_recherche}%";
    $params['r2'] = "%{$filtre_recherche}%";
    $params['r3'] = "%{$filtre_recherche}%";
}

// Total
$sql_count = "SELECT COUNT(*) as total FROM paiements p 
              JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
              JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
              JOIN frais f ON p.id_frais = f.id_frais 
              LEFT JOIN transaction_mobile_money tmm ON p.id_paiement = tmm.id_paiement
              {$where}";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_anomalies = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total_anomalies / $par_page);

// Anomalies
$sql = "SELECT p.*, u.nom as nom_etudiant, u.email, e.matricule, e.telephone,
               fi.nom_filiere, fa.nom_faculte, pr.nom_promotion,
               f.type_frais, f.montant as montant_attendu,
               tmm.operateur, tmm.numero_telephone, tmm.statut_api
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
$anomalies = $stmt->fetchAll();

// Stats
$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'echec'");
$total_echecs = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente'");
$total_attente = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut IN ('echec', 'en_attente') AND date_paiement < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$bloques_longtemps = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE statut = 'echec'");
$montant_echecs = $stmt->fetch()['total'] ?? 0;

// Types d'anomalies disponibles
$types_anomalies = [
    'api_echec' => 'Échec API',
    'montant_incorrect' => 'Montant incorrect',
    'double_paiement' => 'Doublon',
    'bloque_longtemps' => 'Bloqué > 1h',
    'sans_operateur' => 'Sans opérateur'
];

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
    <title>Anomalies - Secrétaire ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/secretaire/dashboard_secretaire.css">
    <link rel="stylesheet" href="../assets/css/secretaire/anomalies.css">
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
                                <i class="fas fa-exclamation-triangle"></i> Gestion des Anomalies
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-tools"></i> 
                                Identifiez et traitez les paiements problématiques. 
                                <span class="text-danger fw-bold"><?= $total_echecs + $total_attente ?> anomalie(s)</span>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <a href="validation_paiements.php" class="btn btn-warning btn-sm me-2">
                                <i class="fas fa-check-double"></i> Validation manuelle
                            </a>
                            <a href="suivi_paiements.php?statut=echec" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-list"></i> Tous les échecs
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="anomalie-stats">
                    <div class="anomalie-stat-card stat-echec">
                        <div class="anom-stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="anom-stat-info">
                            <h4><?= $total_echecs ?></h4>
                            <p>Échecs totaux</p>
                        </div>
                    </div>
                    <div class="anomalie-stat-card stat-attente">
                        <div class="anom-stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="anom-stat-info">
                            <h4><?= $total_attente ?></h4>
                            <p>En attente</p>
                        </div>
                    </div>
                    <div class="anomalie-stat-card stat-bloque">
                        <div class="anom-stat-icon"><i class="fas fa-hourglass-end"></i></div>
                        <div class="anom-stat-info">
                            <h4><?= $bloques_longtemps ?></h4>
                            <p>Bloqués > 1h</p>
                        </div>
                    </div>
                    <div class="anomalie-stat-card stat-montant">
                        <div class="anom-stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="anom-stat-info">
                            <h4>$<?= number_format($montant_echecs, 2) ?></h4>
                            <p>Montant échoué</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-card">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Statut</label>
                                <select name="statut" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="echec" <?= $filtre_statut === 'echec' ? 'selected' : '' ?>>Échecs</option>
                                    <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <label class="filtre-label"><i class="fas fa-filter"></i> Type anomalie</label>
                                <select name="type_anomalie" class="form-select form-select-sm">
                                    <option value="">Tous types</option>
                                    <?php foreach ($types_anomalies as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $filtre_type === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-8 mb-2">
                                <label class="filtre-label"><i class="fas fa-search"></i> Recherche</label>
                                <input type="text" name="recherche" class="form-control form-control-sm" 
                                       placeholder="Nom, matricule, référence..." value="<?= htmlspecialchars($filtre_recherche) ?>">
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-4 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if ($filtre_statut || $filtre_type || $filtre_recherche): ?>
                                    <a href="anomalies.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                                        <i class="fas fa-times"></i> Reset
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Liste des anomalies -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Anomalies détectées</h3>
                        <span class="badge-count badge-danger"><?= $total_anomalies ?> anomalie(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tableAnomalies">
                            <thead>
                                <tr>
                                    <th width="50">#ID</th>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Frais</th>
                                    <th>Montant</th>
                                    <th>Attendu</th>
                                    <th>Type Anomalie</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th width="120">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($anomalies)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-check-circle fa-3x text-success"></i>
                                                <h4 class="mt-3">Aucune anomalie détectée !</h4>
                                                <p class="text-muted">Tous les paiements sont en ordre.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($anomalies as $a): 
                                        // Déterminer le type d'anomalie
                                        $type_anomalie = '';
                                        $type_anomalie_class = '';
                                        
                                        if (!empty($a['statut_api']) && $a['statut_api'] === 'echec') {
                                            $type_anomalie = 'Échec API';
                                            $type_anomalie_class = 'type-api';
                                        } elseif (abs($a['montant_paye'] - ($a['montant_attendu'] ?? $a['montant_paye'])) > 0.01) {
                                            $type_anomalie = 'Montant incorrect';
                                            $type_anomalie_class = 'type-montant';
                                        } elseif (empty($a['operateur'])) {
                                            $type_anomalie = 'Sans opérateur';
                                            $type_anomalie_class = 'type-operateur';
                                        } elseif (strtotime($a['date_paiement']) < strtotime('-1 hour')) {
                                            $type_anomalie = 'Bloqué > 1h';
                                            $type_anomalie_class = 'type-bloque';
                                        } else {
                                            $type_anomalie = 'Statut anormal';
                                            $type_anomalie_class = 'type-autres';
                                        }
                                        
                                        $ecart = $a['montant_paye'] - ($a['montant_attendu'] ?? $a['montant_paye']);
                                    ?>
                                        <tr class="anomalie-row <?= $a['statut'] === 'echec' ? 'row-echec' : 'row-attente' ?> <?= abs($ecart) > 0.01 ? 'row-ecart' : '' ?>">
                                            <td><code>#<?= $a['id_paiement'] ?></code></td>
                                            <td><code class="ref-code"><?= htmlspecialchars(substr($a['reference_transaction'], 0, 12)) ?></code></td>
                                            <td>
                                                <strong><?= htmlspecialchars($a['nom_etudiant']) ?></strong>
                                                <small class="d-block text-muted"><?= htmlspecialchars($a['matricule']) ?></small>
                                            </td>
                                            <td><small><?= htmlspecialchars($a['type_frais']) ?></small></td>
                                            <td>
                                                <strong class="<?= $a['statut'] === 'echec' ? 'text-danger' : 'text-warning' ?>">
                                                    $<?= number_format($a['montant_paye'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php if (abs($ecart) > 0.01): ?>
                                                    <span class="ecart-badge <?= $ecart > 0 ? 'ecart-plus' : 'ecart-moins' ?>">
                                                        $<?= number_format($a['montant_attendu'] ?? $a['montant_paye'], 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="type-anomalie-badge <?= $type_anomalie_class ?>">
                                                    <?= $type_anomalie ?>
                                                </span>
                                                <?php if (!empty($a['operateur'])): ?>
                                                    <small class="d-block text-muted mt-1"><?= htmlspecialchars($a['operateur']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y', strtotime($a['date_paiement'])) ?></small>
                                                <small class="d-block text-muted"><?= date('H:i', strtotime($a['date_paiement'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="status-pill status-<?= $a['statut'] ?>">
                                                    <?= $a['statut'] === 'echec' ? 'Échec' : 'En attente' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="traiterAnomalie(<?= $a['id_paiement'] ?>, '<?= htmlspecialchars(addslashes($a['nom_etudiant'])) ?>', '<?= htmlspecialchars(addslashes($a['matricule'])) ?>')"
                                                        title="Marquer comme traitée">
                                                    <i class="fas fa-check"></i> Traiter
                                                </button>
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
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Légende -->
                <div class="legend-card mt-3">
                    <h6><i class="fas fa-info-circle"></i> Types d'anomalies</h6>
                    <div class="legend-items">
                        <span class="legend-badge type-api">Échec API</span>
                        <span class="legend-badge type-montant">Montant incorrect</span>
                        <span class="legend-badge type-operateur">Sans opérateur</span>
                        <span class="legend-badge type-bloque">Bloqué > 1h</span>
                        <span class="legend-badge type-autres">Statut anormal</span>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/secretaire/dashboard_secretaire.js"></script>
    <script src="../assets/js/secretaire/anomalies.js"></script>
</body>
</html>