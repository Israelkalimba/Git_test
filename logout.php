<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

// Rediriger vers l'accueil si personne n'est connecté
if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$role = $_GET['role'] ?? $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$user_nom = $_SESSION['user_nom'];

$error = '';

// Traitement de la confirmation de déconnexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = "Veuillez saisir votre mot de passe pour quitter.";
    } else {
        $hashed = hash('sha256', $password);
        
        // Vérifier le mot de passe dans la base
        $stmt = $db->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();
        
        if ($user && $user['mot_de_passe'] === $hashed) {
            // 1. Journaliser la déconnexion
            $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'deconnexion', 'logout_confirme', :desc, :ip)");
            $stmt->execute([
                'uid' => $user_id,
                'desc' => "Déconnexion sécurisée de l'utilisateur {$user_nom}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            
            // 2. Détruire la session
            Auth::logout(); // Cette méthode dans Auth.php redirige déjà vers index.php
            exit();
        } else {
            $error = "Mot de passe incorrect. Déconnexion refusée.";
            
            // Journaliser la tentative échouée de déconnexion (Alerte sécurité)
            $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'securite', 'logout_echoue', 'Tentative de déconnexion avec mauvais mot de passe', :ip)");
            $stmt->execute(['uid' => $user_id, 'ip' => $_SERVER['REMOTE_ADDR']]);
        }
    }
}

// Déterminer l'URL de retour en cas d'annulation
$back_url = 'index.php';
if ($role === 'admin') $back_url = 'admin/dashboard.php';
elseif ($role === 'secretaire') $back_url = 'secretaire/dashboard.php';
elseif ($role === 'etudiant') $back_url = 'etudiant/dashboard.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion Sécurisée - ISTAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/logout.css">
</head>
<body class="role-<?= $role ?>">
    <div class="lock-screen">
        <div class="lock-container">
            <div class="user-profile">
                <div class="avatar-wrapper">
                    <?php if ($role === 'admin'): ?>
                        <i class="fas fa-user-shield"></i>
                    <?php elseif ($role === 'secretaire'): ?>
                        <i class="fas fa-user-tie"></i>
                    <?php else: ?>
                        <i class="fas fa-user-graduate"></i>
                    <?php endif; ?>
                </div>
                <h2 class="user-name"><?= htmlspecialchars($user_nom) ?></h2>
                <span class="role-label"><?= ucfirst($role) ?></span>
            </div>

            <div class="logout-form-box">
                <p class="instruction">Confirmez votre mot de passe pour fermer votre session en toute sécurité.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <input type="password" name="password" class="form-control lock-input" 
                               placeholder="Mot de passe" required autofocus>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="confirm_logout" class="btn btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Confirmer la déconnexion
                        </button>
                        <a href="<?= $back_url ?>" class="btn btn-cancel">
                            <i class="fas fa-arrow-left"></i> Annuler et retourner au tableau de bord
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="lock-footer">
                <p>&copy; <?= date('Y') ?> ISTAM Paiement - Système de Protection Active</p>
            </div>
        </div>
    </div>
    
    <script>
        // Empêcher le retour en arrière après déconnexion
        window.history.forward();
        function noBack() { window.history.forward(); }
    </script>
</body>
</html>