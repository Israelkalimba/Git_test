<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// Faculté filtrée (si on vient depuis la page facultés)
$faculte_filter = isset($_GET['faculte']) ? (int)$_GET['faculte'] : 0;

// ========== TRAITEMENT CRUD ==========
$message = '';
$message_type = '';

// AJOUTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom_filiere = trim($_POST['nom_filiere'] ?? '');
    $id_faculte = (int)($_POST['id_faculte'] ?? 0);
    
    if (!empty($nom_filiere) && $id_faculte > 0) {
        try {
            $stmt = $db->prepare("INSERT INTO filieres (nom_filiere, id_faculte) VALUES (:nom, :id_faculte)");
            $stmt->execute(['nom' => $nom_filiere, 'id_faculte' => $id_faculte]);
            $message = "Filière ajoutée avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout.";
            $message_type = 'danger';
        }
    } else {
        $message = "Tous les champs sont obligatoires.";
        $message_type = 'warning';
    }
}

// MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_filiere = (int)($_POST['id_filiere'] ?? 0);
    $nom_filiere = trim($_POST['nom_filiere'] ?? '');
    $id_faculte = (int)($_POST['id_faculte'] ?? 0);
    
    if ($id_filiere > 0 && !empty($nom_filiere) && $id_faculte > 0) {
        try {
            $stmt = $db->prepare("UPDATE filieres SET nom_filiere = :nom, id_faculte = :id_faculte WHERE id_filiere = :id");
            $stmt->execute(['nom' => $nom_filiere, 'id_faculte' => $id_faculte, 'id' => $id_filiere]);
            $message = "Filière modifiée avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la modification.";
            $message_type = 'danger';
        }
    }
}

// SUPPRIMER
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id_filiere = (int)$_GET['id'];
    
    // Vérifier si des étudiants sont liés
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM etudiants WHERE id_filiere = :id");
    $stmt->execute(['id' => $id_filiere]);
    $count_etudiants = $stmt->fetch()['total'] ?? 0;
    
    // Vérifier si des frais sont liés
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM frais WHERE id_filiere = :id");
    $stmt->execute(['id' => $id_filiere]);
    $count_frais = $stmt->fetch()['total'] ?? 0;
    
    $total_lies = $count_etudiants + $count_frais;
    
    if ($total_lies > 0) {
        $message = "Impossible de supprimer cette filière car elle est liée à {$count_etudiants} étudiant(s) et {$count_frais} frais. Supprimez d'abord ces éléments.";
        $message_type = 'warning';
    } else {
        $stmt = $db->prepare("DELETE FROM filieres WHERE id_filiere = :id");
        $stmt->execute(['id' => $id_filiere]);
        $message = "Filière supprimée avec succès !";
        $message_type = 'success';
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========
// Toutes les facultés (pour les selects)
$stmt = $db->query("SELECT * FROM facultes ORDER BY nom_faculte");
$facultes = $stmt->fetchAll();

// Filières avec leurs stats
$sql = "
    SELECT fi.*, 
           f.nom_faculte,
           COUNT(DISTINCT e.id_etudiant) as nb_etudiants,
           COUNT(DISTINCT fr.id_frais) as nb_frais
    FROM filieres fi 
    JOIN facultes f ON fi.id_faculte = f.id_faculte 
    LEFT JOIN etudiants e ON fi.id_filiere = e.id_filiere 
    LEFT JOIN frais fr ON fi.id_filiere = fr.id_filiere 
";
if ($faculte_filter > 0) {
    $sql .= " WHERE fi.id_faculte = :faculte_filter ";
}
$sql .= " GROUP BY fi.id_filiere ORDER BY f.nom_faculte, fi.nom_filiere";

if ($faculte_filter > 0) {
    $stmt = $db->prepare($sql);
    $stmt->execute(['faculte_filter' => $faculte_filter]);
} else {
    $stmt = $db->query($sql);
}
$filieres = $stmt->fetchAll();

// Stats globales
$total_filieres = count($filieres);
$total_etudiants_fil = array_sum(array_column($filieres, 'nb_etudiants'));
$total_frais_fil = array_sum(array_column($filieres, 'nb_frais'));

// Récupérer la faculté filtrée pour affichage
$nom_faculte_filter = '';
if ($faculte_filter > 0) {
    foreach ($facultes as $f) {
        if ($f['id_faculte'] == $faculte_filter) {
            $nom_faculte_filter = $f['nom_faculte'];
            break;
        }
    }
}

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
    <title>Gestion des Filières - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/gestion_filieres.css">
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
                                <i class="fas fa-layer-group"></i> Gestion des Filières
                                <?php if ($faculte_filter > 0): ?>
                                    <span class="filter-badge">
                                        <i class="fas fa-filter"></i> <?= htmlspecialchars($nom_faculte_filter) ?>
                                        <a href="gestion_filieres.php" class="filter-remove" title="Retirer le filtre">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Gérez les filières rattachées à chaque Departement. Chaque étudiant est inscrit dans une filière.
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-primary btn-ajouter" onclick="ouvrirModalAjouter()">
                                <i class="fas fa-plus-circle"></i> Ajouter une filière
                            </button>
                            <a href="gestion_promotions.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-graduation-cap"></i> Promotions
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
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_filieres ?></h4>
                            <p>Filières</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-green">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_etudiants_fil ?></h4>
                            <p>Étudiants</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-orange">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_frais_fil ?></h4>
                            <p>Types de frais</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres rapides par faculté -->
                <div class="filtres-rapides">
                    <span class="filtres-label"><i class="fas fa-filter"></i> Filtrer par Departement :</span>
                    <a href="gestion_filieres.php" class="filtre-btn <?= $faculte_filter === 0 ? 'active' : '' ?>">
                        Toutes
                    </a>
                    <?php foreach ($facultes as $fac): ?>
                        <a href="?faculte=<?= $fac['id_faculte'] ?>" 
                           class="filtre-btn <?= $faculte_filter === (int)$fac['id_faculte'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($fac['nom_faculte']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Tableau des filières -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Liste des filières</h3>
                        <div class="table-actions">
                            <div class="search-table">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchTable" placeholder="Rechercher une filière..." class="form-control form-control-sm">
                            </div>
                            <span class="badge-count badge-info"><?= $total_filieres ?> filière(s)</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table" id="tableFilieres">
                            <thead>
                                <tr>
                                    <th width="70">#ID</th>
                                    <th>Nom de la filière</th>
                                    <th width="200">Departement</th>
                                    <th width="100">Étudiants</th>
                                    <th width="100">Frais</th>
                                    <th width="180">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($filieres)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-layer-group fa-3x"></i>
                                                <h4 class="mt-3">Aucune filière enregistrée</h4>
                                                <p class="text-muted">
                                                    <?= $faculte_filter > 0 ? "Aucune filière trouvée pour cette faculté." : "Commencez par ajouter votre première filière." ?>
                                                </p>
                                                <button class="btn btn-primary" onclick="ouvrirModalAjouter()">
                                                    <i class="fas fa-plus-circle"></i> Ajouter une filière
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($filieres as $filiere): ?>
                                        <tr>
                                            <td><span class="id-badge">#<?= $filiere['id_filiere'] ?></span></td>
                                            <td>
                                                <div class="filiere-info">
                                                    <div class="filiere-icon-small">
                                                        <i class="fas fa-layer-group"></i>
                                                    </div>
                                                    <strong><?= htmlspecialchars($filiere['nom_filiere']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="faculte-badge">
                                                    <i class="fas fa-university"></i>
                                                    <?= htmlspecialchars($filiere['nom_faculte']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="count-badge <?= $filiere['nb_etudiants'] > 0 ? 'badge-blue' : 'badge-gray' ?>">
                                                    <?= $filiere['nb_etudiants'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="count-badge <?= $filiere['nb_frais'] > 0 ? 'badge-green' : 'badge-gray' ?>">
                                                    <?= $filiere['nb_frais'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-action" 
                                                            onclick="ouvrirModalModifier(
                                                                <?= $filiere['id_filiere'] ?>, 
                                                                '<?= htmlspecialchars(addslashes($filiere['nom_filiere'])) ?>', 
                                                                <?= $filiere['id_faculte'] ?>
                                                            )"
                                                            title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="configuration_frais.php?filiere=<?= $filiere['id_filiere'] ?>" 
                                                       class="btn btn-outline-warning btn-action" 
                                                       title="Configurer les frais">
                                                        <i class="fas fa-cog"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger btn-action" 
                                                            onclick="confirmerSuppression(
                                                                <?= $filiere['id_filiere'] ?>, 
                                                                '<?= htmlspecialchars(addslashes($filiere['nom_filiere'])) ?>', 
                                                                <?= $filiere['nb_etudiants'] ?>,
                                                                <?= $filiere['nb_frais'] ?>
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
            </main>
        </div>
    </div>

    <!-- ========== MODAL AJOUTER ========== -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle text-primary"></i> Ajouter une filière
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="form-group mb-3">
                            <label for="nomFiliereAjout" class="form-label">
                                <i class="fas fa-layer-group"></i> Nom de la filière <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg" id="nomFiliereAjout" 
                                   name="nom_filiere" placeholder="Ex: sage femme" required autofocus>
                        </div>
                        
                        <div class="form-group">
                            <label for="faculteAjout" class="form-label">
                                <i class="fas fa-university"></i> Departement de rattachement <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-control-lg" id="faculteAjout" name="id_faculte" required>
                                <option value="">-- Choisir un Departement --</option>
                                <?php foreach ($facultes as $fac): ?>
                                    <option value="<?= $fac['id_faculte'] ?>" <?= $faculte_filter === (int)$fac['id_faculte'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($fac['nom_faculte']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <i class="fas fa-edit text-warning"></i> Modifier la filière
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_filiere" id="modifierIdFiliere">
                        
                        <div class="form-group mb-3">
                            <label for="nomFiliereModif" class="form-label">
                                <i class="fas fa-layer-group"></i> Nom de la filière <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg" id="nomFiliereModif" 
                                   name="nom_filiere" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="faculteModif" class="form-label">
                                <i class="fas fa-university"></i> Faculté de rattachement <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-control-lg" id="faculteModif" name="id_faculte" required>
                                <option value="">-- Choisir une faculté --</option>
                                <?php foreach ($facultes as $fac): ?>
                                    <option value="<?= $fac['id_faculte'] ?>">
                                        <?= htmlspecialchars($fac['nom_faculte']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                    <h5>Êtes-vous sûr de vouloir supprimer cette filière ?</h5>
                    <p class="text-muted" id="supprimerNomFiliere"></p>
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
    <script src="../assets/js/admin/gestion_filieres.js"></script>
</body>
</html>