<?php
require_once 'database.php';

/**
 * Classe Auth : Gère l'authentification et la sécurité des sessions
 */
class Auth {
    
    /**
     * Tente de connecter un utilisateur
     * @param string $email
     * @param string $password
     * @return bool|string Retourne true si succès, ou un message d'erreur
     */
    public static function login($email, $password) {
        $db = Database::getInstance();
        
        // Hashage du mot de passe en SHA256 (comme demandé)
        $hashedPassword = hash('sha256', $password);

        try {
            $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && $user['mot_de_passe'] === $hashedPassword) {
                // Création de la session
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
            return "Email ou mot de passe incorrect.";
        } catch (Exception $e) {
            return "Une erreur est survenue lors de la connexion.";
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté et possède le bon rôle
     * @param string $requiredRole (admin, secretaire, etudiant)
     */
    public static function checkSession($requiredRole = null) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }

        if ($requiredRole && $_SESSION['user_role'] !== $requiredRole) {
            // Redirection si le rôle ne correspond pas
            header('Location: ' . BASE_URL . 'index.php?error=access_denied');
            exit();
        }
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}