<?php
require_once '../../includes/config.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_id = $_SESSION['user_id'];

$stmt = $db->prepare("UPDATE notifications SET statut = 'lu' WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);

echo json_encode(['success' => true]);