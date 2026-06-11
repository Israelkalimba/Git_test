<?php
require_once '../../includes/config.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$id_faculte = (int)($_GET['faculte'] ?? 0);

if ($id_faculte > 0) {
    $stmt = $db->prepare("SELECT id_filiere, nom_filiere FROM filieres WHERE id_faculte = :id ORDER BY nom_filiere");
    $stmt->execute(['id' => $id_faculte]);
} else {
    $stmt = $db->query("SELECT id_filiere, nom_filiere FROM filieres ORDER BY nom_filiere");
}

$filieres = $stmt->fetchAll();
echo json_encode($filieres);