<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('etudiant');

$db = Database::getInstance();
$etudiant_nom = $_SESSION['user_nom'] ?? 'Étudiant';
$etudiant_id_user = $_SESSION['user_id'] ?? 1;

// Récupérer l'id_etudiant
$stmt = $db->prepare("SELECT id_etudiant, matricule FROM etudiants WHERE id_utilisateur = :id_user");
$stmt->execute(['id_user' => $etudiant_id_user]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    echo "<script>alert('Erreur : Profil étudiant introuvable.'); window.location.href='../logout.php?role=etudiant';</script>";
    exit();
}

$id_etudiant = $etudiant['id_etudiant'];
$matricule = $etudiant['matricule'];

// ========== GÉNÉRER UN REÇU ==========
if (isset($_GET['action']) && $_GET['action'] === 'telecharger' && isset($_GET['id'])) {
    $id_paiement = (int)$_GET['id'];
    
    // Vérifier que le paiement appartient bien à l'étudiant
    $stmt = $db->prepare("
        SELECT p.*, f.type_frais, f.montant as montant_frais, f.taux_change,
               u.nom, e.matricule, fi.nom_filiere, fa.nom_faculte, pr.nom_promotion,
               tmm.operateur, tmm.numero_telephone
        FROM paiements p 
        JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
        JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
        JOIN frais f ON p.id_frais = f.id_frais 
        JOIN filieres fi ON e.id_filiere = fi.id_filiere 
        JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
        JOIN promotions pr ON e.id_promotion = pr.id_promotion 
        LEFT JOIN transaction_mobile_money tmm ON p.id_paiement = tmm.id_paiement
        WHERE p.id_paiement = :id AND p.id_etudiant = :id_etudiant AND p.statut = 'succes'
    ");
    $stmt->execute(['id' => $id_paiement, 'id_etudiant' => $id_etudiant]);
    $paiement = $stmt->fetch();
    
    if ($paiement) {
        $recu_html = genererRecuHTML($paiement);
        
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reçu ISTAM</title>';
        echo '<style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
            .recu-header { text-align: center; border-bottom: 2px solid #1e40af; padding-bottom: 15px; margin-bottom: 20px; }
            .recu-header h2 { color: #1e40af; margin: 0; }
            .recu-header p { color: #666; margin: 5px 0; }
            .recu-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
            .recu-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .recu-table th, .recu-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            .recu-table th { background: #1e40af; color: white; }
            .recu-total { text-align: right; font-size: 1.2em; font-weight: bold; margin-top: 15px; }
            .recu-footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; color: #666; font-size: 0.85em; }
            .recu-stamp { color: #10b981; font-size: 1.5em; font-weight: bold; text-align: center; margin: 20px 0; }
            @media print { body { margin: 0; padding: 0; } button { display: none; } }
            .btn-print { display: block; margin: 20px auto; padding: 10px 30px; background: #1e40af; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
            .btn-print:hover { background: #2563eb; }
            @media print { .btn-print { display: none; } }
        </style>';
        echo '</head><body>';
        echo '<button class="btn-print" onclick="window.print()">🖨️ Imprimer le reçu</button>';
        echo $recu_html;
        echo '</body></html>';
        exit();
    } else {
        echo "<script>alert('Reçu introuvable ou paiement non autorisé.'); window.location.href='mes_recus.php';</script>";
        exit();
    }
}

// ========== RÉCUPÉRATION DES PAIEMENTS RÉUSSIS ==========
$stmt = $db->prepare("
    SELECT p.*, f.type_frais, f.montant as montant_frais, f.taux_change, f.montant_fc,
           tmm.operateur, tmm.numero_telephone, tmm.statut_api
    FROM paiements p 
    JOIN frais f ON p.id_frais = f.id_frais 
    LEFT JOIN transaction_mobile_money tmm ON p.id_paiement = tmm.id_paiement
    WHERE p.id_etudiant = :id AND p.statut = 'succes'
    ORDER BY p.date_paiement DESC
");
$stmt->execute(['id' => $id_etudiant]);
$paiements = $stmt->fetchAll();

// Stats
$total_recus = count($paiements);
$total_montant = array_sum(array_column($paiements, 'montant_paye'));

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt->execute(['id' => $etudiant_id_user]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt_nav = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt_nav->execute(['id' => $etudiant_id_user]);
$navbar_notifications = $stmt_nav->fetchAll();

// Fonction pour générer le HTML du reçu
function genererRecuHTML($p) {
    $montant_fc = $p['montant_paye'] * ($p['taux_change'] ?? 2300);
    $date = date('d/m/Y', strtotime($p['date_paiement']));
    $heure = date('H:i', strtotime($p['date_paiement']));
    $ref = substr($p['reference_transaction'], 0, 20);
    $operateur = !empty($p['operateur']) ? htmlspecialchars($p['operateur']) : 'Mobile Money';
    $telephone = !empty($p['numero_telephone']) ? htmlspecialchars($p['numero_telephone']) : 'N/A';
    
    return '
    <div class="recu-header">
        <h2>🏛️ ISTAM - Reçu de Paiement</h2>
        <p>Institut Supérieur des Techniques Appliquées et de Management</p>
        <p>123 Avenue de l\'Université, Kinshasa</p>
        <p><strong>Reçu N° : RECU-' . $p['id_paiement'] . '</strong></p>
    </div>
    
    <div class="recu-info">
        <div>
            <p><strong>Étudiant :</strong> ' . htmlspecialchars($p['nom']) . '</p>
            <p><strong>Matricule :</strong> ' . htmlspecialchars($p['matricule']) . '</p>
            <p><strong>Filière :</strong> ' . htmlspecialchars($p['nom_filiere']) . '</p>
            <p><strong>Promotion :</strong> ' . htmlspecialchars($p['nom_promotion']) . '</p>
        </div>
        <div style="text-align:right;">
            <p><strong>Date :</strong> ' . $date . ' à ' . $heure . '</p>
            <p><strong>Référence :</strong> ' . $ref . '</p>
            <p><strong>Mode :</strong> Mobile Money</p>
            <p><strong>Opérateur :</strong> ' . $operateur . '</p>
            <p><strong>Téléphone :</strong> ' . $telephone . '</p>
        </div>
    </div>
    
    <table class="recu-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Montant USD</th>
                <th>Taux</th>
                <th>Équivalent FC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . htmlspecialchars($p['type_frais']) . '</td>
                <td><strong>$' . number_format($p['montant_paye'], 2) . '</strong></td>
                <td>1$ = ' . number_format($p['taux_change'] ?? 2300, 0) . ' FC</td>
                <td><strong>' . number_format($montant_fc, 0, ',', ' ') . ' FC</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="recu-total">
        Total payé : <span style="color:#10b981;">$' . number_format($p['montant_paye'], 2) . '</span>
    </div>
    
    <div class="recu-stamp">
        ✅ PAYÉ
    </div>
    
    <div class="recu-footer">
        <p>Ce reçu est généré automatiquement par le système ISTAM Paiement.</p>
        <p>Il constitue une preuve de paiement valide.</p>
        <p>Généré le ' . date('d/m/Y à H:i') . '</p>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Reçus - Étudiant ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/etudiant/dashboard_etudiant.css">
    <link rel="stylesheet" href="../assets/css/etudiant/mes_recus.css">
</head>
<body>
    <div class="etudiant-layout">
        <?php include 'includes/sidebar_etudiant.php'; ?>
        <div class="main-content">
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_etudiant.php'; 
            ?>
            <main class="dashboard-content">
                
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-file-pdf"></i> Mes Reçus
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Téléchargez et imprimez vos reçus de paiement. 
                                <span class="text-success fw-bold"><?= $total_recus ?> reçu(s)</span>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <span class="badge-total me-3">
                                <i class="fas fa-coins"></i> Total : <strong>$<?= number_format($total_montant, 2) ?></strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="recus-stats">
                    <div class="recu-stat-card">
                        <div class="recu-stat-icon bg-blue"><i class="fas fa-file-pdf"></i></div>
                        <div class="recu-stat-info"><h4><?= $total_recus ?></h4><p>Reçus disponibles</p></div>
                    </div>
                    <div class="recu-stat-card">
                        <div class="recu-stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
                        <div class="recu-stat-info"><h4>$<?= number_format($total_montant, 2) ?></h4><p>Total payé</p></div>
                    </div>
                    <div class="recu-stat-card">
                        <div class="recu-stat-icon bg-purple"><i class="fas fa-print"></i></div>
                        <div class="recu-stat-info"><h4><i class="fas fa-infinity"></i></h4><p>Téléchargements illimités</p></div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list-alt"></i> Mes reçus de paiement</h3>
                        <span class="badge-count"><?= $total_recus ?> reçu(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tableRecus">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Type de Frais</th>
                                    <th>Montant USD</th>
                                    <th>Équiv. FC</th>
                                    <th>Opérateur</th>
                                    <th>Date</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paiements)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-file-pdf fa-3x"></i>
                                                <h4 class="mt-3">Aucun reçu disponible</h4>
                                                <p class="text-muted">
                                                    Vous n'avez pas encore effectué de paiement. 
                                                    <a href="payer_frais.php" class="text-primary fw-bold">Payer maintenant</a>
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paiements as $p): 
                                        $montant_fc = $p['montant_paye'] * ($p['taux_change'] ?? 2300);
                                    ?>
                                        <tr>
                                            <td><code class="ref-code"><?= htmlspecialchars(substr($p['reference_transaction'], 0, 15)) ?></code></td>
                                            <td><span class="frais-badge"><?= htmlspecialchars($p['type_frais']) ?></span></td>
                                            <td><strong class="text-success">$<?= number_format($p['montant_paye'], 2) ?></strong></td>
                                            <td><span class="fc-badge"><?= number_format($montant_fc, 0, ',', ' ') ?> FC</span></td>
                                            <td>
                                                <?php if (!empty($p['operateur'])): ?>
                                                    <span class="operateur-badge"><?= htmlspecialchars($p['operateur']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></small>
                                                <small class="d-block text-muted"><?= date('H:i', strtotime($p['date_paiement'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=telecharger&id=<?= $p['id_paiement'] ?>" 
                                                       class="btn btn-primary" target="_blank" title="Voir / Imprimer">
                                                        <i class="fas fa-eye"></i> Voir
                                                    </a>
                                                    <a href="?action=telecharger&id=<?= $p['id_paiement'] ?>" 
                                                       class="btn btn-outline-primary" 
                                                       onclick="window.open(this.href, '_blank', 'width=800,height=600'); return false;"
                                                       title="Imprimer">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="info-recus-card mt-4">
                    <div class="info-recus-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="info-recus-text">
                        <h5>Information importante</h5>
                        <p>Ces reçus sont générés automatiquement par le système et constituent une preuve de paiement officielle. Vous pouvez les télécharger et les imprimer à tout moment.</p>
                        <p class="mb-0"><i class="fas fa-shield-alt text-success"></i> <strong>Vos reçus sont conservés indéfiniment</strong> dans votre espace étudiant.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/etudiant/dashboard_etudiant.js"></script>
</body>
</html>