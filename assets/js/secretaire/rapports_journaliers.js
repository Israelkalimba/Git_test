/**
 * ISTAM Paiement - Rapports Journaliers JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        console.log('📊 Rapport Journalier - Prêt pour le ' + 
            document.querySelector('input[name="date"]')?.value || 'aujourd\'hui');
    });

})();