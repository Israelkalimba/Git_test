<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

Auth::checkSession('etudiant');

$db = Database::getInstance();
$etudiant_nom = $_SESSION['user_nom'] ?? 'Étudiant';
$etudiant_id_user = $_SESSION['user_id'] ?? 1;

// Récupérer les infos de l'étudiant
$stmt = $db->prepare("
    SELECT e.*, u.nom, u.email, fi.nom_filiere, fa.nom_faculte, pr.nom_promotion
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
$email_etudiant = $etudiant['email'];
$id_filiere = $etudiant['id_filiere'];
$id_promotion = $etudiant['id_promotion'];

// ========== FRAIS À PAYER ==========
$stmt = $db->prepare("
    SELECT fr.*, 
           COALESCE(pa.montant_paye, 0) as deja_paye,
           pa.statut as statut_paiement, pa.date_paiement, pa.id_paiement
    FROM frais fr 
    LEFT JOIN paiements pa ON fr.id_frais = pa.id_frais AND pa.id_etudiant = :id_etudiant AND pa.statut = 'succes'
    WHERE fr.id_filiere = :id_filiere AND fr.id_promotion = :id_promotion
    ORDER BY fr.type_frais
");
$stmt->execute(['id_etudiant' => $id_etudiant, 'id_filiere' => $id_filiere, 'id_promotion' => $id_promotion]);
$frais_a_payer = $stmt->fetchAll();

$frais_payes = array_filter($frais_a_payer, fn($f) => ($f['statut_paiement'] ?? '') === 'succes');
$frais_restants = array_filter($frais_a_payer, fn($f) => ($f['statut_paiement'] ?? '') !== 'succes');
$frais_selected = isset($_GET['frais']) ? (int)$_GET['frais'] : 0;
$taux_defaut = 2300;

// Notifications
$stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE id_utilisateur = :id AND statut = 'non_lu'");
$stmt->execute(['id' => $etudiant_id_user]);
$notifications_non_lues = $stmt->fetch()['total'] ?? 0;
$stmt_nav = $db->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY date_envoi DESC LIMIT 5");
$stmt_nav->execute(['id' => $etudiant_id_user]);
$navbar_notifications = $stmt_nav->fetchAll();

$api_key = PAYMENT_API_KEY;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payer mes frais - Étudiant ISTAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/etudiant/dashboard_etudiant.css">
    <link rel="stylesheet" href="../assets/css/etudiant/payer_frais.css">
</head>
<body>
<div class="etudiant-layout">
    <?php include 'includes/sidebar_etudiant.php'; ?>
    <div class="main-content">
        <?php $navbar_notif_non_lues = $notifications_non_lues; include 'includes/navbar_etudiant.php'; ?>
        <main class="dashboard-content">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-lg-7"><h1 class="page-title"><i class="fas fa-credit-card"></i> Payer mes Frais Académiques</h1><p class="page-subtitle">Sélectionnez un frais à payer.</p></div>
                    <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                        <a href="historique_paiements.php" class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-history"></i> Historique</a>
                        <a href="mes_recus.php" class="btn btn-outline-success btn-sm"><i class="fas fa-file-pdf"></i> Mes Reçus</a>
                    </div>
                </div>
            </div>

            <div class="resume-paiement">
                <div class="resume-item-paiement"><i class="fas fa-user-graduate"></i><div><span class="resume-label">Étudiant</span><strong><?= htmlspecialchars($etudiant_nom) ?></strong></div></div>
                <div class="resume-item-paiement"><i class="fas fa-id-card"></i><div><span class="resume-label">Matricule</span><strong><?= htmlspecialchars($matricule) ?></strong></div></div>
                <div class="resume-item-paiement"><i class="fas fa-layer-group"></i><div><span class="resume-label">Filière</span><strong><?= htmlspecialchars($etudiant['nom_filiere']) ?></strong></div></div>
                <div class="resume-item-paiement"><i class="fas fa-graduation-cap"></i><div><span class="resume-label">Promotion</span><strong><?= htmlspecialchars($etudiant['nom_promotion']) ?></strong></div></div>
            </div>

            <div class="paiement-grid">
                <!-- Frais à payer -->
                <div class="frais-column">
                    <h3 class="section-title-paiement"><i class="fas fa-list-alt"></i> Frais à payer <span class="badge-count-sm"><?= count($frais_restants) ?> restant(s)</span></h3>
                    <?php if (empty($frais_restants)): ?>
                        <div class="all-paid-card"><i class="fas fa-check-circle"></i><h4>Félicitations !</h4><p>Tous vos frais ont été payés.</p></div>
                    <?php else: ?>
                        <div class="frais-list">
                            <?php foreach ($frais_restants as $frais): 
                                $montant_fc = $frais['montant'] * ($frais['taux_change'] ?? $taux_defaut);
                                $isSelected = ($frais_selected === (int)$frais['id_frais']);
                            ?>
                                <div class="frais-item-paiement <?= $isSelected ? 'selected' : '' ?>" 
                                     onclick="selectionnerFrais(<?= $frais['id_frais'] ?>, '<?= htmlspecialchars(addslashes($frais['type_frais'])) ?>', <?= $frais['montant'] ?>, <?= $frais['taux_change'] ?? $taux_defaut ?>)">
                                    <div class="frais-item-header"><h5><?= htmlspecialchars($frais['type_frais']) ?></h5><span class="frais-montant-item"><strong class="montant-usd-item">$<?= number_format($frais['montant'],2) ?></strong><small class="montant-fc-item"><?= number_format($montant_fc,0,',',' ') ?> FC</small></span></div>
                                    <small class="taux-info-item">1$ = <?= number_format($frais['taux_change'] ?? $taux_defaut,0) ?> FC</small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($frais_payes)): ?>
                        <h4 class="mt-4 mb-3 text-success"><i class="fas fa-check-circle"></i> Déjà payés (<?= count($frais_payes) ?>)</h4>
                        <div class="frais-list payes">
                            <?php foreach ($frais_payes as $frais): ?>
                                <div class="frais-item-paiement frais-deja-paye"><div class="frais-item-header"><h5><?= htmlspecialchars($frais['type_frais']) ?></h5><span class="badge-paye-sm">Payé le <?= date('d/m/Y', strtotime($frais['date_paiement'])) ?></span></div></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Formulaire + SPA -->
                <div class="paiement-column">
                    <!-- ÉTAPE 1 : FORMULAIRE -->
                    <div id="stepFormulaire">
                        <div class="paiement-card">
                            <div class="paiement-card-header"><h3><i class="fas fa-credit-card"></i> Effectuer le paiement</h3></div>
                            <div class="paiement-card-body">
                                <div class="no-frais-selected" id="noFraisSelected"><i class="fas fa-hand-pointer"></i><h4>Sélectionnez un frais</h4><p>Cliquez sur un frais à gauche.</p></div>
                                <form id="formPaiement" style="display:none;" onsubmit="return initierPaiement(event)">
                                    <input type="hidden" name="id_frais" id="inputIdFrais">
                                    <input type="hidden" id="inputMontantUSD" value="0">
                                    <input type="hidden" id="inputTauxChange" value="<?= $taux_defaut ?>">

                                    <div class="frais-details-paiement"><h5 id="detailTypeFrais">—</h5><div class="detail-montants"><div class="detail-montant-usd"><span class="detail-label">USD</span><strong id="detailMontantUSD">$0.00</strong></div><div class="detail-montant-fc"><span class="detail-label">FC</span><strong id="detailMontantFC">0 FC</strong></div></div><small class="detail-taux" id="detailTaux">Taux : 1$ = <?= $taux_defaut ?> FC</small></div>

                                    <div class="devise-choix">
                                        <label class="form-label-paiement"><i class="fas fa-coins"></i> Je paie en :</label>
                                        <div class="devise-options">
                                            <label class="devise-option"><input type="radio" name="devise" value="USD" checked onchange="updateAffichage()"><span class="devise-card"><i class="fas fa-dollar-sign"></i><strong>Dollar US ($)</strong><small id="prixUSD">$0.00</small></span></label>
                                            <label class="devise-option"><input type="radio" name="devise" value="CDF" onchange="updateAffichage()"><span class="devise-card"><i class="fas fa-money-bill-wave"></i><strong>Franc Congolais (FC)</strong><small id="prixCDF">0 FC</small></span></label>
                                        </div>
                                    </div>

                                    <div class="telephone-section">
                                        <label class="form-label-paiement"><i class="fas fa-mobile-alt"></i> N° Mobile Money <span class="text-danger">*</span></label>
                                        <input type="text" id="telephone_paiement" class="form-control form-control-lg" value="<?= htmlspecialchars($telephone ?? '') ?>" placeholder="+243 8XX XXX XXX">
                                        <div class="operateur-detecte" id="operateurDetecte" style="display:none;"><i class="fas fa-signal"></i> <span id="operateurNom">—</span></div>
                                    </div>

                                    <div class="recap-paiement"><h6><i class="fas fa-receipt"></i> Récapitulatif</h6><div class="recap-ligne"><span>Frais</span><span id="recapFrais">—</span></div><div class="recap-ligne"><span>Total</span><strong id="recapMontant">—</strong></div></div>

                                    <button type="submit" class="btn-payer-submit"><i class="fas fa-lock"></i> Payer maintenant</button>
                                    <p class="paiement-securise"><i class="fas fa-shield-alt"></i> Paiement sécurisé via PayLedger</p>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ÉTAPE 2 : VÉRIFICATION SPA -->
                    <div id="stepVerification" style="display:none;">
                        <div class="verification-card">
                            <div class="verif-header">
                                <div class="verif-spinner" id="verifSpinner"><i class="fas fa-spinner fa-spin"></i></div>
                                <div class="verif-check" id="verifCheck" style="display:none;"><i class="fas fa-check-circle"></i></div>
                                <h2 id="verifTitle">Vérification du paiement...</h2>
                            </div>
                            <div class="verif-body">
                                <div class="verif-info"><p><strong>Référence :</strong> <span id="verifRef">—</span></p><p><strong>Montant :</strong> <span id="verifMontant">—</span></p><p><strong>Statut :</strong> <span id="verifStatut" class="badge bg-warning">En attente</span></p></div>
                                <div class="verif-progress mt-3"><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" id="verifProgress" style="width:0%"></div></div><small class="text-muted mt-1">Temps restant : <span id="verifTimer">60s</span></small></div>
                                <div class="verif-actions mt-4" id="verifActions" style="display:none;"><button class="btn btn-primary" onclick="verifierManuellement()"><i class="fas fa-sync-alt"></i> Vérifier maintenant</button></div>
                                <div id="verifMessage" class="mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ÉTAPE 3 : SUCCÈS -->
                    <div id="stepSucces" style="display:none;">
                        <div class="succes-card text-center">
                            <div class="succes-icon"><i class="fas fa-check-circle"></i></div>
                            <h2>Paiement Réussi ! 🎉</h2>
                            <p>Votre paiement a été confirmé avec succès.</p>
                            <div class="succes-details"><div><span>Référence</span><strong id="succesRef">—</strong></div><div><span>Montant</span><strong id="succesMontant">—</strong></div></div>
                            <div class="succes-actions mt-3"><a href="mes_recus.php" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Voir mes reçus</a><a href="historique_paiements.php" class="btn btn-outline-primary"><i class="fas fa-history"></i> Historique</a></div>
                        </div>
                    </div>

                    <!-- ÉTAPE 4 : ÉCHEC -->
                    <div id="stepEchec" style="display:none;">
                        <div class="echec-card text-center"><div class="echec-icon"><i class="fas fa-times-circle"></i></div><h2>Paiement échoué</h2><p id="echecMessage">—</p><button class="btn btn-primary mt-3" onclick="reessayer()"><i class="fas fa-redo"></i> Réessayer</button></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let fraisActuel = { id: 0, nom: '', montant: 0, taux: <?= $taux_defaut ?> };
let pollingInterval = null, countdownInterval = null, tempsRestant = 60;

function selectionnerFrais(id, nom, montant, taux) {
    fraisActuel = { id, nom, montant, taux };
    document.getElementById('inputIdFrais').value = id;
    document.getElementById('inputMontantUSD').value = montant;
    document.getElementById('inputTauxChange').value = taux;
    document.getElementById('detailTypeFrais').textContent = nom;
    document.getElementById('detailMontantUSD').textContent = '$' + montant.toFixed(2);
    document.getElementById('detailMontantFC').textContent = (montant * taux).toLocaleString('fr-FR') + ' FC';
    document.getElementById('detailTaux').textContent = '1$ = ' + taux + ' FC';
    document.getElementById('recapFrais').textContent = nom;
    document.getElementById('noFraisSelected').style.display = 'none';
    document.getElementById('formPaiement').style.display = 'block';
    updateAffichage();
    document.querySelectorAll('.frais-item-paiement').forEach(el => el.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
}

function updateAffichage() {
    const d = document.querySelector('input[name="devise"]:checked').value;
    const m = fraisActuel.montant, t = fraisActuel.taux;
    document.getElementById('prixUSD').textContent = '$' + m.toFixed(2);
    document.getElementById('prixCDF').textContent = (m * t).toLocaleString('fr-FR') + ' FC';
    document.getElementById('recapMontant').textContent = d === 'USD' ? '$' + m.toFixed(2) + ' USD' : (m * t).toLocaleString('fr-FR') + ' FC';
}

// Détection opérateur
document.addEventListener('DOMContentLoaded', () => {
    const tel = document.getElementById('telephone_paiement');
    if (tel) tel.addEventListener('input', function() { const t = this.value.replace(/[^0-9]/g,''); let o = '—'; if(t.length>=9){const p=t.substring(t.length-9,t.length-7); if(['81','82','83','84','85'].includes(p))o='Orange Money'; else if(['97','98','99'].includes(p))o='Airtel Money'; else if(['80','90','91'].includes(p))o='Vodacom M-Pesa';} document.getElementById('operateurNom').textContent=o; document.getElementById('operateurDetecte').style.display=t.length>0?'flex':'none'; });
    <?php if ($frais_selected > 0): ?> const el = document.querySelector(`.frais-item-paiement[onclick*="${<?= $frais_selected ?>}"]`); if(el) el.click(); <?php endif; ?>
});

// ========== INITIER PAIEMENT ==========
async function initierPaiement(event) {
    event.preventDefault();
    const tel = document.getElementById('telephone_paiement').value.trim();
    const devise = document.querySelector('input[name="devise"]:checked').value;
    if (!tel) { Swal.fire({icon:'warning',title:'Numéro requis',timer:2500,showConfirmButton:false}); return false; }
    const m = fraisActuel.montant; const t = fraisActuel.taux;
    const montant = devise === 'USD' ? '$' + m.toFixed(2) : (m * t).toLocaleString('fr-FR') + ' FC';
    const conf = await Swal.fire({title:'Confirmer le paiement ?',html:`<p>Frais : <strong>${fraisActuel.nom}</strong></p><p>Montant : <strong style="color:#7c3aed;font-size:1.2em;">${montant}</strong></p><p>Téléphone : <strong>${tel}</strong></p>`,icon:'question',showCancelButton:true,confirmButtonColor:'#10b981',confirmButtonText:'Oui, payer',cancelButtonText:'Annuler'});
    if (!conf.isConfirmed) return false;

    document.getElementById('stepFormulaire').style.display = 'none';
    document.getElementById('stepVerification').style.display = 'block';
    document.getElementById('verifRef').textContent = 'Initialisation...';
    document.getElementById('verifMontant').textContent = montant;
    document.getElementById('verifStatut').textContent = 'Envoi...';

    try {
        const fd = new FormData(); fd.append('id_frais', fraisActuel.id); fd.append('devise', devise);
        fd.append('telephone', tel); fd.append('montant_usd', m); fd.append('taux_change', t);
        const resp = await fetch('../api/istam/initier_paiement.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('verifRef').textContent = data.notre_ref;
            document.getElementById('verifStatut').textContent = 'En attente de confirmation';
            document.getElementById('verifStatut').className = 'badge bg-warning';
            demarrerPolling(data.gateway_ref, data.notre_ref, data.id_paiement);
            demarrerCountdown();
        } else {
            afficherEchec(data.message || 'Erreur initiation.');
        }
    } catch(e) { afficherEchec('Erreur de connexion.'); }
    return false;
}

// ========== POLLING AUTO (PLAN A) ==========
function demarrerPolling(gatewayRef, notreRef, idPaiement) {
    let tentatives = 0; const max = 20;
    pollingInterval = setInterval(async () => {
        tentatives++;
        document.getElementById('verifProgress').style.width = (tentatives / max * 100) + '%';
        try {
            const resp = await fetch('../api/istam/verifier_paiement.php?ref=' + encodeURIComponent(gatewayRef) + '&notre_ref=' + encodeURIComponent(notreRef) + '&id_paiement=' + idPaiement);
            const data = await resp.json();
            if (data.status === 'successful') { clearIntervals(); afficherSucces(notreRef, data.montant); }
            else if (data.status === 'failed' || data.status === 'cancelled') { clearIntervals(); afficherEchec('Paiement refusé par l\'opérateur.'); }
        } catch(e) {}
        if (tentatives >= max) { clearIntervals(); document.getElementById('verifTitle').textContent='Délai dépassé'; document.getElementById('verifStatut').textContent='En attente longue'; document.getElementById('verifActions').style.display='block'; document.getElementById('verifSpinner').style.display='none'; }
    }, 3000);
}

function demarrerCountdown() {
    tempsRestant = 60; document.getElementById('verifTimer').textContent = '60s';
    countdownInterval = setInterval(() => { tempsRestant--; document.getElementById('verifTimer').textContent = tempsRestant + 's'; if(tempsRestant<=10) document.getElementById('verifActions').style.display='block'; if(tempsRestant<=0) clearInterval(countdownInterval); }, 1000);
}

// ========== PLAN B ==========
async function verifierManuellement() {
    const gw = document.getElementById('verifRef').textContent;
    if (!gw || gw === '—') return;
    document.getElementById('verifSpinner').style.display='block'; document.getElementById('verifCheck').style.display='none';
    document.getElementById('verifTitle').textContent = 'Vérification...';
    try {
        const resp = await fetch('../api/istam/verifier_paiement.php?ref=' + encodeURIComponent(gw) + '&force=1');
        const data = await resp.json();
        if (data.status === 'successful') { clearIntervals(); afficherSucces(data.notre_ref, data.montant); }
        else if (data.status === 'failed') { afficherEchec('Paiement refusé.'); }
        else { document.getElementById('verifTitle').textContent='En attente'; document.getElementById('verifSpinner').style.display='none'; Swal.fire({icon:'info',title:'En attente',text:'Vérifiez votre téléphone.',timer:3000,showConfirmButton:false}); }
    } catch(e) { Swal.fire({icon:'error',title:'Erreur'}); }
}

function afficherSucces(ref, montant) {
    document.getElementById('stepVerification').style.display='none';
    document.getElementById('stepSucces').style.display='block';
    document.getElementById('succesRef').textContent = ref;
    document.getElementById('succesMontant').textContent = '$' + parseFloat(montant).toFixed(2);
}
function afficherEchec(msg) { document.getElementById('stepVerification').style.display='none'; document.getElementById('stepEchec').style.display='block'; document.getElementById('echecMessage').textContent = msg; }
function reessayer() { document.getElementById('stepEchec').style.display='none'; document.getElementById('stepVerification').style.display='none'; document.getElementById('stepFormulaire').style.display='block'; clearIntervals(); }
function clearIntervals() { if(pollingInterval) clearInterval(pollingInterval); if(countdownInterval) clearInterval(countdownInterval); }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/etudiant/dashboard_etudiant.js"></script>
</body>
</html>