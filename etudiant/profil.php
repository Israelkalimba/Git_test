<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('etudiant');

$db = Database::getInstance();
$etudiant_nom = $_SESSION['user_nom'] ?? 'Étudiant';
$etudiant_id_user = $_SESSION['user_id'] ?? 1;

// ========== RÉCUPÉRATION INFOS ÉTUDIANT ==========
$stmt = $db->prepare("
    SELECT e.*, u.nom, u.email, u.created_at, u.statut_compte,
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
    echo "<script>alert('Erreur : Profil étudiant introuvable.'); window.location.href='../logout.php?role=etudiant';</script>";
    exit();
}

$id_etudiant = $etudiant['id_etudiant'];
$matricule = $etudiant['matricule'];
$telephone = $etudiant['telephone'];
$email = $etudiant['email'];
$nom_filiere = $etudiant['nom_filiere'];
$nom_faculte = $etudiant['nom_faculte'];
$nom_promotion = $etudiant['nom_promotion'];
$date_inscription = $etudiant['created_at'];
$statut_compte = $etudiant['statut_compte'] ?? 'actif';

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// Modifier le profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nouveau_telephone = trim($_POST['telephone'] ?? '');
    
    if (empty($nouveau_telephone)) {
        $message = "Le numéro de téléphone est obligatoire.";
        $message_type = 'danger';
    } else {
        try {
            $stmt = $db->prepare("UPDATE etudiants SET telephone = :tel WHERE id_etudiant = :id");
            $stmt->execute(['tel' => $nouveau_telephone, 'id' => $id_etudiant]);
            
            // Journaliser
            try {
                $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'modification', 'mise_a_jour_profil', :desc, :ip)");
                $stmt->execute([
                    'uid' => $etudiant_id_user,
                    'desc' => "Profil étudiant mis à jour - Téléphone: {$nouveau_telephone}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
            } catch (PDOException $e) {}
            
            $telephone = $nouveau_telephone;
            $message = "✅ Profil mis à jour avec succès !";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Changer le mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $ancien_mdp = $_POST['ancien_mdp'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';
    
    $errors = [];
    
    // Récupérer le mot de passe actuel
    $stmt = $db->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $etudiant_id_user]);
    $user = $stmt->fetch();
    
    $hashed_ancien = hash('sha256', $ancien_mdp);
    if ($hashed_ancien !== ($user['mot_de_passe'] ?? '')) {
        $errors[] = "L'ancien mot de passe est incorrect.";
    }
    
    if (strlen($nouveau_mdp) < 6) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    }
    
    if ($nouveau_mdp !== $confirmer_mdp) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    if ($ancien_mdp === $nouveau_mdp) {
        $errors[] = "Le nouveau mot de passe doit être différent de l'ancien.";
    }
    
    if (empty($errors)) {
        $hashed_nouveau = hash('sha256', $nouveau_mdp);
        $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id_utilisateur = :id");
        $stmt->execute(['mdp' => $hashed_nouveau, 'id' => $etudiant_id_user]);
        
        try {
            $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'securite', 'changement_mot_de_passe', :desc, :ip)");
            $stmt->execute([
                'uid' => $etudiant_id_user,
                'desc' => "Mot de passe étudiant modifié",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (PDOException $e) {}
        
        $message = "🔒 Mot de passe changé avec succès ! Veuillez vous reconnecter.";
        $message_type = 'success';
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

// ========== STATISTIQUES ==========
// Total payé
$stmt = $db->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes'");
$stmt->execute(['id' => $id_etudiant]);
$total_paye = $stmt->fetch()['total'] ?? 0;

// Nombre de paiements
$stmt = $db->prepare("SELECT COUNT(*) as total FROM paiements WHERE id_etudiant = :id AND statut = 'succes'");
$stmt->execute(['id' => $id_etudiant]);
$nb_paiements = $stmt->fetch()['total'] ?? 0;

// Total frais configurés
$stmt = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(montant), 0) as montant FROM frais WHERE id_filiere = :filiere AND id_promotion = :promo");
$stmt->execute(['filiere' => $etudiant['id_filiere'], 'promo' => $etudiant['id_promotion']]);
$frais_config = $stmt->fetch();
$nb_frais = $frais_config['total'] ?? 0;
$total_frais = $frais_config['montant'] ?? 0;

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt->execute(['id' => $etudiant_id_user]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt_nav = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt_nav->execute(['id' => $etudiant_id_user]);
$navbar_notifications = $stmt_nav->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Étudiant ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/etudiant/dashboard_etudiant.css">
    <link rel="stylesheet" href="../assets/css/etudiant/profil_etudiant.css">
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
                
                <!-- En-tête -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-user-circle"></i> Mon Profil
                    </h1>
                    <p class="page-subtitle">
                        <i class="fas fa-info-circle"></i> 
                        Consultez et gérez vos informations personnelles.
                    </p>
                </div>

                <!-- Message -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="profil-grid-etu">
                    <!-- Carte Profil -->
                    <div class="profil-card-etu">
                        <div class="profil-cover-etu"></div>
                        <div class="profil-avatar-wrapper-etu">
                            <div class="profil-avatar-etu">
                                <?= strtoupper(substr($etudiant_nom, 0, 2)) ?>
                            </div>
                            <div class="profil-status-etu online"></div>
                        </div>
                        <div class="profil-card-body-etu text-center">
                            <h2><?= htmlspecialchars($etudiant_nom) ?></h2>
                            <p class="profil-role-etu">
                                <span class="role-badge-etu">
                                    <i class="fas fa-user-graduate"></i> Étudiant
                                </span>
                            </p>
                            <div class="profil-info-list">
                                <div class="profil-info-item">
                                    <i class="fas fa-id-card"></i>
                                    <span>Matricule : <strong><?= htmlspecialchars($matricule) ?></strong></span>
                                </div>
                                <div class="profil-info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?= htmlspecialchars($email) ?></span>
                                </div>
                                <div class="profil-info-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?= htmlspecialchars($telephone ?? 'Non renseigné') ?></span>
                                </div>
                                <div class="profil-info-item">
                                    <i class="fas fa-university"></i>
                                    <span><?= htmlspecialchars($nom_faculte) ?></span>
                                </div>
                                <div class="profil-info-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span><?= htmlspecialchars($nom_filiere) ?></span>
                                </div>
                                <div class="profil-info-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?= htmlspecialchars($nom_promotion) ?></span>
                                </div>
                                <div class="profil-info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Inscrit le <?= date('d/m/Y', strtotime($date_inscription ?? 'now')) ?></span>
                                </div>
                                <div class="profil-info-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>
                                        Statut : 
                                        <span class="statut-badge-etu <?= $statut_compte === 'actif' ? 'statut-actif-etu' : 'statut-inactif-etu' ?>">
                                            <?= ucfirst($statut_compte) ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="profil-card-footer-etu">
                            <div class="profil-stats-row-etu">
                                <div class="profil-stat-item-etu">
                                    <h4>$<?= number_format($total_paye, 2) ?></h4>
                                    <p>Total payé</p>
                                </div>
                                <div class="profil-stat-item-etu">
                                    <h4><?= $nb_paiements ?></h4>
                                    <p>Paiements</p>
                                </div>
                                <div class="profil-stat-item-etu">
                                    <h4><?= $nb_frais ?></h4>
                                    <p>Frais configurés</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne droite -->
                    <div class="profil-details-etu">
                        <!-- Informations personnelles -->
                        <div class="settings-card-etu">
                            <div class="settings-card-header-etu">
                                <h3><i class="fas fa-user-edit"></i> Informations personnelles</h3>
                            </div>
                            <div class="settings-card-body-etu">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i> Nom complet
                                        </label>
                                        <input type="text" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($etudiant_nom) ?>" readonly disabled>
                                        <small class="text-muted">Le nom est géré par l'administration.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-envelope"></i> Adresse email
                                        </label>
                                        <input type="email" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($email) ?>" readonly disabled>
                                        <small class="text-muted">L'email est géré par l'administration.</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-phone"></i> Numéro de téléphone <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="telephone" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($telephone ?? '') ?>" 
                                               placeholder="+243..." required>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> Pour contacter l'etudiant
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-id-card"></i> Matricule
                                        </label>
                                        <input type="text" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($matricule) ?>" readonly disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-layer-group"></i> Filière & Promotion
                                        </label>
                                        <input type="text" class="form-control form-control-lg" 
                                               value="<?= htmlspecialchars($nom_filiere) ?> - <?= htmlspecialchars($nom_promotion) ?>" readonly disabled>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Mettre à jour le téléphone
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Changer mot de passe -->
                        <div class="settings-card-etu mt-4">
                            <div class="settings-card-header-etu">
                                <h3><i class="fas fa-lock"></i> Changer le mot de passe</h3>
                            </div>
                            <div class="settings-card-body-etu">
                                <form method="POST" action="" id="formPassword">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-key"></i> Mot de passe actuel
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="ancien_mdp" class="form-control form-control-lg" 
                                                   placeholder="••••••••" required id="ancienMdp">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" 
                                                    data-target="ancienMdp">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i> Nouveau mot de passe
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="nouveau_mdp" class="form-control form-control-lg" 
                                                   placeholder="••••••••" required id="nouveauMdp" minlength="6">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" 
                                                    data-target="nouveauMdp">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Minimum 6 caractères</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-check-circle"></i> Confirmer le mot de passe
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="confirmer_mdp" class="form-control form-control-lg" 
                                                   placeholder="••••••••" required id="confirmerMdp">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" 
                                                    data-target="confirmerMdp">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Indicateur de force -->
                                    <div class="password-strength-etu mb-3" id="passwordStrength" style="display:none;">
                                        <div class="strength-bar-etu">
                                            <div class="strength-fill-etu" id="strengthFill"></div>
                                        </div>
                                        <small class="strength-text-etu" id="strengthText"></small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Changer le mot de passe
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Résumé financier -->
                        <div class="settings-card-etu mt-4">
                            <div class="settings-card-header-etu">
                                <h3><i class="fas fa-chart-pie"></i> Résumé financier</h3>
                            </div>
                            <div class="settings-card-body-etu">
                                <div class="resume-financier">
                                    <div class="resume-item">
                                        <span class="resume-label">Total à payer</span>
                                        <strong class="resume-value">$<?= number_format($total_frais, 2) ?></strong>
                                    </div>
                                    <div class="resume-item">
                                        <span class="resume-label">Total payé</span>
                                        <strong class="resume-value text-success">$<?= number_format($total_paye, 2) ?></strong>
                                    </div>
                                    <div class="resume-item">
                                        <span class="resume-label">Reste à payer</span>
                                        <strong class="resume-value text-warning">$<?= number_format(max(0, $total_frais - $total_paye), 2) ?></strong>
                                    </div>
                                    <div class="resume-item">
                                        <span class="resume-label">Progression</span>
                                        <div class="progress" style="height:8px;flex:1;margin-left:10px;">
                                            <div class="progress-bar bg-success" style="width:<?= $total_frais > 0 ? round(($total_paye/$total_frais)*100, 1) : 0 ?>%"></div>
                                        </div>
                                        <strong><?= $total_frais > 0 ? round(($total_paye/$total_frais)*100, 1) : 0 ?>%</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/etudiant/dashboard_etudiant.js"></script>
    <script src="../assets/js/etudiant/profil_etudiant.js"></script>
</body>
</html>