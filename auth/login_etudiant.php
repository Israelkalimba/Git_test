<?php
require_once '../includes/config.php';

// Redirection si déjà connecté
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['user_role'] === 'etudiant') {
        header('Location: ../etudiant/dashboard.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/Database.php';
    
    $matricule = trim($_POST['matricule'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($matricule) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (strlen($password) < 4) {
        $error = 'Le mot de passe doit contenir au moins 4 chiffres.';
    } else {
        $db = Database::getInstance();
        
        // 1. Chercher l'étudiant par matricule
        $stmt = $db->prepare("
            SELECT e.id_etudiant, e.matricule, e.id_utilisateur, e.telephone,
                   e.id_filiere, e.id_promotion,
                   u.id_utilisateur, u.nom, u.email, u.mot_de_passe, u.role, u.statut_compte
            FROM etudiants e 
            JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
            WHERE e.matricule = :matricule 
            AND u.role = 'etudiant'
            LIMIT 1
        ");
        $stmt->execute(['matricule' => $matricule]);
        $etudiant = $stmt->fetch();
        
        if (!$etudiant) {
            $error = 'Numéro matricule introuvable. Vérifiez votre matricule.';
        } elseif ($etudiant['statut_compte'] !== 'actif') {
            $error = 'Votre compte est désactivé. Contactez l\'administration.';
        } else {
            // 2. Hasher le mot de passe saisi en SHA256 pour comparer
            $password_hash = hash('sha256', $password);
            
            // 3. Comparer avec le hash stocké
            if ($password_hash === $etudiant['mot_de_passe']) {
                // Connexion réussie - Créer la session
                $_SESSION['user_id'] = $etudiant['id_utilisateur'];
                $_SESSION['user_nom'] = $etudiant['nom'];
                $_SESSION['user_email'] = $etudiant['email'];
                $_SESSION['user_role'] = 'etudiant';
                $_SESSION['user_matricule'] = $etudiant['matricule'];
                $_SESSION['id_etudiant'] = $etudiant['id_etudiant'];
                $_SESSION['logged_in'] = true;
                
                // Audit log
                try {
                    $stmt_audit = $db->prepare("
                        INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) 
                        VALUES (:id_user, 'connexion', 'login_etudiant', :description, :ip)
                    ");
                    $stmt_audit->execute([
                        'id_user' => $etudiant['id_utilisateur'],
                        'description' => "Connexion étudiant - Matricule: {$etudiant['matricule']}, Nom: {$etudiant['nom']}",
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                    ]);
                } catch (PDOException $e) {
                    // L'audit ne bloque pas la connexion
                }
                
                header('Location: ../etudiant/dashboard.php');
                exit();
            } else {
                $error = 'Mot de passe incorrect. Veuillez réessayer.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion Espace Étudiant - ISTAM Paiement">
    <title>Connexion Étudiant - ISTAM Paiement</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/etudiant/login_etudiant.css">
</head>
<body>
    <div class="login-page">
        <!-- Bouton retour -->
        <a href="../index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            <span>Retour à l'accueil</span>
        </a>

        <div class="login-card">
            <!-- Section illustration -->
            <div class="login-illustration">
                <div class="illustration-bg">
                    <div class="bg-shape bg-shape-1"></div>
                    <div class="bg-shape bg-shape-2"></div>
                    <div class="bg-shape bg-shape-3"></div>
                </div>
                <div class="illustration-content">
                    <img src="../assets/images/logo-istam.png" alt="ISTAM" class="logo-istam">
                    <h2>Espace Étudiant</h2>
                    <p class="illustration-text">Payez vos frais académiques en toute simplicité</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Paiement Mobile</h4>
                                <p>Orange Money, Airtel Money, M-Pesa</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Bordereaux PDF</h4>
                                <p>Reçus automatiques après paiement</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="feature-text">
                                <h4>Notifications</h4>
                                <p>Confirmations par email et SMS</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section formulaire -->
            <div class="login-form-section">
                <div class="form-container">
                    <div class="form-header">
                        <div class="avatar-circle">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h1>Connexion Étudiant</h1>
                        <p>Connectez-vous avec votre matricule et mot de passe</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="loginForm" class="login-form" novalidate autocomplete="off">
                        <!-- Champ Matricule -->
                        <div class="input-field">
                            <label for="matricule">
                                <i class="fas fa-id-card"></i> Numéro Matricule
                            </label>
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    id="matricule" 
                                    name="matricule" 
                                    placeholder="Ex: 2025147986"
                                    value="<?= htmlspecialchars($_POST['matricule'] ?? '') ?>"
                                    required
                                    autocomplete="off"
                                    autofocus
                                    maxlength="20"
                                    inputmode="numeric"
                                    pattern="[0-9]+"
                                >
                                <i class="fas fa-check-circle valid-icon"></i>
                                <i class="fas fa-exclamation-circle error-icon"></i>
                            </div>
                            <span class="error-message">Veuillez entrer votre numéro matricule</span>
                            <small class="help-text">
                                <i class="fas fa-info-circle"></i> Le numéro à 10 chiffres fourni par l'ISTAM
                            </small>
                        </div>

                        <!-- Champ Mot de passe -->
                        <div class="input-field">
                            <label for="password">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Minimum 4 chiffres"
                                    required
                                    autocomplete="off"
                                    minlength="4"
                                    maxlength="10"
                                    inputmode="numeric"
                                    pattern="[0-9]+"
                                >
                                <button type="button" class="toggle-password" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="error-message">Mot de passe requis (minimum 4 chiffres)</span>
                            <small class="help-text">
                                <i class="fas fa-shield-alt"></i> Code secret à 4 chiffres minimum
                            </small>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" id="rememberMe">
                                <span class="checkbox-custom"></span>
                                Se souvenir de mon matricule
                            </label>
                            <a href="#" class="forgot-link">Mot de passe oublié ?</a>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <span class="btn-content">
                                <i class="fas fa-sign-in-alt"></i>
                                Se connecter
                            </span>
                            <span class="btn-loader">
                                <span class="spinner"></span>
                                Connexion...
                            </span>
                        </button>
                    </form>

                    <div class="form-footer">
                        <p>Première visite ?</p>
                        <a href="#" class="link-register">
                            <i class="fas fa-info-circle"></i> Contactez le secrétariat pour vos identifiants
                        </a>
                    </div>

                    <div class="other-access">
                        <span class="other-label">Autres accès</span>
                        <div class="other-links">
                            <a href="login_secretaire.php" class="other-link">
                                <i class="fas fa-user-tie"></i> Secrétaire
                            </a>
                            <a href="login_admin.php" class="other-link">
                                <i class="fas fa-user-shield"></i> Admin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; <?= date('Y') ?> ISTAM - Tous droits réservés</p>
            <div class="footer-links">
                <a href="../contact.php">Support</a>
                <span>•</span>
                <a href="#">Confidentialité</a>
                <span>•</span>
                <a href="#">Conditions</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/etudiant/login_etudiant.js"></script>
</body>
</html>