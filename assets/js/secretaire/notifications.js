/**
 * ISTAM Paiement - Notifications Secrétaire JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        animateItems();
        console.log('🔔 Notifications Secrétaire - Prêt');
    });

    // ========== ANIMATION ENTRÉE ==========
    function animateItems() {
        document.querySelectorAll('.notif-item').forEach((item, index) => {
            item.style.animation = `fadeInUp 0.3s ease ${index * 0.04}s both`;
        });
    }

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

})();