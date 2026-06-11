<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== TRAITEMENT CRUD ==========
$message = '';
$message_type = '';

// AJOUTER UN ÉTUDIANT (crée le compte utilisateur + l'étudiant)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matricule = trim($_POST['matricule'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $id_filiere = (int)($_POST['id_filiere'] ?? 0);
    $id_promotion = (int)($_POST['id_promotion'] ?? 0);
    $password = trim($_POST['password'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (empty($matricule)) $errors[] = "Le matricule est obligatoire.";
    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire.";
    if ($id_filiere <= 0) $errors[] = "Veuillez sélectionner une filière.";
    if ($id_promotion <= 0) $errors[] = "Veuillez sélectionner une promotion.";
    if (empty($password) || strlen($password) < 4) $errors[] = "Mot de passe requis (min 4 caractères).";
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // 1. Créer le compte utilisateur
            $hashedPassword = hash('sha256', $password);
            $stmt = $db->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mdp, 'etudiant')");
            $stmt->execute(['nom' => $nom, 'email' => $email, 'mdp' => $hashedPassword]);
            $id_utilisateur = $db->lastInsertId();
            
            // 2. Créer l'étudiant
            $stmt = $db->prepare("INSERT INTO etudiants (id_utilisateur, matricule, telephone, id_filiere, id_promotion) VALUES (:id_user, :matricule, :tel, :filiere, :promo)");
            $stmt->execute([
                'id_user' => $id_utilisateur,
                'matricule' => $matricule,
                'tel' => $telephone,
                'filiere' => $id_filiere,
                'promo' => $id_promotion
            ]);
            
            // 3. Notification à l'admin
            $stmt = $db->prepare("INSERT INTO notifications (id_utilisateur, message, statut) VALUES (:admin_id, :msg, 'non_lu')");
            $stmt->execute([
                'admin_id' => $admin_id,
                'msg' => "Nouvel étudiant inscrit : {$nom} (Matricule: {$matricule})"
            ]);
            
            $db->commit();
            $message = "Étudiant {$nom} inscrit avec succès ! Matricule : {$matricule}";
            $message_type = 'success';
            
        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    $message = "Cet email est déjà utilisé.";
                } elseif (strpos($e->getMessage(), 'matricule') !== false) {
                    $message = "Ce matricule existe déjà.";
                } else {
                    $message = "Erreur de contrainte d'unicité.";
                }
            } else {
                $message = "Erreur : " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'warning';
    }
}

// MODIFIER UN ÉTUDIANT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id_etudiant = (int)($_POST['id_etudiant'] ?? 0);
    $id_utilisateur = (int)($_POST['id_utilisateur'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matricule = trim($_POST['matricule'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $id_filiere = (int)($_POST['id_filiere'] ?? 0);
    $id_promotion = (int)($_POST['id_promotion'] ?? 0);
    
    if ($id_etudiant > 0 && $id_utilisateur > 0 && !empty($nom) && !empty($email)) {
        try {
            $db->beginTransaction();
            
            // Mettre à jour l'utilisateur
            $stmt = $db->prepare("UPDATE utilisateurs SET nom = :nom, email = :email WHERE id_utilisateur = :id");
            $stmt->execute(['nom' => $nom, 'email' => $email, 'id' => $id_utilisateur]);
            
            // Mettre à jour l'étudiant
            $stmt = $db->prepare("UPDATE etudiants SET matricule = :mat, telephone = :tel, id_filiere = :fil, id_promotion = :promo WHERE id_etudiant = :id");
            $stmt->execute([
                'mat' => $matricule, 'tel' => $telephone,
                'fil' => $id_filiere, 'promo' => $id_promotion, 'id' => $id_etudiant
            ]);
            
            $db->commit();
            $message = "Étudiant modifié avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "Erreur lors de la modification.";
            $message_type = 'danger';
        }
    }
}

// RÉINITIALISER MOT DE PASSE
if (isset($_GET['action']) && $_GET['action'] === 'reset_password' && isset($_GET['id_user'])) {
    $id_utilisateur = (int)$_GET['id_user'];
    $nouveau_mdp = 'istam' . rand(1000, 9999);
    $hashed = hash('sha256', $nouveau_mdp);
    
    $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id_utilisateur = :id");
    $stmt->execute(['mdp' => $hashed, 'id' => $id_utilisateur]);
    
    $message = "Mot de passe réinitialisé. Nouveau mot de passe : <strong>{$nouveau_mdp}</strong>";
    $message_type = 'warning';
}

// SUPPRIMER UN ÉTUDIANT
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id']) && isset($_GET['id_user'])) {
    $id_etudiant = (int)$_GET['id'];
    $id_utilisateur = (int)$_GET['id_user'];
    
    try {
        $db->beginTransaction();
        
        // Supprimer d'abord l'étudiant
        $stmt = $db->prepare("DELETE FROM etudiants WHERE id_etudiant = :id");
        $stmt->execute(['id' => $id_etudiant]);
        
        // Puis supprimer l'utilisateur (CASCADE gère le reste)
        $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = :id AND role = 'etudiant'");
        $stmt->execute(['id' => $id_utilisateur]);
        
        $db->commit();
        $message = "Étudiant supprimé avec succès !";
        $message_type = 'success';
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Erreur lors de la suppression : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========

// Filtres
$filtre_faculte = isset($_GET['faculte']) ? (int)$_GET['faculte'] : 0;
$filtre_filiere = isset($_GET['filiere']) ? (int)$_GET['filiere'] : 0;
$filtre_promotion = isset($_GET['promotion']) ? (int)$_GET['promotion'] : 0;
$search = trim($_GET['search'] ?? '');

// Facultés pour les filtres
$stmt = $db->query("SELECT * FROM facultes ORDER BY nom_faculte");
$facultes = $stmt->fetchAll();

// Filières (filtrées si une faculté est sélectionnée)
if ($filtre_faculte > 0) {
    $stmt = $db->prepare("SELECT * FROM filieres WHERE id_faculte = :id_faculte ORDER BY nom_filiere");
    $stmt->execute(['id_faculte' => $filtre_faculte]);
} else {
    $stmt = $db->query("SELECT * FROM filieres ORDER BY nom_filiere");
}
$filieres = $stmt->fetchAll();

// Promotions
$stmt = $db->query("SELECT * FROM promotions ORDER BY id_promotion");
$promotions = $stmt->fetchAll();

// Requête étudiants avec tous les filtres
$sql = "
    SELECT e.*, u.nom, u.email, u.id_utilisateur, u.created_at,
           fi.nom_filiere, fa.nom_faculte, fa.id_faculte,
           p.nom_promotion,
           COALESCE(SUM(pa.montant_paye), 0) as total_paye,
           COUNT(DISTINCT pa.id_paiement) as nb_paiements
    FROM etudiants e 
    JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
    JOIN filieres fi ON e.id_filiere = fi.id_filiere 
    JOIN facultes fa ON fi.id_faculte = fa.id_faculte 
    JOIN promotions p ON e.id_promotion = p.id_promotion 
    LEFT JOIN paiements pa ON e.id_etudiant = pa.id_etudiant AND pa.statut = 'succes'
    WHERE 1=1
";
$params = [];

if ($filtre_faculte > 0) {
    $sql .= " AND fa.id_faculte = :faculte";
    $params['faculte'] = $filtre_faculte;
}
if ($filtre_filiere > 0) {
    $sql .= " AND fi.id_filiere = :filiere";
    $params['filiere'] = $filtre_filiere;
}
if ($filtre_promotion > 0) {
    $sql .= " AND e.id_promotion = :promotion";
    $params['promotion'] = $filtre_promotion;
}
if (!empty($search)) {
    $sql .= " AND (u.nom LIKE :search OR e.matricule LIKE :search2 OR u.email LIKE :search3)";
    $params['search'] = "%{$search}%";
    $params['search2'] = "%{$search}%";
    $params['search3'] = "%{$search}%";
}

$sql .= " GROUP BY e.id_etudiant ORDER BY u.nom";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$etudiants = $stmt->fetchAll();

// Stats
$total_etudiants = count($etudiants);
$total_paye_global = array_sum(array_column($etudiants, 'total_paye'));

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
    <title>Gestion des Étudiants - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/gestion_etudiants.css">
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
                                <i class="fas fa-user-graduate"></i> Gestion des Étudiants
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Inscrivez et gérez les étudiants. Chaque étudiant aura un compte pour payer ses frais en ligne.
                            </p>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <button class="btn btn-primary btn-ajouter" onclick="ouvrirModalAjouter()">
                                <i class="fas fa-user-plus"></i> Inscrire un étudiant
                            </button>
                            <a href="gestion_utilisateurs.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-users-cog"></i> Personnel
                            </a>
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

                <!-- Stats rapides -->
                <div class="stats-mini-row">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= $total_etudiants ?></h4>
                            <p>Étudiants</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4><?= count(array_filter($etudiants, fn($e) => $e['nb_paiements'] > 0)) ?></h4>
                            <p>Ayant payé</p>
                        </div>
                    </div>
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon bg-purple">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h4>$<?= number_format($total_paye_global, 2, ',', ' ') ?></h4>
                            <p>Total collecté</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filtres-section">
                    <form method="GET" action="" class="filtres-form" id="formFiltres">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-2 col-md-3">
                                <label class="filtre-label"><i class="fas fa-university"></i> Faculté</label>
                                <select name="faculte" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>" <?= $filtre_faculte === (int)$fac['id_faculte'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fac['nom_faculte']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3">
                                <label class="filtre-label"><i class="fas fa-layer-group"></i> Filière</label>
                                <select name="filiere" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes</option>
                                    <?php foreach ($filieres as $fil): ?>
                                        <option value="<?= $fil['id_filiere'] ?>" <?= $filtre_filiere === (int)$fil['id_filiere'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fil['nom_filiere']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-3">
                                <label class="filtre-label"><i class="fas fa-graduation-cap"></i> Promotion</label>
                                <select name="promotion" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Toutes</option>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>" <?= $filtre_promotion === (int)$promo['id_promotion'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($promo['nom_promotion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                <label class="filtre-label"><i class="fas fa-search"></i> Recherche</label>
                                <input type="text" name="search" class="form-control form-control-sm" 
                                       placeholder="Nom, matricule, email..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-lg-3 col-md-12">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <?php if ($filtre_faculte > 0 || $filtre_filiere > 0 || $filtre_promotion > 0 || !empty($search)): ?>
                                    <a href="gestion_etudiants.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
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
                        <h3><i class="fas fa-list"></i> Liste des étudiants</h3>
                        <span class="badge-count badge-blue"><?= $total_etudiants ?> étudiant(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table" id="tableEtudiants">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom & Email</th>
                                    <th>Téléphone</th>
                                    <th>Filière</th>
                                    <th>Promotion</th>
                                    <th>Paiements</th>
                                    <th>Total Payé</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($etudiants)): ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state py-5">
                                                <i class="fas fa-user-graduate fa-3x"></i>
                                                <h4 class="mt-3">Aucun étudiant trouvé</h4>
                                                <p class="text-muted">
                                                    <?= (!empty($search) || $filtre_faculte > 0) ? "Aucun résultat pour ces filtres." : "Commencez par inscrire votre premier étudiant." ?>
                                                </p>
                                                <button class="btn btn-primary" onclick="ouvrirModalAjouter()">
                                                    <i class="fas fa-user-plus"></i> Inscrire un étudiant
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etudiants as $etu): ?>
                                        <tr>
                                            <td><code class="matricule-code"><?= htmlspecialchars($etu['matricule']) ?></code></td>
                                            <td>
                                                <div class="etudiant-info">
                                                    <div class="etudiant-avatar-sm">
                                                        <?= strtoupper(substr($etu['nom'], 0, 2)) ?>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($etu['nom']) ?></strong>
                                                        <small class="d-block text-muted"><?= htmlspecialchars($etu['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><small><?= htmlspecialchars($etu['telephone']) ?></small></td>
                                            <td><small><?= htmlspecialchars($etu['nom_filiere']) ?></small></td>
                                            <td><span class="promo-badge"><?= htmlspecialchars($etu['nom_promotion']) ?></span></td>
                                            <td><span class="badge-paiements"><?= $etu['nb_paiements'] ?></span></td>
                                            <td>
                                                <strong class="<?= $etu['total_paye'] > 0 ? 'text-success' : 'text-muted' ?>">
                                                    $<?= number_format($etu['total_paye'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-action" 
                                                            onclick="ouvrirModalModifier(<?= htmlspecialchars(json_encode($etu)) ?>)"
                                                            title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?action=reset_password&id_user=<?= $etu['id_utilisateur'] ?>" 
                                                       class="btn btn-outline-warning btn-action" 
                                                       title="Réinitialiser MDP"
                                                       onclick="return confirm('Réinitialiser le mot de passe de <?= htmlspecialchars(addslashes($etu['nom'])) ?> ?')">
                                                        <i class="fas fa-key"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger btn-action" 
                                                            onclick="confirmerSuppression(
                                                                <?= $etu['id_etudiant'] ?>, 
                                                                <?= $etu['id_utilisateur'] ?>, 
                                                                '<?= htmlspecialchars(addslashes($etu['nom'])) ?>',
                                                                '<?= htmlspecialchars(addslashes($etu['matricule'])) ?>'
                                                            )"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i> Inscrire un nouvel étudiant
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="formAjouter">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Nom complet <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" placeholder="Ex: Jean Dupont" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="jean@exemple.com" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="fas fa-id-card"></i> Matricule <span class="text-danger">*</span></label>
                                <input type="text" name="matricule" class="form-control" placeholder="Ex: ISTAM2024001" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="fas fa-phone"></i> Téléphone <span class="text-danger">*</span></label>
                                <input type="text" name="telephone" class="form-control" placeholder="+243..." required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="fas fa-lock"></i> Mot de passe <span class="text-danger">*</span></label>
                                <input type="text" name="password" class="form-control" value="istam2024" required>
                                <small class="text-muted">Mot de passe par défaut : istam2024</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-university"></i> Faculté <span class="text-danger">*</span></label>
                                <select class="form-select" id="selectFaculteAjout" onchange="chargerFilieresAjout()" required>
                                    <option value="">-- Choisir une faculté --</option>
                                    <?php foreach ($facultes as $fac): ?>
                                        <option value="<?= $fac['id_faculte'] ?>"><?= htmlspecialchars($fac['nom_faculte']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-layer-group"></i> Filière <span class="text-danger">*</span></label>
                                <select name="id_filiere" id="selectFiliereAjout" class="form-select" required>
                                    <option value="">-- Choisir d'abord une faculté --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-graduation-cap"></i> Promotion <span class="text-danger">*</span></label>
                                <select name="id_promotion" class="form-select" required>
                                    <option value="">-- Choisir une promotion --</option>
                                    <?php foreach ($promotions as $promo): ?>
                                        <option value="<?= $promo['id_promotion'] ?>"><?= htmlspecialchars($promo['nom_promotion']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Inscrire l'étudiant
                        </button>
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
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Modifier l'étudiant
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_etudiant" id="modIdEtudiant">
                        <input type="hidden" name="id_utilisateur" id="modIdUtilisateur">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Nom complet</label>
                                <input type="text" name="nom" id="modNom" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" id="modEmail" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-id-card"></i> Matricule</label>
                                <input type="text" name="matricule" id="modMatricule" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-phone"></i> Téléphone</label>
                                <input type="text" name="telephone" id="modTelephone" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-layer-group"></i> Filière</label>
                                <select name="id_filiere" id="modFiliere" class="form-select" required>
                                    <?php foreach ($filieres as $fil): ?>
                                        <option value="<?= $fil['id_filiere'] ?>"><?= htmlspecialchars($fil['nom_filiere']) ?></option>
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL SUPPRESSION ========== -->
    <div class="modal fade" id="modalSupprimer" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-user-slash fa-4x text-danger mb-3"></i>
                    <h5>Supprimer cet étudiant ?</h5>
                    <p class="text-muted" id="supprimerInfo"></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Cette action supprimera également le compte utilisateur et est irréversible.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" class="btn btn-danger" id="btnConfirmerSuppression">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/gestion_etudiants.js"></script>
</body>
</html>