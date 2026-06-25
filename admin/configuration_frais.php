<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// Vérifier/créer les colonnes manquantes
try {
    $db->query("SELECT devise FROM frais LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE frais ADD COLUMN devise VARCHAR(10) DEFAULT 'USD' AFTER montant");
    $db->exec("ALTER TABLE frais ADD COLUMN taux_change DECIMAL(10,4) DEFAULT 2300.0000 AFTER devise");
    $db->exec("ALTER TABLE frais ADD COLUMN montant_fc DECIMAL(12,2) DEFAULT NULL AFTER taux_change");
}

// ========== TRAITEMENT CRUD ==========
$message = '';
$message_type = '';

// AJOUTER UNE CONFIGURATION DE FRAIS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $type_frais = trim($_POST['type_frais'] ?? '');
    $montant_usd = (float)($_POST['montant_usd'] ?? 0);
    $taux_change = (float)($_POST['taux_change'] ?? 2300);
    $annee_academique = trim($_POST['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1));
    $id_filiere = (int)($_POST['id_filiere'] ?? 0);
    $id_promotion = (int)($_POST['id_promotion'] ?? 0);
    $date_limite = !empty($_POST['date_limite']) ? $_POST['date_limite'] : null;
    
    $montant_fc = $montant_usd * $taux_change;
    
    $errors = [];
    if (empty($type_frais)) $errors[] = "Le type de frais est obligatoire.";
    if ($montant_usd <= 0) $errors[] = "Le montant doit être supérieur à 0.";
    if ($id_filiere <= 0) $errors[] = "Veuillez sélectionner une filière.";
    if ($id_promotion <= 0) $errors[] = "Veuillez sélectionner une promotion.";
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO frais (type_frais, montant, devise, taux_change, montant_fc, annee_academique, date_limite, id_filiere, id_promotion) 
                VALUES (:type, :montant, 'USD', :taux, :montant_fc, :annee, :date_limite, :filiere, :promo)
            ");
            $stmt->execute([
                'type' => $type_frais,
                'montant' => $montant_usd,
                'taux' => $taux_change,
                'montant_fc' => $montant_fc,
                'annee' => $annee_academique,
                'date_limite' => $date_limite,
                'filiere' => $id_filiere,
                'promo' => $id_promotion
            ]);
            
            $message = "Configuration de frais ajoutée avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'warning';
    }
}

// MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_frais = (int)($_POST['id_frais'] ?? 0);
    $type_frais = trim($_POST['type_frais'] ?? '');
    $montant_usd = (float)($_POST['montant_usd'] ?? 0);
    $taux_change = (float)($_POST['taux_change'] ?? 2300);
    $id_filiere = (int)($_POST['id_filiere'] ?? 0);
    $id_promotion = (int)($_POST['id_promotion'] ?? 0);
    $date_limite = !empty($_POST['date_limite']) ? $_POST['date_limite'] : null;
    
    $montant_fc = $montant_usd * $taux_change;
    
    $errors = [];
    if ($id_frais <= 0) $errors[] = "ID frais invalide.";
    if ($montant_usd <= 0) $errors[] = "Le montant doit être supérieur à 0.";
    if ($id_filiere <= 0) $errors[] = "Veuillez sélectionner une filière.";
    if ($id_promotion <= 0) $errors[] = "Veuillez sélectionner une promotion.";
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE frais 
                SET type_frais = :type, 
                    montant = :montant, 
                    taux_change = :taux, 
                    montant_fc = :montant_fc,
                    id_filiere = :filiere, 
                    id_promotion = :promo, 
                    date_limite = :date_limite
                WHERE id_frais = :id
            ");
            $stmt->execute([
                'type' => $type_frais,
                'montant' => $montant_usd,
                'taux' => $taux_change,
                'montant_fc' => $montant_fc,
                'filiere' => $id_filiere,
                'promo' => $id_promotion,
                'date_limite' => $date_limite,
                'id' => $id_frais
            ]);
            
            $message = "Configuration modifiée avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la modification : " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'warning';
    }
}

// SUPPRIMER
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id_frais = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM frais WHERE id_frais = :id");
        $stmt->execute(['id' => $id_frais]);
        $message = "Configuration supprimée avec succès.";
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = "Erreur lors de la suppression : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// ========== METTRE À JOUR LE TAUX GLOBAL ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_taux_global') {
    $nouveau_taux = (float)($_POST['nouveau_taux'] ?? 0);
    
    if ($nouveau_taux > 0) {
        try {
            // Mettre à jour le taux ET recalculer le montant_fc pour TOUS les frais
            $stmt = $db->prepare("
                UPDATE frais 
                SET taux_change = :taux, 
                    montant_fc = montant * :taux2
            ");
            $stmt->execute(['taux' => $nouveau_taux, 'taux2' => $nouveau_taux]);
            
            $nb_modifies = $stmt->rowCount();
            $message = "Taux de change mis à jour avec succès ! <strong>{$nb_modifies}</strong> configuration(s) impactée(s). Nouveau taux : <strong>1\$ = " . number_format($nouveau_taux, 0, ',', ' ') . " FC</strong>";
            $message_type = 'success';
            
            // Mettre à jour le taux par défaut
            $taux_defaut = $nouveau_taux;
            
        } catch (PDOException $e) {
            $message = "Erreur lors de la mise à jour du taux : " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = "Le taux doit être supérieur à 0.";
        $message_type = 'warning';
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========

// Filtres
$filtre_faculte = isset($_GET['faculte']) ? (int)$_GET['faculte'] : 0;
$filtre_filiere = isset($_GET['filiere']) ? (int)$_GET['filiere'] : 0;
$filtre_promotion = isset($_GET['promotion']) ? (int)$_GET['promotion'] : 0;

// Facultés (pour selects et filtres)
$stmt = $db->query("SELECT * FROM facultes ORDER BY nom_faculte");
$facultes = $stmt->fetchAll();

// Filières avec leur faculté (pour le tableau)
$stmt = $db->query("
    SELECT fi.*, fa.nom_faculte, fa.id_faculte as fac_id
    FROM filieres fi 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    ORDER BY fa.nom_faculte, fi.nom_filiere
");
$all_filieres = $stmt->fetchAll();

// Filières filtrées par faculté (pour les selects)
if ($filtre_faculte > 0) {
    $stmt = $db->prepare("SELECT * FROM filieres WHERE id_faculte = :id_faculte ORDER BY nom_filiere");
    $stmt->execute(['id_faculte' => $filtre_faculte]);
    $filieres_filtrees = $stmt->fetchAll();
} else {
    $filieres_filtrees = $all_filieres;
}

// Promotions
$stmt = $db->query("SELECT * FROM promotions ORDER BY id_promotion");
$promotions = $stmt->fetchAll();

// Types de frais prédéfinis
$rubriques_frais = [
    'Minerval - Tranche 1',
    'Minerval - Tranche 2', 
    'Minerval - Tranche 3',
    'Minerval - Tranche 4',
    'Examen - 1ère Session',
    'Examen - 2ème Session',
    'Défense de Mémoire',
    'Frais de Toge',
    'Frais de Bibliothèque',
    'Frais de Stage',
    'Frais de Laboratoire',
    'Frais d\'Inscription Administrative',
    'Assurance Étudiant',
    'Contribution Développement',
    'Frais de Certification'
];

// Frais configurés avec toutes les jointures
$sql = "
    SELECT fr.*, fi.nom_filiere, fa.nom_faculte, fa.id_faculte, p.nom_promotion,
           (SELECT COUNT(*) FROM paiements WHERE id_frais = fr.id_frais) as nb_paiements
    FROM frais fr 
    JOIN filieres fi ON fr.id_filiere = fi.id_filiere 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    JOIN promotions p ON fr.id_promotion = p.id_promotion 
    WHERE 1=1
";
$params = [];

if ($filtre_faculte > 0) {
    $sql .= " AND fa.id_faculte = :faculte";
    $params['faculte'] = $filtre_faculte;
}
if ($filtre_filiere > 0) {
    $sql .= " AND fr.id_filiere = :filiere";
    $params['filiere'] = $filtre_filiere;
}
if ($filtre_promotion > 0) {
    $sql .= " AND fr.id_promotion = :promotion";
    $params['promotion'] = $filtre_promotion;
}

$sql .= " ORDER BY fa.nom_faculte, fi.nom_filiere, p.nom_promotion, fr.type_frais";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$frais_configures = $stmt->fetchAll();

// Taux de change par défaut (peut être modifié dynamiquement)
$taux_defaut = 2300;

// Récupérer le taux actuel depuis la BDD s'il existe
if (!empty($frais_configures)) {
    $taux_defaut = $frais_configures[0]['taux_change'] ?? 2300;
}

// Stats
$total_configs = count($frais_configures);
$total_montant_usd = array_sum(array_column($frais_configures, 'montant'));

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration des Frais - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/configuration_frais.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar_admin.php'; ?>
        <div class="main-content">
            <?php 
            $navbar_notif_non_lues = $notifications_non_lues;
            include 'includes/navbar_admin.php'; 
            ?>
            <main class="dashboard-content">
                
                <!-- En-tête -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h1 class="page-title">
                                <i class="fas fa-cogs"></i> Configuration des Frais Académiques
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Définissez les frais par <strong>Departement → Filière → Promotion</strong>. 
                                Taux de référence : <strong class="text-primary" id="tauxReference">1 $ = <?= number_format($taux_defaut, 0, ',', ' ') ?> FC</strong>
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <div class="btn-group-header">
                                <button class="btn btn-warning btn-taux-global" onclick="ouvrirModalTauxGlobal()" title="Mettre à jour le taux de change pour toutes les configurations">
                                    <i class="fas fa-exchange-alt"></i> Changer le taux global
                                </button>
                                <button class="btn btn-success btn-ajouter" onclick="ouvrirModalAjouter()">
                                    <i class="fas fa-plus-circle"></i> Ajouter une configuration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-mini-row">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-purple">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_configs ?></h4>
                            <p>Configurations actives</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4>$<?= number_format($total_montant_usd, 2, ',', ' ') ?></h4>
                            <p>Total configuré (USD)</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-blue">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= number_format($total_montant_usd * $taux_defaut, 0, ',', ' ') ?> FC</h4>
                            <p>Équivalent (<?= number_format($taux_defaut, 0) ?> FC/$)</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-section">
                    <form method="GET" action="" class="filtres-form">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-3 col-md-4 mb-2">
                                <label class="filtre-label"><i class="fas fa-university"></i> Departement </label>
                                <select name="faculte" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Tous les Departements </option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>" <?= $filtre_faculte === (int)$fac['id_faculte'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fac['nom_faculte']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-4 mb-2">
                                <label class="filtre-label"><i class="fas fa-layer-group"></i> Filière</label>
                                <select name="filiere" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes les filières</option>
                                    <?php foreach ($filieres_filtrees as $fil): ?>
                                        <option value="<?= $fil['id_filiere'] ?>" <?= $filtre_filiere === (int)$fil['id_filiere'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fil['nom_filiere']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-4 mb-2">
                                <label class="filtre-label"><i class="fas fa-graduation-cap"></i> Promotion</label>
                                <select name="promotion" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes les promotions</option>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>" <?= $filtre_promotion === (int)$promo['id_promotion'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($promo['nom_promotion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-12 mb-2">
                                <?php if ($filtre_faculte > 0 || $filtre_filiere > 0 || $filtre_promotion > 0): ?>
                                    <a href="configuration_frais.php" class="btn btn-outline-secondary btn-sm w-100">
                                        <i class="fas fa-times"></i> Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tableau -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h3><i class="fas fa-list"></i> Frais configurés</h3>
                        <span class="badge-count badge-purple"><?= $total_configs ?> config(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Type de Frais</th>
                                    <th>Departement</th>
                                    <th>Filière</th>
                                    <th>Promotion</th>
                                    <th>USD</th>
                                    <th>Équiv. FC</th>
                                    <th>Taux</th>
                                    <th>Année</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($frais_configures)): ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="empty-state py-4">
                                                <i class="fas fa-cogs fa-3x"></i>
                                                <h4 class="mt-3">Aucune configuration</h4>
                                                <p class="text-muted">Ajoutez votre première configuration de frais.</p>
                                                <button class="btn btn-success" onclick="ouvrirModalAjouter()">
                                                    <i class="fas fa-plus-circle"></i> Ajouter
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($frais_configures as $frais): ?>
                                        <tr>
                                            <td>
                                                <span class="type-frais-badge">
                                                    <?= htmlspecialchars($frais['type_frais']) ?>
                                                </span>
                                            </td>
                                            <td><small><?= htmlspecialchars($frais['nom_faculte']) ?></small></td>
                                            <td><small><?= htmlspecialchars($frais['nom_filiere']) ?></small></td>
                                            <td><span class="promo-badge"><?= htmlspecialchars($frais['nom_promotion']) ?></span></td>
                                            <td><strong class="text-success">$<?= number_format($frais['montant'], 2) ?></strong></td>
                                            <td><span class="fc-badge"><?= number_format($frais['montant_fc'], 0, ',', ' ') ?> FC</span></td>
                                            <td><small>1$ = <?= number_format($frais['taux_change'], 0) ?> FC</small></td>
                                            <td><small><?= htmlspecialchars($frais['annee_academique']) ?></small></td>
                                            <td>
                                                <button class="btn btn-outline-primary btn-action" 
                                                        onclick='ouvrirModalModifier(<?= json_encode($frais) ?>)'
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-action" 
                                                        onclick="confirmerSuppression(<?= $frais['id_frais'] ?>, '<?= htmlspecialchars(addslashes($frais['type_frais'])) ?>')"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ========== MODAL AJOUTER ========== -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Configurer un frais académique</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="formAjouter">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-tag"></i> Type de frais <span class="text-danger">*</span></label>
                                <select name="type_frais" class="form-select" required>
                                    <option value="">-- Choisir --</option>
                                    <?php foreach ($rubriques_frais as $rubrique): ?>
                                        <option value="<?= htmlspecialchars($rubrique) ?>"><?= htmlspecialchars($rubrique) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Année académique</label>
                                <input type="text" name="annee_academique" class="form-control" 
                                       value="<?= date('Y') . '-' . (date('Y') + 1) ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-university"></i> Departement </label>
                                <select class="form-select" id="selectFaculteAjout" onchange="chargerFilieresAjout()">
                                    <option value="">-- Choisir un Departement --</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>"><?= htmlspecialchars($fac['nom_faculte']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-layer-group"></i> Filière <span class="text-danger">*</span></label>
                                <select name="id_filiere" id="selectFiliereAjout" class="form-select" required>
                                    <option value="">-- Sélectionnez d'abord une faculté --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-graduation-cap"></i> Promotion <span class="text-danger">*</span></label>
                                <select name="id_promotion" class="form-select" required>
                                    <option value="">-- Choisir --</option>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>"><?= htmlspecialchars($promo['nom_promotion']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar-times"></i> Date limite</label>
                                <input type="date" name="date_limite" class="form-control">
                            </div>
                        </div>
                        
                        <!-- Section Devises -->
                        <div class="devise-section">
                            <h6 class="devise-title"><i class="fas fa-exchange-alt"></i> Configuration des montants (Taux dynamique)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-dollar-sign"></i> Montant en $</label>
                                    <input type="number" name="montant_usd" id="montantUsdAjout" 
                                           class="form-control form-control-lg" placeholder="200" 
                                           step="0.01" min="0.01" required
                                           oninput="calculerConversionAjout()">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-calculator"></i> Taux (1$ = ? FC)</label>
                                    <input type="number" name="taux_change" id="tauxChangeAjout" 
                                           class="form-control form-control-lg" value="<?= $taux_defaut ?>" 
                                           step="0.01" min="1" required
                                           oninput="calculerConversionAjout()">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-money-bill-wave"></i> Équivalent FC</label>
                                    <input type="text" id="montantFcAjout" class="form-control form-control-lg" 
                                           readonly style="background:#fffbe6; font-weight:700; font-size:1.1rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL MODIFIER ========== -->
    <div class="modal fade" id="modalModifier" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier la configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_frais" id="modIdFrais">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-tag"></i> Type de frais</label>
                                <select name="type_frais" id="modTypeFrais" class="form-select" required>
                                    <?php foreach ($rubriques_frais as $rubrique): ?>
                                        <option value="<?= htmlspecialchars($rubrique) ?>"><?= htmlspecialchars($rubrique) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-graduation-cap"></i> Promotion</label>
                                <select name="id_promotion" id="modPromotion" class="form-select" required>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>"><?= htmlspecialchars($promo['nom_promotion']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-university"></i> Faculté</label>
                                <select class="form-select" id="selectFaculteModif" onchange="chargerFilieresModif()">
                                    <option value="">-- Choisir --</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>"><?= htmlspecialchars($fac['nom_faculte']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-layer-group"></i> Filière <span class="text-danger">*</span></label>
                                <select name="id_filiere" id="selectFiliereModif" class="form-select" required>
                                    <option value="">-- Sélectionnez une faculté --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="devise-section">
                            <h6 class="devise-title"><i class="fas fa-exchange-alt"></i> Montants (modification dynamique)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-dollar-sign"></i> Montant $</label>
                                    <input type="number" name="montant_usd" id="modMontantUsd" 
                                           class="form-control form-control-lg" step="0.01" min="0.01" required
                                           oninput="calculerConversionModif()">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-calculator"></i> Taux (1$ = ? FC)</label>
                                    <input type="number" name="taux_change" id="modTauxChange" 
                                           class="form-control form-control-lg" step="0.01" min="1" required
                                           oninput="calculerConversionModif()">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-money-bill-wave"></i> Équivalent FC</label>
                                    <input type="text" id="modMontantFc" class="form-control form-control-lg" 
                                           readonly style="background:#fffbe6; font-weight:700; font-size:1.1rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL TAUX GLOBAL ========== -->
    <div class="modal fade" id="modalTauxGlobal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-exchange-alt"></i> Mise à jour du taux de change global
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="formTauxGlobal">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_taux_global">
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-sync-alt fa-3x text-warning mb-3"></i>
                            <h5>Modifier le taux pour toutes les configurations</h5>
                            <p class="text-muted">
                                Cette action mettra à jour le taux de change et recalculera l'équivalent en Francs 
                                pour <strong class="text-danger">toutes</strong> les configurations de frais existantes.
                            </p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong><?= $total_configs ?> configuration(s)</strong> seront impactées.
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-calculator"></i> Ancien taux de référence
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       value="1 $ = <?= number_format($taux_defaut, 0, ',', ' ') ?> FC" 
                                       readonly disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-edit"></i> Nouveau taux <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-warning">1$ =</span>
                                    <input type="number" name="nouveau_taux" id="nouveauTauxGlobal" 
                                           class="form-control form-control-lg" 
                                           value="<?= $taux_defaut ?>" 
                                           step="0.01" min="1" required
                                           oninput="previewImpactGlobal()">
                                    <span class="input-group-text">FC</span>
                                </div>
                                <small class="text-muted">Ex: 2500, 2800, 3000...</small>
                            </div>
                        </div>
                        
                        <!-- Aperçu de l'impact -->
                        <div class="impact-preview" id="impactPreview" style="display:none;">
                            <h6 class="mb-3"><i class="fas fa-eye"></i> Aperçu de l'impact</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Exemple</th>
                                            <th>Ancien FC</th>
                                            <th>Nouveau FC</th>
                                            <th>Différence</th>
                                        </tr>
                                    </thead>
                                    <tbody id="impactTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-warning" id="btnAppliquerTaux">
                            <i class="fas fa-check-circle"></i> Appliquer à toutes les configurations
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Données pour JS -->
    <script>
        const allFilieres = <?= json_encode($all_filieres) ?>;
        const tauxDefaut = <?= $taux_defaut ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/configuration_frais.js"></script>
</body>
</html>