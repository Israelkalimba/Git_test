<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== TRAITEMENT DES ACTIONS CRUD ==========
$message = '';
$message_type = '';

// AJOUTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom_faculte = trim($_POST['nom_faculte'] ?? '');
    
    if (!empty($nom_faculte)) {
        try {
            $stmt = $db->prepare("INSERT INTO facultes (nom_faculte) VALUES (:nom)");
            $stmt->execute(['nom' => $nom_faculte]);
            $message = "Departement ajouté avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Ce Departement existe déjà.";
            } else {
                $message = "Erreur lors de l'ajout : " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    } else {
        $message = "Le nom du Departement est obligatoire.";
        $message_type = 'warning';
    }
}

// MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_faculte = (int)($_POST['id_faculte'] ?? 0);
    $nom_faculte = trim($_POST['nom_faculte'] ?? '');
    
    if ($id_faculte > 0 && !empty($nom_faculte)) {
        try {
            $stmt = $db->prepare("UPDATE facultes SET nom_faculte = :nom WHERE id_faculte = :id");
            $stmt->execute(['nom' => $nom_faculte, 'id' => $id_faculte]);
            $message = "Departement modifiée avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Un Departement avec ce nom existe déjà.";
            } else {
                $message = "Erreur lors de la modification.";
            }
            $message_type = 'danger';
        }
    }
}

// SUPPRIMER
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id_faculte = (int)$_GET['id'];
    
    // Vérifier si des filières sont liées
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM filieres WHERE id_faculte = :id");
    $stmt->execute(['id' => $id_faculte]);
    $count = $stmt->fetch()['total'] ?? 0;
    
    if ($count > 0) {
        $message = "Impossible de supprimer ce Departement car il contient {$count} filière(s). Supprimez d'abord les filières associées.";
        $message_type = 'warning';
    } else {
        $stmt = $db->prepare("DELETE FROM facultes WHERE id_faculte = :id");
        $stmt->execute(['id' => $id_faculte]);
        $message = "Departement supprimé avec succès !";
        $message_type = 'success';
    }
}

// ========== RÉCUPÉRATION DES FACULTÉS ==========
$stmt = $db->query("
    SELECT f.*, 
           COUNT(DISTINCT fi.id_filiere) as nb_filieres,
           COUNT(DISTINCT e.id_etudiant) as nb_etudiants
    FROM facultes f 
    LEFT JOIN filieres fi ON f.id_faculte = fi.id_faculte 
    LEFT JOIN etudiants e ON fi.id_filiere = e.id_filiere 
    GROUP BY f.id_faculte 
    ORDER BY f.nom_faculte
");
$facultes = $stmt->fetchAll();

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
    <title>Gestion des Facultés - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS de base du dashboard -->
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <!-- CSS spécifique à la gestion des facultés -->
    <link rel="stylesheet" href="../assets/css/admin/gestion_facultes.css">
</head>
<body>
    <div class="admin-layout">
        <!-- SIDEBAR -->
        <?php include 'includes/sidebar_admin.php'; ?>

        <!-- CONTENU PRINCIPAL -->
        <div class="main-content">
            <!-- NAVBAR -->
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_admin.php'; 
            ?>

            <!-- CONTENU DE LA PAGE -->
            <main class="dashboard-content">
                <!-- En-tête -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <h1 class="page-title">
                                <i class="fas fa-university"></i> Gestion des Départements
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Gérez les Départements de l'établissement. Les filières et promotions sont rattachées aux Departements.
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-primary btn-ajouter" onclick="ouvrirModalAjouter()">
                                <i class="fas fa-plus-circle"></i> Ajouter un Departement
                            </button>
                            <a href="gestion_filieres.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-layer-group"></i> Gérer les filières
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
                        <div class="stat-mini-icon bg-blue">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= count($facultes) ?></h4>
                            <p>Département</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-green">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-mini-info">
                            <?php
                            $total_filieres = array_sum(array_column($facultes, 'nb_filieres'));
                            ?>
                            <h4><?= $total_filieres ?></h4>
                            <p>Filières totales</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-purple">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-mini-info">
                            <?php
                            $total_etudiants_fac = array_sum(array_column($facultes, 'nb_etudiants'));
                            ?>
                            <h4><?= $total_etudiants_fac ?></h4>
                            <p>Étudiants</p>
                        </div>
                    </div>
                </div>

                <!-- Tableau des facultés -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Liste des départements</h3>
                        <div class="table-actions">
                            <div class="search-table">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchTable" placeholder="Rechercher un Departement..." class="form-control form-control-sm">
                            </div>
                            <span class="badge-count badge-info"><?= count($facultes) ?> Département(s)</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table" id="tableFacultes">
                            <thead>
                                <tr>
                                    <th width="80">#ID</th>
                                    <th>Nom du Departement</th>
                                    <th width="120">Filières</th>
                                    <th width="120">Étudiants</th>
                                    <th width="150">Date de création</th>
                                    <th width="180">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($facultes)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-university fa-3x"></i>
                                                <h4 class="mt-3">Aucun Departement enregistré</h4>
                                                <p class="text-muted">Commencez par ajouter votre premier Departement.</p>
                                                <button class="btn btn-primary" onclick="ouvrirModalAjouter()">
                                                    <i class="fas fa-plus-circle"></i> Ajouter un Departement
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($facultes as $faculte): ?>
                                        <tr>
                                            <td><span class="id-badge">#<?= $faculte['id_faculte'] ?></span></td>
                                            <td>
                                                <div class="faculte-info">
                                                    <div class="faculte-icon-small">
                                                        <i class="fas fa-university"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="faculte-nom"><?= htmlspecialchars($faculte['nom_faculte']) ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="count-badge <?= $faculte['nb_filieres'] > 0 ? 'badge-green' : 'badge-gray' ?>">
                                                    <?= $faculte['nb_filieres'] ?> filière(s)
                                                </span>
                                            </td>
                                            <td>
                                                <span class="count-badge <?= $faculte['nb_etudiants'] > 0 ? 'badge-blue' : 'badge-gray' ?>">
                                                    <?= $faculte['nb_etudiants'] ?> étudiant(s)
                                                </span>
                                            </td>
                                            <td><small class="text-muted">Ajoutée manuellement</small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-action" 
                                                            onclick="ouvrirModalModifier(<?= $faculte['id_faculte'] ?>, '<?= htmlspecialchars(addslashes($faculte['nom_faculte'])) ?>')"
                                                            title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="gestion_filieres.php?faculte=<?= $faculte['id_faculte'] ?>" 
                                                       class="btn btn-outline-info btn-action" 
                                                       title="Voir les filières">
                                                        <i class="fas fa-layer-group"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger btn-action" 
                                                            onclick="confirmerSuppression(<?= $faculte['id_faculte'] ?>, '<?= htmlspecialchars(addslashes($faculte['nom_faculte'])) ?>', <?= $faculte['nb_filieres'] ?>)"
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
            </main>
        </div>
    </div>

    <!-- ========== MODAL AJOUTER ========== -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle text-primary"></i> Ajouter un Departement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="formAjouter">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="form-group">
                            <label for="nomFaculteAjout" class="form-label">
                                <i class="fas fa-university"></i> Nom du Departement <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg" id="nomFaculteAjout" 
                                   name="nom_faculte" placeholder="Ex: Departement des Sciences informatique" required autofocus>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Le nom doit être unique.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
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
                        <i class="fas fa-edit text-warning"></i> Modifier le Departement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="formModifier">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_faculte" id="modifierIdFaculte">
                        <div class="form-group">
                            <label for="nomFaculteModif" class="form-label">
                                <i class="fas fa-university"></i> Nom du Departement <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg" id="nomFaculteModif" 
                                   name="nom_faculte" required>
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

    <!-- ========== MODAL CONFIRMATION SUPPRESSION ========== -->
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
                    <h5>Êtes-vous sûr de vouloir supprimer ce Departement ?</h5>
                    <p class="text-muted" id="supprimerNomFaculte"></p>
                    <div class="alert alert-warning" id="alertFilieres" style="display:none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="messageFilieres"></span>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS de base du dashboard -->
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <!-- JS spécifique à la gestion des facultés -->
    <script src="../assets/js/admin/gestion_facultes.js"></script>
</body>
</html>