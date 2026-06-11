/**
 * ISTAM Paiement - Gestion des Facultés JS
 * Fonctionnalités : CRUD Ajax, Recherche, Modals
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSearchTable();
        initAutoFocus();
        console.log('🏛️ Gestion Facultés ISTAM - Prêt');
    });

    // ========== MODAL AJOUTER ==========
    window.ouvrirModalAjouter = function() {
        const modal = new bootstrap.Modal(document.getElementById('modalAjouter'));
        modal.show();
        // Focus sur l'input après ouverture
        setTimeout(() => {
            document.getElementById('nomFaculteAjout')?.focus();
        }, 500);
    };

    // ========== MODAL MODIFIER ==========
    window.ouvrirModalModifier = function(id, nom) {
        document.getElementById('modifierIdFaculte').value = id;
        document.getElementById('nomFaculteModif').value = nom;
        const modal = new bootstrap.Modal(document.getElementById('modalModifier'));
        modal.show();
        setTimeout(() => {
            document.getElementById('nomFaculteModif')?.focus();
            document.getElementById('nomFaculteModif')?.select();
        }, 500);
    };

    // ========== MODAL SUPPRESSION ==========
    window.confirmerSuppression = function(id, nom, nbFilieres) {
        document.getElementById('supprimerNomFaculte').textContent = 
            'Faculté : « ' + nom + ' » (ID: #' + id + ')';
        
        const alertDiv = document.getElementById('alertFilieres');
        const messageDiv = document.getElementById('messageFilieres');
        const btnSupprimer = document.getElementById('btnConfirmerSuppression');
        
        if (nbFilieres > 0) {
            alertDiv.style.display = 'block';
            messageDiv.textContent = 
                '⚠️ Cette faculté contient ' + nbFilieres + ' filière(s). ' +
                'Vous devez d\'abord supprimer ou déplacer ces filières avant de pouvoir supprimer la faculté.';
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
            const rows = document.querySelectorAll('#tableFacultes tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            // Afficher un message si aucun résultat
            const visibleRows = document.querySelectorAll('#tableFacultes tbody tr[style=""]').length;
            // Gérer l'affichage du "no results" si nécessaire
        });
    }

    // ========== AUTO FOCUS SUR LES MODALS ==========
    function initAutoFocus() {
        // Si la page a été rechargée après une action, faire défiler vers le message
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // ========== CONFIRMATION AVANT SUPPRESSION (DOUBLE SÉCURITÉ) ==========
    window.addEventListener('beforeunload', function(e) {
        // Si un formulaire est en cours d'édition
        const formModifier = document.getElementById('formModifier');
        if (formModifier && document.getElementById('nomFaculteModif')?.value !== '') {
            // Ne pas bloquer, juste un avertissement
        }
    });

})();