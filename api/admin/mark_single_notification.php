<?php
require_once '../../includes/config.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$data = json_decode(file_get_contents('php://input'), true);
$notif_id = $data['id'] ?? 0;

if ($notif_id > 0) {
    $stmt = $db->prepare("UPDATE notifications SET statut = 'lu' WHERE id_notification = :id");
    $stmt->execute(['id' => $notif_id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}