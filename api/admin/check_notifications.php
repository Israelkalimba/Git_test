<?php
require_once '../../includes/config.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$result = $stmt->fetch();

echo json_encode(['count' => (int)($result['count'] ?? 0)]);