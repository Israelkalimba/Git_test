<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;
$admin_email = '';

// ========== RÉCUPÉRATION DES INFOS DU PROFIL ==========
$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = :id");
$stmt->execute(['id' => $admin_id]);
$admin = $stmt->fetch();

if ($admin) {
    $admin_nom = $admin['nom'];
    $admin_email = $admin['email'];
    $admin_role = $admin['role'];
    $admin_created = $admin['created_at'];
}

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// Modifier le profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nouveau_nom = trim($_POST['nom'] ?? '');
    $nouveau_email = trim($_POST['email'] ?? '');
    
    $errors = [];
    if (empty($nouveau_nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($nouveau_email) || !filter_var($nouveau_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    
    // Vérifier si l'email n'est pas déjà utilisé par un autre utilisateur
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM utilisateurs WHERE email = :email AND id_utilisateur != :id");
        $stmt->execute(['email' => $nouveau_email, 'id' => $admin_id]);
        if ($stmt->fetch()['total'] > 0) {
            $errors[] = "Cet email est déjà utilisé par un autre compte.";
        }
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE utilisateurs SET nom = :nom, email = :email WHERE id_utilisateur = :id");
        $stmt->execute(['nom' => $nouveau_nom, 'email' => $nouveau_email, 'id' => $admin_id]);
        
        // Mettre à jour la session
        $_SESSION['user_nom'] = $nouveau_nom;
        $admin_nom = $nouveau_nom;
        $admin_email = $nouveau_email;
        
        // Journaliser
        $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'modification', 'mise_a_jour_profil', :desc, :ip)");
        $stmt->execute([
            'uid' => $admin_id,
            'desc' => "Profil administrateur mis à jour - Nom: {$nouveau_nom}, Email: {$nouveau_email}",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        
        $message = "✅ Profil mis à jour avec succès !";
        $message_type = 'success';
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

// Changer le mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $ancien_mdp = $_POST['ancien_mdp'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';
    
    $errors = [];
    
    // Vérifier l'ancien mot de passe
    $hashed_ancien = hash('sha256', $ancien_mdp);
    if ($hashed_ancien !== $admin['mot_de_passe']) {
        $errors[] = "L'ancien mot de passe est incorrect.";
    }
    
    if (strlen($nouveau_mdp) < 6) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    }
    
    if ($nouveau_mdp !== $confirmer_mdp) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    if ($ancien_mdp === $nouveau_mdp) {
        $errors[] = "Le nouveau mot de passe doit être différent de l'ancien.";
    }
    
    if (empty($errors)) {
        $hashed_nouveau = hash('sha256', $nouveau_mdp);
        $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id_utilisateur = :id");
        $stmt->execute(['mdp' => $hashed_nouveau, 'id' => $admin_id]);
        
        // Journaliser
        $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'securite', 'changement_mot_de_passe', :desc, :ip)");
        $stmt->execute([
            'uid' => $admin_id,
            'desc' => "Mot de passe administrateur modifié",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        
        $message = "🔒 Mot de passe changé avec succès ! Veuillez vous reconnecter.";
        $message_type = 'success';
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

// ========== STATISTIQUES PERSONNELLES ==========
// Connexions aujourd'hui
$stmt = $db->prepare("SELECT COUNT(*) as total FROM audit_log WHERE id_utilisateur = :id AND type_action = 'connexion' AND DATE(date_action) = CURDATE()");
$stmt->execute(['id' => $admin_id]);
$connexions_aujourdhui = $stmt->fetch()['total'] ?? 0;

// Total actions
$stmt = $db->prepare("SELECT COUNT(*) as total FROM audit_log WHERE id_utilisateur = :id");
$stmt->execute(['id' => $admin_id]);
$total_actions = $stmt->fetch()['total'] ?? 0;

// Dernière connexion
$stmt = $db->prepare("SELECT date_action FROM audit_log WHERE id_utilisateur = :id AND type_action = 'connexion' ORDER BY date_action DESC LIMIT 1,1");
$stmt->execute(['id' => $admin_id]);
$derniere_connexion = $stmt->fetch()['date_action'] ?? null;

// Résolutions d'anomalies
$stmt = $db->prepare("SELECT COUNT(*) as total FROM audit_log WHERE id_utilisateur = :id AND action = 'resolution_anomalie'");
$stmt->execute(['id' => $admin_id]);
$anomalies_resolues = $stmt->fetch()['total'] ?? 0;

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
    <title>Mon Profil - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/profil.css">
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
                    <h1 class="page-title">
                        <i class="fas fa-user-circle"></i> Mon Profil
                    </h1>
                    <p class="page-subtitle">
                        <i class="fas fa-info-circle"></i> 
                        Gérez vos informations personnelles et votre sécurité.
                    </p>
                </div>

                <!-- Message -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="profil-grid">
                    <!-- Carte Profil -->
                    <div class="profil-card">
                        <div class="profil-cover"></div>
                        <div class="profil-avatar-wrapper">
                            <div class="profil-avatar">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="profil-status online"></div>
                        </div>
                        <div class="profil-card-body text-center">
                            <h2><?= htmlspecialchars($admin_nom) ?></h2>
                            <p class="profil-role">
                                <span class="role-badge role-admin">
                                    <i class="fas fa-user-shield"></i> Super Administrateur
                                </span>
                            </p>
                            <p class="profil-email">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($admin_email) ?>
                            </p>
                            <p class="profil-date">
                                <i class="fas fa-calendar-alt"></i> Membre depuis le <?= date('d/m/Y', strtotime($admin_created ?? 'now')) ?>
                            </p>
                        </div>
                        <div class="profil-card-footer">
                            <div class="profil-stats-row">
                                <div class="profil-stat-item">
                                    <h4><?= $connexions_aujourdhui ?></h4>
                                    <p>Connexions aujourd'hui</p>
                                </div>
                                <div class="profil-stat-item">
                                    <h4><?= $total_actions ?></h4>
                                    <p>Actions totales</p>
                                </div>
                                <div class="profil-stat-item">
                                    <h4><?= $anomalies_resolues ?></h4>
                                    <p>Anomalies résolues</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne droite -->
                    <div class="profil-details">
                        <!-- Informations personnelles -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><i class="fas fa-user-edit"></i> Informations personnelles</h3>
                            </div>
                            <div class="settings-card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i> Nom complet
                                        </label>
                                        <input type="text" name="nom" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($admin_nom) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-envelope"></i> Adresse email
                                        </label>
                                        <input type="email" name="email" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($admin_email) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-shield-alt"></i> Rôle
                                        </label>
                                        <input type="text" class="form-control form-control-lg" 
                                               value="Super Administrateur" readonly disabled>
                                        <small class="text-muted">Le rôle ne peut pas être modifié depuis cette interface.</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Mettre à jour le profil
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Changer mot de passe -->
                        <div class="settings-card mt-4">
                            <div class="settings-card-header">
                                <h3><i class="fas fa-lock"></i> Changer le mot de passe</h3>
                            </div>
                            <div class="settings-card-body">
                                <form method="POST" action="" id="formPassword">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-key"></i> Mot de passe actuel
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="ancien_mdp" class="form-control form-control-lg" 
                                                   placeholder="••••••••" required id="ancienMdp">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" 
                                                    data-target="ancienMdp">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i> Nouveau mot de passe
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="nouveau_mdp" class="form-control form-control-lg" 
                                                   placeholder="••••••••" required id="nouveauMdp" minlength="6">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" 
                                                    data-target="nouveauMdp">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Minimum 6 caractères</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-check-circle"></i> Confirmer le mot de passe
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="confirmer_mdp" class="form-control form-control-lg" 
                                                   placeholder="••••••••" required id="confirmerMdp">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" 
                                                    data-target="confirmerMdp">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Indicateur de force -->
                                    <div class="password-strength mb-3" id="passwordStrength" style="display:none;">
                                        <div class="strength-bar">
                                            <div class="strength-fill" id="strengthFill"></div>
                                        </div>
                                        <small class="strength-text" id="strengthText"></small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning" id="btnChangePassword">
                                        <i class="fas fa-key"></i> Changer le mot de passe
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Dernière activité -->
                        <div class="settings-card mt-4">
                            <div class="settings-card-header">
                                <h3><i class="fas fa-history"></i> Dernière activité</h3>
                            </div>
                            <div class="settings-card-body">
                                <?php if ($derniere_connexion): ?>
                                    <div class="activity-item-profil">
                                        <div class="activity-dot-profil bg-green"></div>
                                        <div>
                                            <strong>Dernière connexion</strong>
                                            <p class="text-muted small mb-0">
                                                <?= date('d/m/Y H:i:s', strtotime($derniere_connexion)) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Aucune activité enregistrée.</p>
                                <?php endif; ?>
                                
                                <div class="activity-item-profil mt-3">
                                    <div class="activity-dot-profil bg-blue"></div>
                                    <div>
                                        <strong>Session actuelle</strong>
                                        <p class="text-muted small mb-0">
                                            Connecté depuis <?= date('H:i:s') ?> | IP: <?= $_SERVER['REMOTE_ADDR'] ?? 'N/A' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/profil.js"></script>
</body>
</html>