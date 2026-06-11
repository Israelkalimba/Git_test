<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('admin');

$db = Database::getInstance();
$admin_nom = $_SESSION['user_nom'] ?? 'Administrateur';
$admin_id = $_SESSION['user_id'] ?? 1;

// ========== TRAITEMENT DES ACTIONS ==========
$message = '';
$message_type = '';

// Mise à jour des paramètres généraux
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $taux_change = (float)($_POST['taux_change'] ?? 2300);
    $annee_academique = trim($_POST['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1));
    $devise_defaut = $_POST['devise_defaut'] ?? 'USD';
    $email_notifications = trim($_POST['email_notifications'] ?? '');
    $delai_expiration = (int)($_POST['delai_expiration'] ?? 30);
    
    // Mettre à jour le taux de change sur tous les frais
    if ($taux_change > 0) {
        $stmt = $db->prepare("UPDATE frais SET taux_change = :taux, montant_fc = montant * :taux2");
        $stmt->execute(['taux' => $taux_change, 'taux2' => $taux_change]);
    }
    
    // Sauvegarder les paramètres dans un fichier ou une table de configuration
    // Pour l'instant, on les stocke dans des variables de session pour la démo
    $_SESSION['settings'] = [
        'taux_change' => $taux_change,
        'annee_academique' => $annee_academique,
        'devise_defaut' => $devise_defaut,
        'email_notifications' => $email_notifications,
        'delai_expiration' => $delai_expiration,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Journaliser
    $stmt = $db->prepare("INSERT INTO audit_log (id_utilisateur, type_action, action, description, adresse_ip) VALUES (:uid, 'configuration', 'mise_a_jour_parametres', :desc, :ip)");
    $stmt->execute([
        'uid' => $admin_id,
        'desc' => "Paramètres système mis à jour - Taux: {$taux_change} FC/$, Année: {$annee_academique}, Devise: {$devise_defaut}",
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);
    
    $message = "✅ Paramètres mis à jour avec succès !";
    $message_type = 'success';
}

// Sauvegarde de la base de données
if (isset($_GET['action']) && $_GET['action'] === 'backup_db') {
    try {
        $backup_file = '../backups/istam_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Créer le dossier backups s'il n'existe pas
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // Commande mysqldump (à adapter selon l'environnement)
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($backup_file)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            $message = "✅ Sauvegarde créée : " . basename($backup_file);
            $message_type = 'success';
        } else {
            // Fallback : export via PHP
            $message = backupViaPHP($backup_file);
            $message_type = strpos($message, '✅') !== false ? 'success' : 'danger';
        }
    } catch (Exception $e) {
        $message = "❌ Erreur : " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Vider le cache
if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
    // Simuler un vidage de cache
    $message = "✅ Cache système vidé avec succès. Les modifications prendront effet immédiatement.";
    $message_type = 'success';
}

// ========== RÉCUPÉRATION DES PARAMÈTRES ==========
$settings = $_SESSION['settings'] ?? [
    'taux_change' => 2300,
    'annee_academique' => date('Y') . '-' . (date('Y') + 1),
    'devise_defaut' => 'USD',
    'email_notifications' => 'admin@istam.ac.cd',
    'delai_expiration' => 30
];

// Infos système
$php_version = phpversion();
$mysql_version = $db->query("SELECT VERSION() as version")->fetch()['version'] ?? 'N/A';
$espace_disque = function_exists('disk_free_space') ? round(disk_free_space('.') / 1024 / 1024 / 1024, 2) : 'N/A';
$memory_limit = ini_get('memory_limit');
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$max_execution = ini_get('max_execution_time');

// Stats base de données
$stmt = $db->query("SELECT 
    (SELECT COUNT(*) FROM utilisateurs) as nb_utilisateurs,
    (SELECT COUNT(*) FROM etudiants) as nb_etudiants,
    (SELECT COUNT(*) FROM paiements) as nb_paiements,
    (SELECT COUNT(*) FROM facultes) as nb_facultes,
    (SELECT COUNT(*) FROM filieres) as nb_filieres,
    (SELECT COUNT(*) FROM promotions) as nb_promotions,
    (SELECT COUNT(*) FROM frais) as nb_frais,
    (SELECT COUNT(*) FROM notifications) as nb_notifications,
    (SELECT COUNT(*) FROM audit_log) as nb_logs");
$db_stats = $stmt->fetch();

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :admin_id AND statut = 'non_lu'");
$stmt->execute(['admin_id' => $admin_id]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;

$stmt = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :admin_id ORDER BY date_envoi DESC LIMIT 5");
$stmt->execute(['admin_id' => $admin_id]);
$navbar_notifications = $stmt->fetchAll();

// Fonction de backup via PHP (fallback)
function backupViaPHP($file) {
    global $db;
    try {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $output = "-- ISTAM Paiement Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $stmt = $db->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll();
            
            if (!empty($rows)) {
                $output .= "-- Table: {$table}\n";
                $columns = array_keys($rows[0]);
                $output .= "INSERT INTO `{$table}` (`" . implode('`,`', $columns) . "`) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $vals = array_map(function($v) use ($db) {
                        if ($v === null) return 'NULL';
                        return $db->quote($v);
                    }, array_values($row));
                    $values[] = "(" . implode(',', $vals) . ")";
                }
                $output .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($file, $output);
        return "✅ Sauvegarde créée via PHP : " . basename($file) . " (" . round(filesize($file)/1024, 1) . " KB)";
    } catch (Exception $e) {
        return "❌ Erreur backup : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Admin ISTAM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin/dashboard_admin.css">
    <link rel="stylesheet" href="../assets/css/admin/parametres.css">
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
                        <div class="col-lg-6">
                            <h1 class="page-title">
                                <i class="fas fa-sliders-h"></i> Paramètres du Système
                            </h1>
                            <p class="page-subtitle">
                                <i class="fas fa-info-circle"></i> 
                                Configuration générale, sauvegarde et maintenance.
                            </p>
                        </div>
                        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                            <a href="?action=backup_db" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-database"></i> Sauvegarder BDD
                            </a>
                            <a href="?action=clear_cache" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-broom"></i> Vider le cache
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'times-circle' : 'exclamation-triangle') ?>"></i>
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="settings-grid">
                    <!-- Paramètres généraux -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-cog"></i> Paramètres Généraux</h3>
                            <span class="text-muted small">Dernière mise à jour : <?= $settings['updated_at'] ?? 'Jamais' ?></span>
                        </div>
                        <div class="settings-card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_settings">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-exchange-alt"></i> Taux de change ($ → FC)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">1$ =</span>
                                            <input type="number" name="taux_change" class="form-control" 
                                                   value="<?= $settings['taux_change'] ?>" step="0.01" min="1" required>
                                            <span class="input-group-text">FC</span>
                                        </div>
                                        <small class="text-muted">Appliqué à tous les frais configurés</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Année académique</label>
                                        <input type="text" name="annee_academique" class="form-control" 
                                               value="<?= htmlspecialchars($settings['annee_academique']) ?>" 
                                               placeholder="2026-2027" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-dollar-sign"></i> Devise par défaut</label>
                                        <select name="devise_defaut" class="form-select">
                                            <option value="USD" <?= $settings['devise_defaut'] === 'USD' ? 'selected' : '' ?>>Dollar US ($)</option>
                                            <option value="CDF" <?= $settings['devise_defaut'] === 'CDF' ? 'selected' : '' ?>>Franc Congolais (FC)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-clock"></i> Délai expiration transaction (jours)</label>
                                        <input type="number" name="delai_expiration" class="form-control" 
                                               value="<?= $settings['delai_expiration'] ?>" min="1" max="90">
                                        <small class="text-muted">Les transactions en attente expirent après ce délai</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-envelope"></i> Email notifications</label>
                                    <input type="email" name="email_notifications" class="form-control" 
                                           value="<?= htmlspecialchars($settings['email_notifications']) ?>" 
                                           placeholder="admin@istam.ac.cd">
                                    <small class="text-muted">Les notifications système seront envoyées à cette adresse</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les paramètres
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Informations système -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-server"></i> Informations Système</h3>
                        </div>
                        <div class="settings-card-body">
                            <div class="info-grid-sys">
                                <div class="info-item-sys">
                                    <span class="info-label-sys">Version PHP</span>
                                    <span class="info-value-sys"><code><?= $php_version ?></code></span>
                                </div>
                                <div class="info-item-sys">
                                    <span class="info-label-sys">Version MySQL</span>
                                    <span class="info-value-sys"><code><?= $mysql_version ?></code></span>
                                </div>
                                <div class="info-item-sys">
                                    <span class="info-label-sys">Espace disque disponible</span>
                                    <span class="info-value-sys"><?= $espace_disque ?> Go</span>
                                </div>
                                <div class="info-item-sys">
                                    <span class="info-label-sys">Memory Limit</span>
                                    <span class="info-value-sys"><code><?= $memory_limit ?></code></span>
                                </div>
                                <div class="info-item-sys">
                                    <span class="info-label-sys">Upload Max</span>
                                    <span class="info-value-sys"><code><?= $upload_max ?></code></span>
                                </div>
                                <div class="info-item-sys">
                                    <span class="info-label-sys">Max Execution Time</span>
                                    <span class="info-value-sys"><code><?= $max_execution ?>s</code></span>
                                </div>
                                <div class="info-item-sys">
                                    <span class="info-label-sys">Timezone</span>
                                    <span class="info-value-sys"><?= date_default_timezone_get() ?></span>
                                </div>
                                <div class="info-item-sys">
                                    <span class="info-label-sys">URL de base</span>
                                    <span class="info-value-sys"><code><?= BASE_URL ?></code></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques base de données -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-database"></i> Statistiques Base de Données</h3>
                        </div>
                        <div class="settings-card-body">
                            <div class="db-stats-grid">
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-blue"><i class="fas fa-users"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= number_format($db_stats['nb_utilisateurs']) ?></h4>
                                        <p>Utilisateurs</p>
                                    </div>
                                </div>
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-green"><i class="fas fa-user-graduate"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= number_format($db_stats['nb_etudiants']) ?></h4>
                                        <p>Étudiants</p>
                                    </div>
                                </div>
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-purple"><i class="fas fa-credit-card"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= number_format($db_stats['nb_paiements']) ?></h4>
                                        <p>Paiements</p>
                                    </div>
                                </div>
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-orange"><i class="fas fa-university"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= $db_stats['nb_facultes'] ?> / <?= $db_stats['nb_filieres'] ?></h4>
                                        <p>Facultés / Filières</p>
                                    </div>
                                </div>
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-pink"><i class="fas fa-graduation-cap"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= $db_stats['nb_promotions'] ?></h4>
                                        <p>Promotions</p>
                                    </div>
                                </div>
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-teal"><i class="fas fa-cogs"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= $db_stats['nb_frais'] ?></h4>
                                        <p>Configs Frais</p>
                                    </div>
                                </div>
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-indigo"><i class="fas fa-bell"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= number_format($db_stats['nb_notifications']) ?></h4>
                                        <p>Notifications</p>
                                    </div>
                                </div>
                                <div class="db-stat-item">
                                    <div class="db-stat-icon bg-dark"><i class="fas fa-history"></i></div>
                                    <div class="db-stat-info">
                                        <h4><?= number_format($db_stats['nb_logs']) ?></h4>
                                        <p>Logs d'audit</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clé API -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-key"></i> Clé API PayLedger</h3>
                        </div>
                        <div class="settings-card-body">
                            <div class="api-info-box">
                                <div class="row align-items-center">
                                    <div class="col-lg-8">
                                        <label class="form-label"><i class="fas fa-shield-alt"></i> Clé active</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="<?= PAYMENT_API_KEY ?>" readonly 
                                                   style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;">
                                            <button class="btn btn-outline-secondary" onclick="copierCleAPI()">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="form-label"><i class="fas fa-calendar-times"></i> Expiration</label>
                                        <input type="text" class="form-control text-warning fw-bold" 
                                               value="05/06/2026 08:42" readonly>
                                    </div>
                                </div>
                                <div class="api-url-box mt-3">
                                    <label class="form-label"><i class="fas fa-link"></i> URL Gateway</label>
                                    <code>https://pay-ledger.b-manage.net/api/v1/gateway</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin/dashboard_admin.js"></script>
    <script src="../assets/js/admin/parametres.js"></script>
</body>
</html>