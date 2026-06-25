<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== TRAITEMENT CRUD ==========
$message = '';
$message_type = '';

// AJOUTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom_promotion = trim($_POST['nom_promotion'] ?? '');
    
    if (!empty($nom_promotion)) {
        try {
            $stmt = $db->prepare("INSERT INTO promotions (nom_promotion) VALUES (:nom)");
            $stmt->execute(['nom' => $nom_promotion]);
            $message = "Promotion ajoutée avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Cette promotion existe déjà.";
            } else {
                $message = "Erreur lors de l'ajout.";
            }
            $message_type = 'danger';
        }
    } else {
        $message = "Le nom de la promotion est obligatoire.";
        $message_type = 'warning';
    }
}

// MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_promotion = (int)($_POST['id_promotion'] ?? 0);
    $nom_promotion = trim($_POST['nom_promotion'] ?? '');
    
    if ($id_promotion > 0 && !empty($nom_promotion)) {
        try {
            $stmt = $db->prepare("UPDATE promotions SET nom_promotion = :nom WHERE id_promotion = :id");
            $stmt->execute(['nom' => $nom_promotion, 'id' => $id_promotion]);
            $message = "Promotion modifiée avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Une promotion avec ce nom existe déjà.";
            } else {
                $message = "Erreur lors de la modification.";
            }
            $message_type = 'danger';
        }
    }
}

// SUPPRIMER
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id_promotion = (int)$_GET['id'];
    
    // Vérifier si des étudiants sont liés
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM etudiants WHERE id_promotion = :id");
    $stmt->execute(['id' => $id_promotion]);
    $count_etudiants = $stmt->fetch()['total'] ?? 0;
    
    // Vérifier si des frais sont liés
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM frais WHERE id_promotion = :id");
    $stmt->execute(['id' => $id_promotion]);
    $count_frais = $stmt->fetch()['total'] ?? 0;
    
    $total_lies = $count_etudiants + $count_frais;
    
    if ($total_lies > 0) {
        $message = "Impossible de supprimer cette promotion car elle est liée à {$count_etudiants} étudiant(s) et {$count_frais} type(s) de frais. Supprimez d'abord ces éléments.";
        $message_type = 'warning';
    } else {
        $stmt = $db->prepare("DELETE FROM promotions WHERE id_promotion = :id");
        $stmt->execute(['id' => $id_promotion]);
        $message = "Promotion supprimée avec succès !";
        $message_type = 'success';
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========
$stmt = $db->query("
    SELECT p.*, 
           COUNT(DISTINCT e.id_etudiant) as nb_etudiants,
           COUNT(DISTINCT fr.id_frais) as nb_frais_config,
           COALESCE(SUM(pa.montant_paye), 0) as total_paye
    FROM promotions p 
    LEFT JOIN etudiants e ON p.id_promotion = e.id_promotion 
    LEFT JOIN frais fr ON p.id_promotion = fr.id_promotion 
    LEFT JOIN paiements pa ON e.id_etudiant = pa.id_etudiant AND pa.statut = 'succes'
    GROUP BY p.id_promotion 
    ORDER BY p.id_promotion
");
$promotions = $stmt->fetchAll();

// Stats
$total_promotions = count($promotions);
$total_etudiants_promo = array_sum(array_column($promotions, 'nb_etudiants'));
$total_frais_config = array_sum(array_column($promotions, 'nb_frais_config'));
$total_montant_promo = array_sum(array_column($promotions, 'total_paye'));

// Notifications pour la navbar
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
    <title>Gestion des Promotions - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/gestion_promotions.css">
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
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-graduation-cap"></i> Gestion des Promotions
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Les promotions définissent les niveaux d'études ( BAC 1, BAC 2, etc.). 
                                Elles sont utilisées pour configurer les frais académiques et classer les étudiants.
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-primary btn-ajouter" onclick="ouvrirModalAjouter()">
                                <i class="fas fa-plus-circle"></i> Ajouter une promotion
                            </button>
                            <a href="configuration_frais.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-cogs"></i> Configurer les frais
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats rapides -->
                <div class="stats-mini-row">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-pink">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_promotions ?></h4>
                            <p>Promotions</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_etudiants_promo ?></h4>
                            <p>Étudiants classés</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-green">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_frais_config ?></h4>
                            <p>Configurations frais</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-purple">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4>$<?= number_format($total_montant_promo, 2, ',', ' ') ?></h4>
                            <p>Total collecté</p>
                        </div>
                    </div>
                </div>

                <!-- Tableau des promotions -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Liste des promotions</h3>
                        <div class="table-actions">
                            <div class="search-table">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchTable" placeholder="Rechercher..." class="form-control form-control-sm">
                            </div>
                            <span class="badge-count badge-pink"><?= $total_promotions ?> promotion(s)</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table" id="tablePromotions">
                            <thead>
                                <tr>
                                    <th width="80">#ID</th>
                                    <th>Nom de la promotion</th>
                                    <th width="120">Étudiants</th>
                                    <th width="130">Config. Frais</th>
                                    <th width="150">Total collecté</th>
                                    <th width="120">Statut</th>
                                    <th width="180">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($promotions)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-graduation-cap fa-3x"></i>
                                                <h4 class="mt-3">Aucune promotion enregistrée</h4>
                                                <p class="text-muted">
                                                    Commencez par ajouter les promotions (BAC 1, BAC 2, etc.).
                                                </p>
                                                <button class="btn btn-primary" onclick="ouvrirModalAjouter()">
                                                    <i class="fas fa-plus-circle"></i> Ajouter une promotion
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($promotions as $promo): ?>
                                        <tr>
                                            <td><span class="id-badge">#<?= $promo['id_promotion'] ?></span></td>
                                            <td>
                                                <div class="promo-info">
                                                    <div class="promo-icon-small">
                                                        <i class="fas fa-graduation-cap"></i>
                                                    </div>
                                                    <strong class="promo-nom"><?= htmlspecialchars($promo['nom_promotion']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="count-badge <?= $promo['nb_etudiants'] > 0 ? 'badge-blue' : 'badge-gray' ?>">
                                                    <i class="fas fa-user-graduate"></i>
                                                    <?= $promo['nb_etudiants'] ?> étudiant(s)
                                                </span>
                                            </td>
                                            <td>
                                                <span class="count-badge <?= $promo['nb_frais_config'] > 0 ? 'badge-green' : 'badge-gray' ?>">
                                                    <i class="fas fa-tag"></i>
                                                    <?= $promo['nb_frais_config'] ?> config(s)
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="montant-collecte">
                                                    $<?= number_format($promo['total_paye'], 2, ',', ' ') ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php if ($promo['nb_etudiants'] > 0): ?>
                                                    <span class="status-pill pill-success">
                                                        <i class="fas fa-check-circle"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-pill pill-gray">
                                                        <i class="fas fa-circle"></i> En attente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-action" 
                                                            onclick="ouvrirModalModifier(
                                                                <?= $promo['id_promotion'] ?>, 
                                                                '<?= htmlspecialchars(addslashes($promo['nom_promotion'])) ?>'
                                                            )"
                                                            title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="configuration_frais.php?promotion=<?= $promo['id_promotion'] ?>" 
                                                       class="btn btn-outline-warning btn-action" 
                                                       title="Configurer les frais">
                                                        <i class="fas fa-cog"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger btn-action" 
                                                            onclick="confirmerSuppression(
                                                                <?= $promo['id_promotion'] ?>, 
                                                                '<?= htmlspecialchars(addslashes($promo['nom_promotion'])) ?>', 
                                                                <?= $promo['nb_etudiants'] ?>,
                                                                <?= $promo['nb_frais_config'] ?>
                                                            )"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Info supplémentaire -->
                <div class="info-supplementaire">
                    <div class="info-card-petite">
                        <div class="info-card-icon">
                            <i class="fas fa-lightbulb text-warning"></i>
                        </div>
                        <div class="info-card-text">
                            <h5>Comment ça fonctionne ?</h5>
                            <p>Les promotions sont utilisées pour définir les frais académiques par niveau. 
                               Par exemple, une « BAC 2 » en « Informatique » peut avoir des frais différents 
                               d'une « BAC 3 » dans la même filière. Configurez les frais après avoir créé vos promotions.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ========== MODAL AJOUTER ========== -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle text-success"></i> Ajouter une promotion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="form-group">
                            <label for="nomPromotionAjout" class="form-label">
                                <i class="fas fa-graduation-cap"></i> Nom de la promotion <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg" id="nomPromotionAjout" 
                                   name="nom_promotion" 
                                   placeholder="Ex: BAC 1, BAC 2, Licence 3, Master 1..." 
                                   required autofocus>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Utilisez des noms clairs : BAC 1, BAC 2, Licence 1, Master 1, etc.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL MODIFIER ========== -->
    <div class="modal fade" id="modalModifier" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit text-warning"></i> Modifier la promotion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_promotion" id="modifierIdPromotion">
                        <div class="form-group">
                            <label for="nomPromotionModif" class="form-label">
                                <i class="fas fa-graduation-cap"></i> Nom de la promotion <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg" id="nomPromotionModif" 
                                   name="nom_promotion" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL SUPPRESSION ========== -->
    <div class="modal fade" id="modalSupprimer" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle"></i> Confirmation de suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                    <h5>Êtes-vous sûr de vouloir supprimer cette promotion ?</h5>
                    <p class="text-muted" id="supprimerNomPromotion"></p>
                    <div class="alert alert-warning" id="alertElementsLies" style="display:none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="messageElementsLies"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <a href="#" class="btn btn-danger" id="btnConfirmerSuppression">
                        <i class="fas fa-trash-alt"></i> Supprimer définitivement
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/gestion_promotions.js"></script>
</body>
</html>