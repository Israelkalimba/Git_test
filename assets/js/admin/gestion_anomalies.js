/**
 * ISTAM Paiement - Gestion des Anomalies JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSearchTable();
        initConfirmationDouble();
        console.log('🔧 Gestion Anomalies ISTAM - Prêt');
    });

    // ========== RECHERCHE DANS LE TABLEAU ==========
    function initSearchTable() {
        const searchInput = document.querySelector('input[name="recherche"]');
        if (!searchInput) return;
        
        // La recherche est gérée côté serveur via le formulaire
        // Ceci est pour une recherche instantanée côté client en complément
    }

    // ========== DOUBLE CONFIRMATION ACTIONS CRITIQUES ==========
    function initConfirmationDouble() {
        // Les confirmations sont gérées via onclick dans les liens
        // Ajout d'une sécurité supplémentaire
        document.querySelectorAll('a[onclick*="confirm"]').forEach(link => {
            link.addEventListener('click', function(e) {
                // La confirmation native est déjà gérée par le onclick
                // On ajoute juste un log
                const action = this.getAttribute('title') || 'Action';
                console.log('🔧 Action anomalies : ' + action);
            });
        });
    }

    // ========== ANIMATION DES LIGNES ==========
    document.querySelectorAll('.anomalie-row').forEach((row, index) => {
        row.style.animation = `fadeInLeft 0.3s ease ${index * 0.03}s both`;
    });

    if (!document.getElementById('anim-anomalie')) {
        const style = document.createElement('style');
        style.id = 'anim-anomalie';
        style.textContent = `
            @keyframes fadeInLeft {
                from { opacity: 0; transform: translateX(-10px); }
                to { opacity: 1; transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);
    }

})();
