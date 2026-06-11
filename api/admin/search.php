<?php
require_once '../../includes/config.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$results = [];

// Recherche étudiants
$stmt = $db->prepare("
    SELECT u.nom, u.email, e.matricule 
    FROM utilisateurs u 
    JOIN etudiants e ON u.id_utilisateur = e.id_utilisateur 
    WHERE u.role = 'etudiant' AND (u.nom LIKE :q OR e.matricule LIKE :q2) 
    LIMIT 5
");
$stmt->execute(['q' => "%$query%", 'q2' => "%$query%"]);
while ($row = $stmt->fetch()) {
    $results[] = [
        'type' => 'etudiant',
        'title' => htmlspecialchars($row['nom']),
        'subtitle' => 'Matricule: ' . htmlspecialchars($row['matricule']),
        'url' => 'gestion_etudiants.php?search=' . urlencode($row['matricule'])
    ];
}

// Recherche transactions
$stmt = $db->prepare("
    SELECT p.reference_transaction, p.montant_paye, u.nom 
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    WHERE p.reference_transaction LIKE :q 
    LIMIT 3
");
$stmt->execute(['q' => "%$query%"]);
while ($row = $stmt->fetch()) {
    $results[] = [
        'type' => 'transaction',
        'title' => htmlspecialchars(substr($row['reference_transaction'], 0, 15)),
        'subtitle' => '$' . number_format($row['montant_paye'], 2) . ' - ' . htmlspecialchars($row['nom']),
        'url' => 'rapports.php?ref=' . urlencode($row['reference_transaction'])
    ];
}

echo json_encode($results);