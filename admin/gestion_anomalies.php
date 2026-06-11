<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// Résoudre une anomalie (forcer succès)
if (isset($_GET['action']) && $_GET['action'] === 'forcer_succes' && isset($_GET['id'])) {
    $id_paiement = (int)$_GET['id'];
    
    try {
        $db->beginTransaction();
        
        // Récupérer les infos du paiement
        $stmt = $db->prepare("SELECT p.*, u.nom, u.email FROM paiements p JOIN etudiants e ON p.id_etudiant = e.id_etudiant JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur WHERE p.id_paiement = :id");
        $stmt->execute(['id' => $id_paiement]);
        $paiement = $stmt->fetch();
        
        if ($paiement) {
            // Mettre à jour le statut
            $stmt = $db->prepare("UPDATE paiements SET statut = 'succes' WHERE id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
            
            // Mettre à jour la transaction mobile money
            $stmt = $db->prepare("UPDATE transaction_mobile_money SET statut_api = 'succes' WHERE id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
            
            // Notifier l'admin
            $stmt = $db->prepare("INSERT INTO notifications (id_utilisateur, message, statut) VALUES (:admin_id, :msg, 'non_lu')");
            $stmt->execute([
                'admin_id' => $admin_id,
                'msg' => "Anomalie résolue manuellement : Paiement #{$id_paiement} de {$paiement['nom']} forcé en succès."
            ]);
            
            // Ajouter au journal d'audit
            $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'modification', 'resolution_anomalie', :desc, :ip)");
            $stmt->execute([
                'uid' => $admin_id,
                'desc' => "Anomalie #{$id_paiement} résolue - Paiement forcé en succès pour {$paiement['nom']} ({$paiement['email']}) - {$paiement['montant_paye']}$",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            
            $db->commit();
            $message = "✅ Anomalie résolue avec succès ! Le paiement #{$id_paiement} est maintenant marqué comme réussi.";
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Forcer échec (annuler un paiement)
if (isset($_GET['action']) && $_GET['action'] === 'forcer_echec' && isset($_GET['id'])) {
    $id_paiement = (int)$_GET['id'];
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT p.*, u.nom FROM paiements p JOIN etudiants e ON p.id_etudiant = e.id_etudiant JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur WHERE p.id_paiement = :id");
        $stmt->execute(['id' => $id_paiement]);
        $paiement = $stmt->fetch();
        
        if ($paiement) {
            $stmt = $db->prepare("UPDATE paiements SET statut = 'echec' WHERE id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
            
            $stmt = $db->prepare("UPDATE transaction_mobile_money SET statut_api = 'echec' WHERE id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
            
            $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'modification', 'annulation_paiement', :desc, :ip)");
            $stmt->execute([
                'uid' => $admin_id,
                'desc' => "Paiement #{$id_paiement} annulé manuellement pour {$paiement['nom']} - {$paiement['montant_paye']}$",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            
            $db->commit();
            $message = "⚠️ Paiement #{$id_paiement} marqué comme échec.";
            $message_type = 'warning';
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Rembourser (inverser le paiement)
if (isset($_GET['action']) && $_GET['action'] === 'rembourser' && isset($_GET['id'])) {
    $id_paiement = (int)$_GET['id'];
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT p.*, u.nom, u.email FROM paiements p JOIN etudiants e ON p.id_etudiant = e.id_etudiant JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur WHERE p.id_paiement = :id");
        $stmt->execute(['id' => $id_paiement]);
        $paiement = $stmt->fetch();
        
        if ($paiement && $paiement['statut'] === 'succes') {
            // Marquer comme remboursé
            $stmt = $db->prepare("UPDATE paiements SET statut = 'echec' WHERE id_paiement = :id");
            $stmt->execute(['id' => $id_paiement]);
            
            // Ajouter une note
            $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'modification', 'remboursement', :desc, :ip)");
            $stmt->execute([
                'uid' => $admin_id,
                'desc' => "REMBOURSEMENT : Paiement #{$id_paiement} de {$paiement['nom']} ({$paiement['email']}) - {$paiement['montant_paye']}$ remboursé",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            
            $db->commit();
            $message = "💰 Remboursement traité pour le paiement #{$id_paiement} ({$paiement['nom']} - {$paiement['montant_paye']}$).";
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// ========== FILTRES ==========
$filtre_statut = $_GET['statut'] ?? '';
$filtre_type = $_GET['type'] ?? '';
$filtre_recherche = trim($_GET['recherche'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$par_page = 20;
$offset = ($page - 1) * $par_page;

// ========== RÉCUPÉRATION DES ANOMALIES ==========

// Clause WHERE
$where = "WHERE (p.statut = 'echec' OR p.statut = 'en_attente')";
$params = [];

if (!empty($filtre_statut) && in_array($filtre_statut, ['echec', 'en_attente'])) {
    $where = "WHERE p.statut = :statut";
    $params['statut'] = $filtre_statut;
}

if (!empty($filtre_type)) {
    switch ($filtre_type) {
        case 'double_paiement':
            $where .= " AND p.reference_transaction IN (SELECT reference_transaction FROM paiements GROUP BY reference_transaction HAVING COUNT(*) > 1)";
            break;
        case 'montant_incorrect':
            $where .= " AND p.montant_paye != (SELECT f.montant FROM frais f WHERE f.id_frais = p.id_frais)";
            break;
        case 'api_echec':
            $where .= " AND EXISTS (SELECT 1 FROM transaction_mobile_money tmm WHERE tmm.id_paiement = p.id_paiement AND tmm.statut_api = 'echec')";
            break;
    }
}

if (!empty($filtre_recherche)) {
    $where .= " AND (u.nom LIKE :recherche OR e.matricule LIKE :recherche2 OR p.reference_transaction LIKE :recherche3)";
    $params['recherche'] = "%{$filtre_recherche}%";
    $params['recherche2'] = "%{$filtre_recherche}%";
    $params['recherche3'] = "%{$filtre_recherche}%";
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

$stmt = $db->query("SELECT COUNT(*) as total FROM paiements WHERE statut = 'echec' AND DATE(date_paiement) = CURDATE()");
$echecs_aujourdhui = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE statut = 'echec'");
$montant_echecs = $stmt->fetch()['total'] ?? 0;

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Anomalies - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/gestion_anomalies.css">
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
                                <i class="fas fa-exclamation-triangle"></i> Gestion des Anomalies
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-tools"></i> 
                                Traitez les paiements échoués, les litiges et les cas particuliers.
                                <span class="text-danger fw-bold"><?= $total_echecs + $total_attente ?> en attente</span>
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <a href="?statut=en_attente" class="btn btn-outline-warning btn-sm me-2">
                                <i class="fas fa-clock"></i> En attente (<?= $total_attente ?>)
                            </a>
                            <a href="?statut=echec" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-times-circle"></i> Échecs (<?= $total_echecs ?>)
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

                <!-- Stats anomalies -->
                <div class="anomaly-stats">
                    <div class="anomaly-stat-card stat-echec">
                        <div class="anomaly-stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="anomaly-stat-info">
                            <h4><?= $total_echecs ?></h4>
                            <p>Échecs totaux</p>
                        </div>
                    </div>
                    <div class="anomaly-stat-card stat-attente">
                        <div class="anomaly-stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="anomaly-stat-info">
                            <h4><?= $total_attente ?></h4>
                            <p>En attente</p>
                        </div>
                    </div>
                    <div class="anomaly-stat-card stat-today">
                        <div class="anomaly-stat-icon"><i class="fas fa-calendar-day"></i></div>
                        <div class="anomaly-stat-info">
                            <h4><?= $echecs_aujourdhui ?></h4>
                            <p>Aujourd'hui</p>
                        </div>
                    </div>
                    <div class="anomaly-stat-card stat-montant">
                        <div class="anomaly-stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="anomaly-stat-info">
                            <h4>$<?= number_format($montant_echecs, 2) ?></h4>
                            <p>Montant échoué</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-section">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-3 mb-2">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Statut</label>
                                <select name="statut" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="echec" <?= $filtre_statut === 'echec' ? 'selected' : '' ?>>Échecs</option>
                                    <option value="en_attente" <?= $filtre_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3 mb-2">
                                <label class="filtre-label"><i class="fas fa-filter"></i> Type</label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="">Tous types</option>
                                    <option value="double_paiement" <?= $filtre_type === 'double_paiement' ? 'selected' : '' ?>>Doublons</option>
                                    <option value="montant_incorrect" <?= $filtre_type === 'montant_incorrect' ? 'selected' : '' ?>>Montant incorrect</option>
                                    <option value="api_echec" <?= $filtre_type === 'api_echec' ? 'selected' : '' ?>>Échec API</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 mb-2">
                                <label class="filtre-label"><i class="fas fa-search"></i> Recherche</label>
                                <input type="text" name="recherche" class="form-control form-control-sm" 
                                       placeholder="Nom, matricule, référence..." value="<?= htmlspecialchars($filtre_recherche) ?>">
                            </div>
                            <div class="col-lg-2 col-md-3 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if (!empty($filtre_statut) || !empty($filtre_type) || !empty($filtre_recherche)): ?>
                                    <a href="gestion_anomalies.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
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
                        <table class="table admin-table" id="tableAnomalies">
                            <thead>
                                <tr>
                                    <th width="60">#ID</th>
                                    <th width="130">Référence</th>
                                    <th>Étudiant</th>
                                    <th>Téléphone</th>
                                    <th>Frais</th>
                                    <th width="90">Montant Payé</th>
                                    <th width="90">Attendu</th>
                                    <th width="90">Statut</th>
                                    <th width="100">Date</th>
                                    <th width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($anomalies)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-check-circle fa-3x text-success"></i>
                                                <h4 class="mt-3">Aucune anomalie détectée</h4>
                                                <p class="text-muted">Tous les paiements sont en ordre.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($anomalies as $anomalie): 
                                        $ecart = $anomalie['montant_paye'] - ($anomalie['montant_attendu'] ?? $anomalie['montant_paye']);
                                        $a_ecart = abs($ecart) > 0.01;
                                    ?>
                                        <tr class="anomalie-row <?= $anomalie['statut'] === 'echec' ? 'row-echec' : 'row-attente' ?> <?= $a_ecart ? 'row-ecart' : '' ?>">
                                            <td><code>#<?= $anomalie['id_paiement'] ?></code></td>
                                            <td>
                                                <code class="ref-code"><?= htmlspecialchars(substr($anomalie['reference_transaction'], 0, 12)) ?></code>
                                            </td>
                                            <td>
                                                <div class="etudiant-info-sm">
                                                    <strong><?= htmlspecialchars($anomalie['nom_etudiant']) ?></strong>
                                                    <small class="d-block text-muted">
                                                        <?= htmlspecialchars($anomalie['matricule']) ?> | 
                                                        <?= htmlspecialchars($anomalie['nom_filiere']) ?> - 
                                                        <?= htmlspecialchars($anomalie['nom_promotion']) ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($anomalie['telephone'] ?? $anomalie['numero_telephone'] ?? 'N/A') ?></code>
                                                <?php if (!empty($anomalie['operateur'])): ?>
                                                    <small class="d-block operateur-text"><?= htmlspecialchars($anomalie['operateur']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= htmlspecialchars($anomalie['type_frais']) ?></small></td>
                                            <td>
                                                <strong class="<?= $anomalie['statut'] === 'succes' ? 'text-success' : 'text-danger' ?>">
                                                    $<?= number_format($anomalie['montant_paye'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php if ($a_ecart): ?>
                                                    <span class="ecart-badge <?= $ecart > 0 ? 'ecart-plus' : 'ecart-moins' ?>">
                                                        <?= $ecart > 0 ? '+' : '' ?>$<?= number_format(abs($ecart), 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">$<?= number_format($anomalie['montant_attendu'] ?? $anomalie['montant_paye'], 2) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-pill pill-<?= $anomalie['statut'] === 'echec' ? 'danger' : 'warning' ?>">
                                                    <i class="fas fa-<?= $anomalie['statut'] === 'echec' ? 'times-circle' : 'clock' ?>"></i>
                                                    <?= $anomalie['statut'] === 'echec' ? 'Échec' : 'En attente' ?>
                                                </span>
                                                <?php if (!empty($anomalie['statut_api'])): ?>
                                                    <small class="d-block text-muted mt-1">API: <?= htmlspecialchars($anomalie['statut_api']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($anomalie['date_paiement'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=forcer_succes&id=<?= $anomalie['id_paiement'] ?>" 
                                                       class="btn btn-success btn-xs" 
                                                       title="Forcer en succès"
                                                       onclick="return confirm('✅ Marquer ce paiement comme RÉUSSI ?\n\nÉtudiant: <?= htmlspecialchars(addslashes($anomalie['nom_etudiant'])) ?>\nMontant: $<?= number_format($anomalie['montant_paye'], 2) ?>\n\nCette action est IRREVERSIBLE.')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="?action=forcer_echec&id=<?= $anomalie['id_paiement'] ?>" 
                                                       class="btn btn-danger btn-xs" 
                                                       title="Forcer en échec"
                                                       onclick="return confirm('❌ Marquer ce paiement comme ÉCHEC ?\n\nCette action est IRREVERSIBLE.')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                    <?php if ($anomalie['statut'] === 'succes'): ?>
                                                        <a href="?action=rembourser&id=<?= $anomalie['id_paiement'] ?>" 
                                                           class="btn btn-warning btn-xs" 
                                                           title="Rembourser"
                                                           onclick="return confirm('💰 Rembourser ce paiement ?\n\nÉtudiant: <?= htmlspecialchars(addslashes($anomalie['nom_etudiant'])) ?>\nMontant: $<?= number_format($anomalie['montant_paye'], 2) ?>\n\nLe statut sera changé en échec.')">
                                                            <i class="fas fa-undo"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="rapports.php?ref=<?= urlencode($anomalie['reference_transaction']) ?>" 
                                                       class="btn btn-info btn-xs" 
                                                       title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
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
                                Page <?= $page ?> sur <?= $total_pages ?> (<?= $total_anomalies ?> anomalies)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Légende -->
                <div class="legend-card mt-3">
                    <h6><i class="fas fa-info-circle"></i> Actions disponibles</h6>
                    <div class="legend-items">
                        <span class="legend-item"><span class="legend-color bg-success"></span> <strong>Forcer Succès</strong> : Valider manuellement un paiement</span>
                        <span class="legend-item"><span class="legend-color bg-danger"></span> <strong>Forcer Échec</strong> : Annuler un paiement</span>
                        <span class="legend-item"><span class="legend-color bg-warning"></span> <strong>Rembourser</strong> : Inverser un paiement réussi</span>
                        <span class="legend-item"><span class="legend-color bg-info"></span> <strong>Voir</strong> : Voir les détails dans les rapports</span>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/gestion_anomalies.js"></script>
</body>
</html>
