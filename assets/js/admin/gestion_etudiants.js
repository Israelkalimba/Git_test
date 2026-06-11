/**
 * ISTAM Paiement - Gestion des Étudiants JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initAutoFocus();
        console.log('👨‍🎓 Gestion Étudiants ISTAM - Prêt');
    });

    // ========== MODAL AJOUTER ==========
    window.ouvrirModalAjouter = function() {
        const modal = new bootstrap.Modal(document.getElementById('modalAjouter'));
        modal.show();
    };

    // Charger les filières dans le modal d'ajout selon la faculté
    window.chargerFilieresAjout = function() {
        const idFaculte = document.getElementById('selectFaculteAjout').value;
        const selectFiliere = document.getElementById('selectFiliereAjout');
        
        if (!idFaculte) {
            selectFiliere.innerHTML = '<option value="">-- Choisir d\'abord une faculté --</option>';
            return;
        }
        
        selectFiliere.innerHTML = '<option value="">Chargement...</option>';
        
        fetch(`../api/admin/get_filieres.php?faculte=${idFaculte}`)
            .then(r => r.json())
            .then(data => {
                selectFiliere.innerHTML = '<option value="">-- Choisir une filière --</option>';
                data.forEach(f => {
                    selectFiliere.innerHTML += `<option value="${f.id_filiere}">${f.nom_filiere}</option>`;
                });
            });
    };

    // ========== MODAL MODIFIER ==========
    window.ouvrirModalModifier = function(etudiant) {
        document.getElementById('modIdEtudiant').value = etudiant.id_etudiant;
        document.getElementById('modIdUtilisateur').value = etudiant.id_utilisateur;
        document.getElementById('modNom').value = etudiant.nom;
        document.getElementById('modEmail').value = etudiant.email;
        document.getElementById('modMatricule').value = etudiant.matricule;
        document.getElementById('modTelephone').value = etudiant.telephone || '';
        document.getElementById('modFiliere').value = etudiant.id_filiere;
        document.getElementById('modPromotion').value = etudiant.id_promotion;
        
        const modal = new bootstrap.Modal(document.getElementById('modalModifier'));
        modal.show();
    };

    // ========== CONFIRMATION SUPPRESSION ==========
    window.confirmerSuppression = function(idEtudiant, idUser, nom, matricule) {
        document.getElementById('supprimerInfo').textContent = 
            `Étudiant : ${nom} (Matricule: ${matricule})`;
        document.getElementById('btnConfirmerSuppression').href = 
            `?action=supprimer&id=${idEtudiant}&id_user=${idUser}`;
        
        const modal = new bootstrap.Modal(document.getElementById('modalSupprimer'));
        modal.show();
    };

    // ========== AUTO SCROLL ==========
    function initAutoFocus() {
        const alert = document.querySelector('.alert');
        if (alert) alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

})();