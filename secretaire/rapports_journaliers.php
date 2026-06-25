<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('secretaire');

$db = Database::getInstance();
$secretaire_nom = $_SESSION['user_nom'] ?? 'Secrétaire';
$secretaire_id = $_SESSION['user_id'] ?? 1;

// ========== DATE DU RAPPORT ==========
$date_rapport = $_GET['date'] ?? date('Y-m-d');
$date_debut = $date_rapport . ' 00:00:00';
$date_fin = $date_rapport . ' 23:59:59';

// ========== STATISTIQUES DU JOUR ==========

// Total paiements du jour
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE date_paiement BETWEEN :debut AND :fin");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$total_transactions = $stmt->fetch()['total'] ?? 0;

// Paiements réussis
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE statut = 'succes' AND date_paiement BETWEEN :debut AND :fin");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$total_succes = $stmt->fetch()['total'] ?? 0;

// Paiements échoués
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE statut = 'echec' AND date_paiement BETWEEN :debut AND :fin");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$total_echec = $stmt->fetch()['total'] ?? 0;

// Paiements en attente
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE statut = 'en_attente' AND date_paiement BETWEEN :debut AND :fin");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$total_attente = $stmt->fetch()['total'] ?? 0;

// Montant collecté
$stmt = $db->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE statut = 'succes' AND date_paiement BETWEEN :debut AND :fin");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$montant_collecte = $stmt->fetch()['total'] ?? 0;

// Panier moyen
$panier_moyen = $total_succes > 0 ? round($montant_collecte / $total_succes, 2) : 0;

// ========== PAIEMENTS PAR OPÉRATEUR ==========
$stmt = $db->prepare("
    SELECT tmm.operateur, COUNT(*) as total, COALESCE(SUM(p.montant_paye), 0) as montant
    FROM transaction_mobile_money tmm 
    JOIN paiements p ON tmm.id_paiement = p.id_paiement 
    WHERE p.statut = 'succes' AND p.date_paiement BETWEEN :debut AND :fin
    GROUP BY tmm.operateur
    ORDER BY total DESC
");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$paiements_par_operateur = $stmt->fetchAll();

// ========== PAIEMENTS PAR FACULTÉ ==========
$stmt = $db->prepare("
    SELECT fa.nom_faculte, COUNT(p.id_paiement) as total, COALESCE(SUM(p.montant_paye), 0) as montant
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    WHERE p.statut = 'succes' AND p.date_paiement BETWEEN :debut AND :fin
    GROUP BY fa.id_faculte
    ORDER BY montant DESC
");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$paiements_par_faculte = $stmt->fetchAll();

// ========== PAIEMENTS PAR TYPE DE FRAIS ==========
$stmt = $db->prepare("
    SELECT f.type_frais, COUNT(p.id_paiement) as total, COALESCE(SUM(p.montant_paye), 0) as montant
    FROM paiements p 
    JOIN frais f ON p.id_frais = f.id_frais 
    WHERE p.statut = 'succes' AND p.date_paiement BETWEEN :debut AND :fin
    GROUP BY f.type_frais
    ORDER BY montant DESC
");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$paiements_par_frais = $stmt->fetchAll();

// ========== LISTE DÉTAILLÉE DES PAIEMENTS DU JOUR ==========
$stmt = $db->prepare("
    SELECT p.*, u.nom as nom_etudiant, e.matricule, f.type_frais,
           fi.nom_filiere, fa.nom_faculte, pr.nom_promotion,
           tmm.operateur, tmm.numero_telephone
    FROM paiements p 
    JOIN etudiants e ON p.id_etudiant = e.id_etudiant 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN frais f ON p.id_frais = f.id_frais 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    JOIN promotions pr ON e.id_promotion = pr.id_promotion 
    LEFT JOIN transaction_mobile_money tmm ON p.id_paiement = tmm.id_paiement
    WHERE p.date_paiement BETWEEN :debut AND :fin
    ORDER BY p.date_paiement DESC
");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$paiements_jour = $stmt->fetchAll();

// ========== NOUVEAUX INSCRITS DU JOUR ==========
$stmt = $db->prepare("
    SELECT u.nom, u.email, e.matricule, e.telephone, u.created_at,
           fi.nom_filiere, fa.nom_faculte, pr.nom_promotion
    FROM utilisateurs u 
    JOIN etudiants e ON u.id_utilisateur = e.id_utilisateur 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    JOIN promotions pr ON e.id_promotion = pr.id_promotion 
    WHERE u.role = 'etudiant' AND u.created_at BETWEEN :debut AND :fin
    ORDER BY u.created_at DESC
");
$stmt->execute(['debut' => $date_debut, 'fin' => $date_fin]);
$nouveaux_inscrits = $stmt->fetchAll();

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt->execute(['id' => $secretaire_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['id' => $secretaire_id]);
$navbar_notifications = $stmt->fetchAll();

// Formater la date
$date_formatee = date('d/m/Y', strtotime($date_rapport));
$jour_semaine = date('l', strtotime($date_rapport));
$jours_fr = ['Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'];
$jour_fr = $jours_fr[$jour_semaine] ?? $jour_semaine;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Journalier - Secrétaire ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/secretaire/dashboard_secretaire.css">
    <link rel="stylesheet" href="../assets/css/secretaire/rapports_journaliers.css">
</head>
<body>
    <div class="secretaire-layout">
        <?php include 'includes/sidebar_secretaire.php'; ?>
        <div class="main-content">
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_secretaire.php'; 
            ?>
            <main class="dashboard-content">
                
                <!-- En-tête -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-file-alt"></i> Rapport Journalier
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-calendar-day"></i> 
                                <strong><?= $jour_fr ?> <?= $date_formatee ?></strong> | 
                                Généré par : <?= htmlspecialchars($secretaire_nom) ?> à <?= date('H:i') ?>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <!-- Sélecteur de date -->
                            <form method="GET" class="d-inline-flex align-items-center gap-2">
                                <input type="date" name="date" class="form-control form-control-sm" 
                                       value="<?= $date_rapport ?>" style="width:160px;" onchange="this.form.submit()">
                                <a href="rapports_journaliers.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-calendar-day"></i> Aujourd'hui
                                </a>
                            </form>
                            <button class="btn btn-outline-success btn-sm ms-2" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPIs du jour -->
                <div class="rapport-kpi-grid">
                    <div class="rapport-kpi kpi-total">
                        <div class="kpi-icon"><i class="fas fa-exchange-alt"></i></div>
                        <div class="kpi-info">
                            <h4><?= $total_transactions ?></h4>
                            <p>Transactions</p>
                        </div>
                    </div>
                    <div class="rapport-kpi kpi-succes">
                        <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="kpi-info">
                            <h4><?= $total_succes ?></h4>
                            <p>Réussies</p>
                        </div>
                    </div>
                    <div class="rapport-kpi kpi-echec">
                        <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="kpi-info">
                            <h4><?= $total_echec ?></h4>
                            <p>Échouées</p>
                        </div>
                    </div>
                    <div class="rapport-kpi kpi-attente">
                        <div class="kpi-icon"><i class="fas fa-clock"></i></div>
                        <div class="kpi-info">
                            <h4><?= $total_attente ?></h4>
                            <p>En attente</p>
                        </div>
                    </div>
                    <div class="rapport-kpi kpi-montant">
                        <div class="kpi-icon"><i class="fas fa-coins"></i></div>
                        <div class="kpi-info">
                            <h4>$<?= number_format($montant_collecte, 2) ?></h4>
                            <p>Total collecté</p>
                        </div>
                    </div>
                    <div class="rapport-kpi kpi-panier">
                        <div class="kpi-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="kpi-info">
                            <h4>$<?= number_format($panier_moyen, 2) ?></h4>
                            <p>Panier moyen</p>
                        </div>
                    </div>
                </div>

                <!-- Tableaux -->
                <div class="rapport-grid">
                    <!-- Par opérateur -->
                    <div class="rapport-card">
                        <div class="rapport-card-header">
                            <h4><i class="fas fa-mobile-alt"></i> Par Opérateur</h4>
                        </div>
                        <div class="rapport-card-body">
                            <table class="table table-sm rapport-table">
                                <thead>
                                    <tr><th>Opérateur</th><th>Nb</th><th>Montant</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paiements_par_operateur)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">Aucune transaction</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($paiements_par_operateur as $op): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($op['operateur'] ?? 'N/A') ?></strong></td>
                                                <td><?= $op['total'] ?></td>
                                                <td><strong class="text-success">$<?= number_format($op['montant'], 2) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Par faculté -->
                    <div class="rapport-card">
                        <div class="rapport-card-header">
                            <h4><i class="fas fa-university"></i> Par Département</h4>
                        </div>
                        <div class="rapport-card-body">
                            <table class="table table-sm rapport-table">
                                <thead>
                                    <tr><th>Département</th><th>Nb</th><th>Montant</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paiements_par_faculte)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">Aucun paiement</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($paiements_par_faculte as $fac): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($fac['nom_faculte']) ?></strong></td>
                                                <td><?= $fac['total'] ?></td>
                                                <td><strong class="text-success">$<?= number_format($fac['montant'], 2) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Par type de frais -->
                    <div class="rapport-card">
                        <div class="rapport-card-header">
                            <h4><i class="fas fa-tags"></i> Par Type de Frais</h4>
                        </div>
                        <div class="rapport-card-body">
                            <table class="table table-sm rapport-table">
                                <thead>
                                    <tr><th>Type Frais</th><th>Nb</th><th>Montant</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paiements_par_frais)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">Aucun paiement</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($paiements_par_frais as $fr): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($fr['type_frais']) ?></strong></td>
                                                <td><?= $fr['total'] ?></td>
                                                <td><strong class="text-success">$<?= number_format($fr['montant'], 2) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Nouveaux inscrits -->
                    <div class="rapport-card">
                        <div class="rapport-card-header">
                            <h4><i class="fas fa-user-plus"></i> Nouveaux Inscrits (<?= count($nouveaux_inscrits) ?>)</h4>
                        </div>
                        <div class="rapport-card-body">
                            <?php if (empty($nouveaux_inscrits)): ?>
                                <p class="text-center text-muted py-3">Aucune nouvelle inscription</p>
                            <?php else: ?>
                                <div class="inscrits-list">
                                    <?php foreach ($nouveaux_inscrits as $inscrit): ?>
                                        <div class="inscrit-item">
                                            <div class="inscrit-avatar">
                                                <?= strtoupper(substr($inscrit['nom'], 0, 2)) ?>
                                            </div>
                                            <div class="inscrit-info">
                                                <strong><?= htmlspecialchars($inscrit['nom']) ?></strong>
                                                <small><?= htmlspecialchars($inscrit['matricule']) ?> | <?= htmlspecialchars($inscrit['nom_filiere']) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tableau détaillé des paiements -->
                <div class="table-card mt-4">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Détail des paiements - <?= $date_formatee ?></h3>
                        <span class="badge-count"><?= count($paiements_jour) ?> paiement(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table secretaire-table" id="tableRapport">
                            <thead>
                                <tr>
                                    <th>Réf.</th>
                                    <th>Étudiant</th>
                                    <th>Matricule</th>
                                    <th>Frais</th>
                                    <th>Filière</th>
                                    <th>Opérateur</th>
                                    <th>Montant</th>
                                    <th>Heure</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paiements_jour)): ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="empty-state py-4">
                                                <i class="fas fa-inbox fa-2x"></i>
                                                <p class="text-muted mt-2">Aucun paiement enregistré pour cette date.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paiements_jour as $p): ?>
                                        <tr>
                                            <td><code class="ref-code"><?= htmlspecialchars(substr($p['reference_transaction'], 0, 12)) ?></code></td>
                                            <td><strong><?= htmlspecialchars($p['nom_etudiant']) ?></strong></td>
                                            <td><small><?= htmlspecialchars($p['matricule']) ?></small></td>
                                            <td><small><?= htmlspecialchars($p['type_frais']) ?></small></td>
                                            <td><small><?= htmlspecialchars($p['nom_filiere']) ?></small></td>
                                            <td><small><?= htmlspecialchars($p['operateur'] ?? 'N/A') ?></small></td>
                                            <td>
                                                <strong class="<?= $p['statut'] === 'succes' ? 'text-success' : ($p['statut'] === 'echec' ? 'text-danger' : 'text-warning') ?>">
                                                    $<?= number_format($p['montant_paye'], 2) ?>
                                                </strong>
                                            </td>
                                            <td><small><?= date('H:i', strtotime($p['date_paiement'])) ?></small></td>
                                            <td>
                                                <span class="status-pill status-<?= $p['statut'] ?>">
                                                    <?= $p['statut'] === 'succes' ? 'Réussi' : ($p['statut'] === 'echec' ? 'Échec' : 'En attente') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Résumé -->
                    <?php if (!empty($paiements_jour)): ?>
                        <div class="rapport-resume">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <span class="resume-label">Total transactions</span>
                                    <strong class="resume-value"><?= $total_transactions ?></strong>
                                </div>
                                <div class="col-md-4">
                                    <span class="resume-label">Total collecté</span>
                                    <strong class="resume-value text-success">$<?= number_format($montant_collecte, 2) ?></strong>
                                </div>
                                <div class="col-md-4">
                                    <span class="resume-label">Taux réussite</span>
                                    <strong class="resume-value text-primary"><?= $total_transactions > 0 ? round(($total_succes / $total_transactions) * 100, 1) : 0 ?>%</strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/secretaire/dashboard_secretaire.js"></script>
    <script src="../assets/js/secretaire/rapports_journaliers.js"></script>
</body>
</html>