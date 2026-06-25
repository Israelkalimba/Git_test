<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/database.php';

Auth::checkSession('etudiant');

$db = Database::getInstance();
$etudiant_id_user = $_SESSION['user_id'] ?? 1;
$etudiant_nom = $_SESSION['user_nom'] ?? 'Ã‰tudiant';

// ========== RÃ‰CUPÃ‰RATION INFOS Ã‰TUDIANT ==========
$stmt = $db->prepare("
    SELECT e.*, u.nom, u.email, u.created_at,
           fi.nom_filiere, fa.nom_faculte, fa.id_faculte,
           pr.nom_promotion
    FROM etudiants e 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    JOIN promotions pr ON e.id_promotion = pr.id_promotion 
    WHERE e.id_utilisateur = :id_user
");
$stmt->execute(['id_user' => $etudiant_id_user]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    echo "<script>alert('Erreur : Profil Ã©tudiant introuvable.'); window.location.href='../logout.php?role=etudiant';</script>";
    exit();
}

$id_etudiant = $etudiant['id_etudiant'];
$matricule = $etudiant['matricule'];
$telephone = $etudiant['telephone'];
$nom_filiere = $etudiant['nom_filiere'];
$nom_faculte = $etudiant['nom_faculte'];
$nom_promotion = $etudiant['nom_promotion'];
$id_filiere = $etudiant['id_filiere'];
$id_promotion = $etudiant['id_promotion'];

// ========== STATISTIQUES ==========
$stmt = $db->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes'");
$stmt->execute(['id' => $id_etudiant]);
$total_paye = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes'");
$stmt->execute(['id' => $id_etudiant]);
$nb_paiements = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_etudiant = :id AND statut = 'en_attente'");
$stmt->execute(['id' => $id_etudiant]);
$nb_attente = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_etudiant = :id AND statut = 'echec'");
$stmt->execute(['id' => $id_etudiant]);
$nb_echec = $stmt->fetch()['total'] ?? 0;

// ========== FRAIS Ã€ PAYER ==========
$stmt = $db->prepare("
    SELECT fr.*, 
           COALESCE(pa.montant_paye, 0) as deja_paye,
           pa.statut as statut_paiement,
           pa.date_paiement,
           pa.id_paiement
    FROM frais fr 
    LEFT JOIN paiements pa ON fr.id_frais = pa.id_frais AND pa.id_etudiant = :id_etudiant AND pa.statut = 'succes'
    WHERE fr.id_filiere = :id_filiere AND fr.id_promotion = :id_promotion
    ORDER BY fr.type_frais
");
$stmt->execute([
    'id_etudiant' => $id_etudiant,
    'id_filiere' => $id_filiere,
    'id_promotion' => $id_promotion
]);
$frais_a_payer = $stmt->fetchAll();

$total_a_payer = array_sum(array_column($frais_a_payer, 'montant'));
$reste_a_payer = $total_a_payer - $total_paye;
$pourcentage_paye = $total_a_payer > 0 ? round(($total_paye / $total_a_payer) * 100, 1) : 0;
$nb_frais_payes = count(array_filter($frais_a_payer, fn($f) => ($f['statut_paiement'] ?? '') === 'succes'));

// ========== DERNIERS PAIEMENTS ==========
$stmt = $db->prepare("
    SELECT p.*, f.type_frais
    FROM paiements p 
    JOIN frais f ON p.id_frais = f.id_frais 
    WHERE p.id_etudiant = :id 
    ORDER BY p.date_paiement DESC 
    LIMIT 5
");
$stmt->execute(['id' => $id_etudiant]);
$derniers_paiements = $stmt->fetchAll();

// ========== FRAIS PAYÃ‰S ==========
$stmt = $db->prepare("
    SELECT p.*, f.type_frais, f.montant as montant_frais
    FROM paiements p 
    JOIN frais f ON p.id_frais = f.id_frais 
    WHERE p.id_etudiant = :id AND p.statut = 'succes'
    ORDER BY p.date_paiement DESC
");
$stmt->execute(['id' => $id_etudiant]);
$frais_payes = $stmt->fetchAll();

// ========== Ã‰VOLUTION MENSUELLE DES PAIEMENTS ==========
$evolution_mensuelle = [];
for ($i = 5; $i >= 0; $i--) {
    $mois = date('Y-m', strtotime("-{$i} months"));
    $stmt = $db->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes' AND DATE_FORMAT(date_paiement, '%Y-%m') = :mois");
    $stmt->execute(['id' => $id_etudiant, 'mois' => $mois]);
    $evolution_mensuelle[] = [
        'mois' => date('M Y', strtotime("-{$i} months")),
        'total' => (float)($stmt->fetch()['total'] ?? 0)
    ];
}

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt->execute(['id' => $etudiant_id_user]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['id' => $etudiant_id_user]);
$navbar_notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ã‰tudiant - ISTAM Paiement</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/etudiant/dashboard_etudiant.css">
</head>
<body>
    <div class="etudiant-layout">
        <!-- SIDEBAR -->
        <?php include 'includes/sidebar_etudiant.php'; ?>

        <!-- CONTENU PRINCIPAL -->
        <div class="main-content">
            <!-- NAVBAR -->
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_etudiant.php'; 
            ?>

            <!-- DASHBOARD -->
            <main class="dashboard-content">
                <!-- En-tÃªte -->
                <header class="dashboard-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="dashboard-title">
                                <i class="fas fa-tachometer-alt"></i> Mon Tableau de Bord
                            </h1>
                            <p class="dashboard-subtitle">
                                Bienvenue, <strong><?= htmlspecialchars($etudiant_nom) ?></strong> | 
                                Matricule : <strong><?= htmlspecialchars($matricule) ?></strong> |
                                <?= htmlspecialchars($nom_filiere) ?> - <?= htmlspecialchars($nom_promotion) ?>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <a href="payer_frais.php" class="btn btn-primary">
                                <i class="fas fa-credit-card"></i> Payer maintenant
                            </a>
                            <a href="historique_paiements.php" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-history"></i> Historique
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Cartes statistiques -->
                <div class="stats-grid-etu">
                    <div class="stat-card-etu stat-green">
                        <div class="stat-icon-etu"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info-etu">
                            <h4>$<?= number_format($total_paye, 2) ?></h4>
                            <p>Total payÃ©</p>
                        </div>
                    </div>
                    <div class="stat-card-etu stat-orange">
                        <div class="stat-icon-etu"><i class="fas fa-coins"></i></div>
                        <div class="stat-info-etu">
                            <h4>$<?= number_format($reste_a_payer, 2) ?></h4>
                            <p>Reste Ã  payer</p>
                        </div>
                    </div>
                    <div class="stat-card-etu stat-blue">
                        <div class="stat-icon-etu"><i class="fas fa-credit-card"></i></div>
                        <div class="stat-info-etu">
                            <h4><?= $nb_paiements ?></h4>
                            <p>Paiements rÃ©ussis</p>
                        </div>
                    </div>
                    <div class="stat-card-etu stat-purple">
                        <div class="stat-icon-etu"><i class="fas fa-percent"></i></div>
                        <div class="stat-info-etu">
                            <h4 id="statPourcentage">0%</h4>
                            <p>ComplÃ©tÃ©</p>
                        </div>
                    </div>
                </div>

                <!-- Barre de progression ANIMÃ‰E -->
                <div class="progression-card">
                    <div class="progression-header">
                        <h4><i class="fas fa-chart-line"></i> Progression des paiements</h4>
                        <span id="progressionPourcentage">0%</span>
                    </div>
                    <div class="progression-bar-wrapper">
                        <div class="progression-bar" id="progressionBar" style="width: 0%;">
                            <span class="progression-label" id="progressionLabel">0%</span>
                        </div>
                    </div>
                    <div class="progression-footer">
                        <span>Total Ã  payer : <strong>$<?= number_format($total_a_payer, 2) ?></strong></span>
                        <span>PayÃ© : <strong class="text-success">$<?= number_format($total_paye, 2) ?></strong></span>
                        <span>Reste : <strong class="text-warning">$<?= number_format($reste_a_payer, 2) ?></strong></span>
                    </div>
                    <div class="progression-detail">
                        <span><i class="fas fa-check-circle text-success"></i> <?= $nb_frais_payes ?> frais payÃ©s</span>
                        <span><i class="fas fa-clock text-warning"></i> <?= count($frais_a_payer) - $nb_frais_payes ?> frais restants</span>
                        <span><i class="fas fa-list-alt text-primary"></i> <?= count($frais_a_payer) ?> frais au total</span>
                    </div>
                </div>

                <!-- Frais Ã  payer -->
                <div class="frais-section">
                    <div class="section-header">
                        <h3><i class="fas fa-list-alt"></i> Mes Frais AcadÃ©miques</h3>
                        <span class="badge-info"><?= htmlspecialchars($nom_filiere) ?> - <?= htmlspecialchars($nom_promotion) ?></span>
                    </div>
                    
                    <?php if (empty($frais_a_payer)): ?>
                        <div class="empty-state">
                            <i class="fas fa-info-circle fa-3x"></i>
                            <h4 class="mt-3">Aucun frais configurÃ©</h4>
                            <p>Aucun frais n'a Ã©tÃ© configurÃ© pour votre filiÃ¨re et promotion. Veuillez contacter le secrÃ©tariat.</p>
                        </div>
                    <?php else: ?>
                        <div class="frais-grid">
                            <?php foreach ($frais_a_payer as $frais): 
                                $est_paye = ($frais['statut_paiement'] ?? '') === 'succes';
                                $montant_fc = $frais['montant'] * ($frais['taux_change'] ?? 2300);
                            ?>
                                <div class="frais-card <?= $est_paye ? 'frais-paye' : 'frais-a-payer' ?>">
                                    <div class="frais-card-status">
                                        <?php if ($est_paye): ?>
                                            <span class="badge-paye"><i class="fas fa-check-circle"></i> PayÃ©</span>
                                        <?php else: ?>
                                            <span class="badge-a-payer"><i class="fas fa-clock"></i> Ã€ payer</span>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="frais-type"><?= htmlspecialchars($frais['type_frais']) ?></h5>
                                    <div class="frais-montant">
                                        <span class="montant-usd">$<?= number_format($frais['montant'], 2) ?></span>
                                        <span class="montant-fc"><?= number_format($montant_fc, 0, ',', ' ') ?> FC</span>
                                    </div>
                                    <small class="taux-info">1$ = <?= number_format($frais['taux_change'] ?? 2300, 0) ?> FC</small>
                                    
                                    <?php if ($est_paye): ?>
                                        <div class="frais-paye-info">
                                            <small>PayÃ© le <?= date('d/m/Y', strtotime($frais['date_paiement'])) ?></small>
                                        </div>
                                    <?php else: ?>
                                        <a href="payer_frais.php?frais=<?= $frais['id_frais'] ?>" class="btn-payer">
                                            <i class="fas fa-credit-card"></i> Payer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Derniers paiements -->
                <div class="derniers-paiements">
                    <div class="section-header">
                        <h3><i class="fas fa-history"></i> DerniÃ¨res Transactions</h3>
                        <a href="historique_paiements.php" class="see-all">Voir tout <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <?php if (empty($derniers_paiements)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox fa-2x"></i>
                            <p>Aucune transaction rÃ©cente.</p>
                        </div>
                    <?php else: ?>
                        <div class="transactions-list">
                            <?php foreach ($derniers_paiements as $p): ?>
                                <div class="transaction-item">
                                    <div class="transac-icon <?= $p['statut'] === 'succes' ? 'icon-succes' : ($p['statut'] === 'echec' ? 'icon-echec' : 'icon-attente') ?>">
                                        <i class="fas fa-<?= $p['statut'] === 'succes' ? 'check-circle' : ($p['statut'] === 'echec' ? 'times-circle' : 'clock') ?>"></i>
                                    </div>
                                    <div class="transac-info">
                                        <strong><?= htmlspecialchars($p['type_frais']) ?></strong>
                                        <small><?= date('d/m/Y H:i', strtotime($p['date_paiement'])) ?></small>
                                    </div>
                                    <div class="transac-montant">
                                        <strong class="<?= $p['statut'] === 'succes' ? 'text-success' : ($p['statut'] === 'echec' ? 'text-danger' : 'text-warning') ?>">
                                            $<?= number_format($p['montant_paye'], 2) ?>
                                        </strong>
                                        <span class="status-pill status-<?= $p['statut'] ?>">
                                            <?= $p['statut'] === 'succes' ? 'RÃ©ussi' : ($p['statut'] === 'echec' ? 'Ã‰chec' : 'En attente') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ========== GRAPHIQUES Ã‰TUDIANT ========== -->
                <div class="charts-grid-etu">
                    <!-- RÃ©partition des paiements -->
                    <div class="chart-card-etu">
                        <div class="chart-card-header">
                            <h4><i class="fas fa-chart-pie"></i> RÃ©partition de mes paiements</h4>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartRepartition"></canvas>
                        </div>
                    </div>
                    
                    <!-- Mes paiements par type de frais -->
                    <div class="chart-card-etu">
                        <div class="chart-card-header">
                            <h4><i class="fas fa-chart-bar"></i> Mes paiements par type de frais</h4>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="chartFrais"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Ã‰volution mensuelle -->
                <div class="chart-card-etu chart-full mt-3">
                    <div class="chart-card-header">
                        <h4><i class="fas fa-chart-line"></i> Ã‰volution mensuelle de mes paiements</h4>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="chartEvolution"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- DonnÃ©es pour les graphiques -->
    <script>
    const progressionPourcentage = <?= $pourcentage_paye ?>;
    const totalAPayer = <?= $total_a_payer ?>;
    const totalPaye = <?= $total_paye ?>;
    const nbFraisPayes = <?= $nb_frais_payes ?>;
    const nbFraisTotal = <?= count($frais_a_payer) ?>;
    const fraisLabels = <?= json_encode(array_column($frais_a_payer, 'type_frais')) ?>;
    const fraisMontants = <?= json_encode(array_map('floatval', array_column($frais_a_payer, 'montant'))) ?>;
    const fraisPayesData = <?= json_encode(array_map(function($f) { return ($f['statut_paiement'] ?? '') === 'succes' ? floatval($f['montant']) : 0; }, $frais_a_payer)) ?>;
    const evolutionLabels = <?= json_encode(array_column($evolution_mensuelle, 'mois')) ?>;
    const evolutionValues = <?= json_encode(array_column($evolution_mensuelle, 'total')) ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/etudiant/dashboard_etudiant.js"></script>
</body>
</html>
