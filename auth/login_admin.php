<?php
require_once '../includes/config.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/Auth.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format d\'email invalide.';
    } else {
        $result = Auth::login($email, $password);
        
        if ($result === true) {
            if ($_SESSION['user_role'] !== 'admin') {
                Auth::logout();
                $error = 'Accès refusé. Privilèges administrateur requis.';
            } else {
                header('Location: ../admin/dashboard.php');
                exit();
            }
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion Espace Administrateur - ISTAM Paiement">
    <title>Connexion Admin - ISTAM Paiement</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/login_admin.css">
</head>
<body>
    <div class="login-page">
        <a href="../index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            <span>Retour à l'accueil</span>
        </a>

        <div class="login-card">
            <div class="login-illustration">
                <div class="illustration-bg">
                    <div class="bg-shape bg-shape-1"></div>
                    <div class="bg-shape bg-shape-2"></div>
                    <div class="bg-shape bg-shape-3"></div>
                </div>
                <div class="illustration-content">
                    <img src="../assets/images/logo-istam.png" alt="ISTAM" class="logo-istam">
                    <h2>Administration</h2>
                    <p class="illustration-text">Supervision complète du système de paiement</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Configuration</h4>
                                <p>Gérez les frais et les paramètres</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Statistiques</h4>
                                <p>Rapports et analyses détaillés</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Utilisateurs</h4>
                                <p>Gestion des comptes et accès</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="login-form-section">
                <div class="form-container">
                    <div class="form-header">
                        <div class="avatar-circle">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h1>Connexion Admin</h1>
                        <p>Accès super-administrateur</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="loginForm" class="login-form" novalidate>
                        <div class="input-field">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email administrateur
                            </label>
                            <div class="input-wrapper">
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    placeholder="admin@istam.ac.cd"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    required
                                    autocomplete="email"
                                    autofocus
                                >
                                <i class="fas fa-check-circle valid-icon"></i>
                                <i class="fas fa-exclamation-circle error-icon"></i>
                            </div>
                            <span class="error-message">Veuillez entrer une adresse email valide</span>
                        </div>

                        <div class="input-field">
                            <label for="password">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="toggle-password" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="error-message">Veuillez entrer votre mot de passe</span>
                        </div>

                        <div class="security-notice">
                            <i class="fas fa-shield-alt"></i>
                            <span>Connexion sécurisée - Accès restreint</span>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <span class="btn-content">
                                <i class="fas fa-sign-in-alt"></i>
                                Se connecter
                            </span>
                            <span class="btn-loader">
                                <span class="spinner"></span>
                                Vérification...
                            </span>
                        </button>
                    </form>

                    <div class="other-access">
                        <span class="other-label">Autres accès</span>
                        <div class="other-links">
                            <a href="login_etudiant.php" class="other-link">
                                <i class="fas fa-user-graduate"></i> Étudiant
                            </a>
                            <a href="login_secretaire.php" class="other-link">
                                <i class="fas fa-user-tie"></i> Secrétaire
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-footer">
            <p>&copy; <?= date('Y') ?> ISTAM - Tous droits réservés</p>
            <div class="footer-links">
                <a href="#">Journal d'audit</a>
                <span>•</span>
                <a href="#">Sécurité</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/login_admin.js"></script>
</body>
</html>