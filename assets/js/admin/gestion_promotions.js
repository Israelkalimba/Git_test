/**
 * ISTAM Paiement - Gestion des Promotions JS
 * Fonctionnalités : CRUD, Recherche, Modals
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSearchTable();
        initAutoFocus();
        console.log('🎓 Gestion Promotions ISTAM - Prêt');
    });

    // ========== MODAL AJOUTER ==========
    window.ouvrirModalAjouter = function() {
        const modal = new bootstrap.Modal(document.getElementById('modalAjouter'));
        modal.show();
        setTimeout(() => {
            document.getElementById('nomPromotionAjout')?.focus();
        }, 500);
    };

    // ========== MODAL MODIFIER ==========
    window.ouvrirModalModifier = function(id, nom) {
        document.getElementById('modifierIdPromotion').value = id;
        document.getElementById('nomPromotionModif').value = nom;
        
        const modal = new bootstrap.Modal(document.getElementById('modalModifier'));
        modal.show();
        setTimeout(() => {
            document.getElementById('nomPromotionModif')?.focus();
            document.getElementById('nomPromotionModif')?.select();
        }, 500);
    };

    // ========== CONFIRMATION SUPPRESSION ==========
    window.confirmerSuppression = function(id, nom, nbEtudiants, nbFrais) {
        document.getElementById('supprimerNomPromotion').textContent = 
            'Promotion : « ' + nom + ' » (ID: #' + id + ')';
        
        const alertDiv = document.getElementById('alertElementsLies');
        const messageDiv = document.getElementById('messageElementsLies');
        const btnSupprimer = document.getElementById('btnConfirmerSuppression');
        
        const totalLies = nbEtudiants + nbFrais;
        
        if (totalLies > 0) {
            alertDiv.style.display = 'block';
            let msg = '⚠️ Cette promotion est liée à :';
            if (nbEtudiants > 0) msg += '<br>• ' + nbEtudiants + ' étudiant(s)';
            if (nbFrais > 0) msg += '<br>• ' + nbFrais + ' configuration(s) de frais';
            msg += '<br><br>Supprimez d\'abord ces éléments avant de supprimer la promotion.';
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
            const rows = document.querySelectorAll('#tablePromotions tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
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