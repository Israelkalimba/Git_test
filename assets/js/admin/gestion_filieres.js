/**
 * ISTAM Paiement - Gestion des Filières JS
 * Fonctionnalités : CRUD, Recherche, Modals, Filtres
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSearchTable();
        initAutoFocus();
        console.log('📚 Gestion Filières ISTAM - Prêt');
    });

    // ========== MODAL AJOUTER ==========
    window.ouvrirModalAjouter = function() {
        const modal = new bootstrap.Modal(document.getElementById('modalAjouter'));
        modal.show();
        setTimeout(() => {
            document.getElementById('nomFiliereAjout')?.focus();
        }, 500);
    };

    // ========== MODAL MODIFIER ==========
    window.ouvrirModalModifier = function(id, nom, idFaculte) {
        document.getElementById('modifierIdFiliere').value = id;
        document.getElementById('nomFiliereModif').value = nom;
        document.getElementById('faculteModif').value = idFaculte;
        
        const modal = new bootstrap.Modal(document.getElementById('modalModifier'));
        modal.show();
        setTimeout(() => {
            document.getElementById('nomFiliereModif')?.focus();
            document.getElementById('nomFiliereModif')?.select();
        }, 500);
    };

    // ========== CONFIRMATION SUPPRESSION ==========
    window.confirmerSuppression = function(id, nom, nbEtudiants, nbFrais) {
        document.getElementById('supprimerNomFiliere').textContent = 
            'Filière : « ' + nom + ' » (ID: #' + id + ')';
        
        const alertDiv = document.getElementById('alertElementsLies');
        const messageDiv = document.getElementById('messageElementsLies');
        const btnSupprimer = document.getElementById('btnConfirmerSuppression');
        
        const totalLies = nbEtudiants + nbFrais;
        
        if (totalLies > 0) {
            alertDiv.style.display = 'block';
            let msg = '⚠️ Cette filière est liée à :';
            if (nbEtudiants > 0) msg += '<br>• ' + nbEtudiants + ' étudiant(s)';
            if (nbFrais > 0) msg += '<br>• ' + nbFrais + ' type(s) de frais';
            msg += '<br>Vous devez d\'abord supprimer ces éléments avant de pouvoir supprimer la filière.';
            messageDiv.innerHTML = msg;
            
            btnSupprimer.href = '#';
            btnSupprimer.classList.add('disabled');
            btnSupprimer.style.pointerEvents = 'none';
            btnSupprimer.style.opacity = '0.5';
        } else {
            alertDiv.style.display = 'none';
            btnSupprimer.href = '?action=supprimer&id=' + id;
            btnSupprimer.classList.remove('disabled');
            btnSupprimer.style.pointerEvents = 'auto';
            btnSupprimer.style.opacity = '1';
        }
        
        const modal = new bootstrap.Modal(document.getElementById('modalSupprimer'));
        modal.show();
    };

    // ========== RECHERCHE DANS LE TABLEAU ==========
    function initSearchTable() {
        const searchInput = document.getElementById('searchTable');
        if (!searchInput) return;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#tableFilieres tbody tr');
            
            let foundAny = false;
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) foundAny = true;
            });
        });
    }

    // ========== AUTO SCROLL VERS MESSAGE ==========
    function initAutoFocus() {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

})();