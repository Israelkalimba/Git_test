/**
 * ISTAM Paiement - Centre de Notifications JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initTooltips();
        initAutoRefresh();
        console.log('🔔 Centre Notifications ISTAM - Prêt');
    });

    // ========== TOOLTIPS ==========
    function initTooltips() {
        if (typeof bootstrap !== 'undefined') {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => new bootstrap.Tooltip(el));
        }
    }

    // ========== ANIMATION ENTRÉE ==========
    document.querySelectorAll('.notif-item').forEach((item, index) => {
        item.style.animation = `fadeInUp 0.3s ease ${index * 0.04}s both`;
    });

    // Injecter l'animation si pas déjà présente
    if (!document.getElementById('anim-notif-style')) {
        const style = document.createElement('style');
        style.id = 'anim-notif-style';
        style.textContent = `
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
    }

    // ========== AUTO REFRESH (optionnel) ==========
    function initAutoRefresh() {
        // Rafraîchir la page toutes les 60 secondes si on est sur l'onglet
        // (décommenter pour activer)
        // setInterval(() => {
        //     if (document.visibilityState === 'visible') {
        //         location.reload();
        //     }
        // }, 60000);
    }

})();