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

// AJOUTER UN ADMIN OU SECRÉTAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'secretaire';
    $password = trim($_POST['password'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (!in_array($role, ['admin', 'secretaire'])) $errors[] = "Rôle invalide.";
    if (empty($password) || strlen($password) < 6) $errors[] = "Mot de passe requis (min 6 caractères).";
    
    // Vérifier que l'email n'est pas déjà utilisé
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM utilisateurs WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()['total'] > 0) {
            $errors[] = "Cet email est déjà utilisé par un autre compte.";
        }
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = hash('sha256', $password);
            $stmt = $db->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mdp, :role)");
            $stmt->execute(['nom' => $nom, 'email' => $email, 'mdp' => $hashedPassword, 'role' => $role]);
            
            $role_label = $role === 'admin' ? 'Administrateur' : 'Secrétaire';
            
            // Notification
            $stmt = $db->prepare("INSERT INTO notifications (id_utilisateur, message, statut) VALUES (:admin_id, :msg, 'non_lu')");
            $stmt->execute([
                'admin_id' => $admin_id,
                'msg' => "Nouveau {$role_label} ajouté : {$nom} ({$email})"
            ]);
            
            $message = "{$role_label} {$nom} ajouté avec succès !";
            $message_type = 'success';
            
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout : " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'warning';
    }
}

// MODIFIER UN UTILISATEUR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_utilisateur = (int)($_POST['id_utilisateur'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'secretaire';
    
    if ($id_utilisateur > 0 && !empty($nom) && !empty($email)) {
        try {
            $stmt = $db->prepare("UPDATE utilisateurs SET nom = :nom, email = :email, role = :role WHERE id_utilisateur = :id AND id_utilisateur != :admin_id");
            $stmt->execute(['nom' => $nom, 'email' => $email, 'role' => $role, 'id' => $id_utilisateur, 'admin_id' => $admin_id]);
            
            $message = "Utilisateur modifié avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Cet email est déjà utilisé.";
            } else {
                $message = "Erreur lors de la modification.";
            }
            $message_type = 'danger';
        }
    }
}

// ACTIVER / DÉSACTIVER UN COMPTE
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id_utilisateur = (int)$_GET['id'];
    
    // Récupérer le statut actuel
    $stmt = $db->prepare("SELECT statut_compte FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $id_utilisateur]);
    $user = $stmt->fetch();
    
    if ($user) {
        $nouveau_statut = ($user['statut_compte'] ?? 'actif') === 'actif' ? 'inactif' : 'actif';
        $stmt = $db->prepare("UPDATE utilisateurs SET statut_compte = :statut WHERE id_utilisateur = :id AND id_utilisateur != :admin_id");
        $stmt->execute(['statut' => $nouveau_statut, 'id' => $id_utilisateur, 'admin_id' => $admin_id]);
        
        $message = $nouveau_statut === 'actif' ? "Compte activé avec succès." : "Compte désactivé avec succès.";
        $message_type = $nouveau_statut === 'actif' ? 'success' : 'warning';
    }
}

// RÉINITIALISER LE MOT DE PASSE
if (isset($_GET['action']) && $_GET['action'] === 'reset_password' && isset($_GET['id'])) {
    $id_utilisateur = (int)$_GET['id'];
    
    // Vérifier que ce n'est pas le compte admin principal
    if ($id_utilisateur !== $admin_id) {
        $nouveau_mdp = 'Istam@' . rand(10000, 99999);
        $hashed = hash('sha256', $nouveau_mdp);
        
        $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id_utilisateur = :id");
        $stmt->execute(['mdp' => $hashed, 'id' => $id_utilisateur]);
        
        $message = "Mot de passe réinitialisé. Nouveau mot de passe : <strong>{$nouveau_mdp}</strong>";
        $message_type = 'warning';
    } else {
        $message = "Vous ne pouvez pas réinitialiser votre propre mot de passe depuis cette interface.";
        $message_type = 'danger';
    }
}

// SUPPRIMER UN UTILISATEUR
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id_utilisateur = (int)$_GET['id'];
    
    if ($id_utilisateur === $admin_id) {
        $message = "Vous ne pouvez pas supprimer votre propre compte.";
        $message_type = 'danger';
    } else {
        // Vérifier si c'est un secrétaire et s'il a traité des anomalies
        $stmt = $db->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $id_utilisateur]);
        $user = $stmt->fetch();
        
        if ($user && $user['role'] === 'admin') {
            // Vérifier s'il y a d'autres admins
            $stmt = $db->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'admin' AND id_utilisateur != :id");
            $stmt->execute(['id' => $id_utilisateur]);
            // On peut supprimer un admin s'il y en a d'autres
            
            $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = :id AND id_utilisateur != :admin_id");
            $stmt->execute(['id' => $id_utilisateur, 'admin_id' => $admin_id]);
        } else {
            $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = :id AND role = 'secretaire'");
            $stmt->execute(['id' => $id_utilisateur]);
        }
        
        if ($stmt->rowCount() > 0) {
            $message = "Utilisateur supprimé avec succès !";
            $message_type = 'success';
        } else {
            $message = "Erreur lors de la suppression.";
            $message_type = 'danger';
        }
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========

// Filtres
$filtre_role = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');

// Tous les utilisateurs admin et secrétaire
$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM notifications WHERE id_utilisateur = u.id_utilisateur) as nb_notifications
    FROM utilisateurs u 
    WHERE u.role IN ('admin', 'secretaire')
";
$params = [];

if (!empty($filtre_role) && in_array($filtre_role, ['admin', 'secretaire'])) {
    $sql .= " AND u.role = :role";
    $params['role'] = $filtre_role;
}

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE :search OR u.email LIKE :search2)";
    $params['search'] = "%{$search}%";
    $params['search2'] = "%{$search}%";
}

$sql .= " ORDER BY u.role, u.nom";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

// Stats
$total_admins = count(array_filter($utilisateurs, fn($u) => $u['role'] === 'admin'));
$total_secretaires = count(array_filter($utilisateurs, fn($u) => $u['role'] === 'secretaire'));
$total_actifs = count(array_filter($utilisateurs, fn($u) => ($u['statut_compte'] ?? 'actif') === 'actif'));
$total_inactifs = count($utilisateurs) - $total_actifs;

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();

// Vérifier si la colonne statut_compte existe, sinon l'ajouter
try {
    $db->query("SELECT statut_compte FROM utilisateurs LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE utilisateurs ADD COLUMN statut_compte VARCHAR(20) DEFAULT 'actif' AFTER role");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Personnel - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/gestion_utilisateurs.css">
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
                                <i class="fas fa-users-cog"></i> Gestion du Personnel
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Gérez les comptes Administrateurs et Secrétaires. 
                                <span class="text-warning"><i class="fas fa-shield-alt"></i> Vous ne pouvez pas modifier votre propre compte ici.</span>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-primary btn-ajouter" onclick="ouvrirModalAjouter()">
                                <i class="fas fa-user-plus"></i> Ajouter un personnel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats rapides -->
                <div class="stats-mini-row">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= count($utilisateurs) ?></h4>
                            <p>Total personnel</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-dark">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_admins ?></h4>
                            <p>Administrateurs</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-green">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_secretaires ?></h4>
                            <p>Secrétaires</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-orange">
                            <i class="fas fa-toggle-on"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_actifs ?>/<?= count($utilisateurs) ?></h4>
                            <p>Comptes actifs</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-section">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="filtre-label"><i class="fas fa-tag"></i> Rôle</label>
                                <select name="role" class="form-select form-select-sm">
                                    <option value="">Tous les rôles</option>
                                    <option value="admin" <?= $filtre_role === 'admin' ? 'selected' : '' ?>>Administrateurs</option>
                                    <option value="secretaire" <?= $filtre_role === 'secretaire' ? 'selected' : '' ?>>Secrétaires</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="filtre-label"><i class="fas fa-search"></i> Recherche</label>
                                <input type="text" name="search" class="form-control form-control-sm" 
                                       placeholder="Nom, email..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if (!empty($filtre_role) || !empty($search)): ?>
                                    <a href="gestion_utilisateurs.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times"></i> Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tableau -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Liste du personnel</h3>
                        <span class="badge-count badge-dark"><?= count($utilisateurs) ?> personne(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table" id="tableUtilisateurs">
                            <thead>
                                <tr>
                                    <th width="50">#ID</th>
                                    <th>Nom & Email</th>
                                    <th width="100">Rôle</th>
                                    <th width="110">Statut</th>
                                    <th width="130">Créé le</th>
                                    <th width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($utilisateurs)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-users-cog fa-3x"></i>
                                                <h4 class="mt-3">Aucun personnel trouvé</h4>
                                                <button class="btn btn-primary" onclick="ouvrirModalAjouter()">
                                                    <i class="fas fa-user-plus"></i> Ajouter du personnel
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($utilisateurs as $user): 
                                        $isMe = ($user['id_utilisateur'] === $admin_id);
                                        $isActive = ($user['statut_compte'] ?? 'actif') === 'actif';
                                    ?>
                                        <tr class="<?= $isMe ? 'table-primary' : '' ?> <?= !$isActive ? 'table-inactive' : '' ?>">
                                            <td>
                                                <span class="id-badge">#<?= $user['id_utilisateur'] ?></span>
                                                <?php if ($isMe): ?>
                                                    <span class="badge-moi">Moi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="personnel-info">
                                                    <div class="personnel-avatar <?= $user['role'] === 'admin' ? 'avatar-admin' : 'avatar-secretaire' ?>">
                                                        <i class="fas fa-<?= $user['role'] === 'admin' ? 'user-shield' : 'user-tie' ?>"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($user['nom']) ?></strong>
                                                        <small class="d-block text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="role-badge <?= $user['role'] === 'admin' ? 'role-admin' : 'role-secretaire' ?>">
                                                    <i class="fas fa-<?= $user['role'] === 'admin' ? 'user-shield' : 'user-tie' ?>"></i>
                                                    <?= $user['role'] === 'admin' ? 'Admin' : 'Secrétaire' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-toggle <?= $isActive ? 'status-actif' : 'status-inactif' ?>">
                                                    <span class="status-dot-sm"></span>
                                                    <?= $isActive ? 'Actif' : 'Inactif' ?>
                                                </span>
                                            </td>
                                            <td><small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (!$isMe): ?>
                                                        <button class="btn btn-outline-primary btn-action" 
                                                                onclick="ouvrirModalModifier(<?= htmlspecialchars(json_encode($user)) ?>)"
                                                                title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <a href="?action=toggle_status&id=<?= $user['id_utilisateur'] ?>" 
                                                           class="btn btn-outline-<?= $isActive ? 'warning' : 'success' ?> btn-action" 
                                                           title="<?= $isActive ? 'Désactiver' : 'Activer' ?> le compte"
                                                           onclick="return confirm('<?= $isActive ? 'Désactiver' : 'Activer' ?> le compte de <?= htmlspecialchars(addslashes($user['nom'])) ?> ?')">
                                                            <i class="fas fa-<?= $isActive ? 'toggle-off' : 'toggle-on' ?>"></i>
                                                        </a>
                                                        
                                                        <a href="?action=reset_password&id=<?= $user['id_utilisateur'] ?>" 
                                                           class="btn btn-outline-info btn-action" 
                                                           title="Réinitialiser le mot de passe"
                                                           onclick="return confirm('Réinitialiser le mot de passe de <?= htmlspecialchars(addslashes($user['nom'])) ?> ?')">
                                                            <i class="fas fa-key"></i>
                                                        </a>
                                                        
                                                        <button class="btn btn-outline-danger btn-action" 
                                                                onclick="confirmerSuppression(<?= $user['id_utilisateur'] ?>, '<?= htmlspecialchars(addslashes($user['nom'])) ?>', '<?= $user['role'] ?>')"
                                                                title="Supprimer">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted small">
                                                            <i class="fas fa-info-circle"></i> Votre compte
                                                        </span>
                                                    <?php endif; ?>
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i> Ajouter un membre du personnel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="formAjouter">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: Marie K." required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="marie@istam.ac.cd" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-tag"></i> Rôle <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="secretaire">Secrétaire</option>
                                <option value="admin">Administrateur</option>
                            </select>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Admin</strong> : accès total | 
                                <strong>Secrétaire</strong> : consultation, gestion anomalies
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-lock"></i> Mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="password" id="passwordAjout" class="form-control" 
                                       value="Istam@2024" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="genererMotDePasse()">
                                    <i class="fas fa-dice"></i> Générer
                                </button>
                            </div>
                            <small class="text-muted">Mot de passe par défaut : Istam@2024 (min 6 caractères)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Ajouter
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
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Modifier le personnel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_utilisateur" id="modIdUtilisateur">
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Nom complet</label>
                            <input type="text" name="nom" id="modNom" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" id="modEmail" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-tag"></i> Rôle</label>
                            <select name="role" id="modRole" class="form-select" required>
                                <option value="secretaire">Secrétaire</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Enregistrer
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
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-user-slash fa-4x text-danger mb-3"></i>
                    <h5>Supprimer ce compte ?</h5>
                    <p class="text-muted" id="supprimerInfo"></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Cette action est irréversible. Le compte sera définitivement supprimé.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" class="btn btn-danger" id="btnConfirmerSuppression">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/gestion_utilisateurs.js"></script>
</body>
</html>