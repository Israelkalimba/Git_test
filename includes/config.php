<?php

/**
 * CONFIGURATION GLOBALE DU PROJET ISTAM_PAIEMENT
 */

// Paramètres de la base de données
define('DB_HOST', 'db');
define('DB_NAME', 'istam_paiement');
define('DB_USER', 'IZOU');
define('DB_PASS', 'Izou@2026');
// Clé API de paiement
define('PAYMENT_API_KEY', 'pl_htSEOb8G7VojrKRHKNHEcQySHqHKYxzldZkLsBU3');

// Configuration des chemins (URL de base pour les redirections)
define('BASE_URL', 'http://localhost/istam_paiement/');

// Paramètres d'affichage des erreurs (Désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fuseau horaire
date_default_timezone_set('Africa/Kinshasa');

// Initialisation de la session de manière sécurisée sur toutes les pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
