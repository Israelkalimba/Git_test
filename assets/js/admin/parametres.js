/**
 * ISTAM Paiement - Paramètres JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        console.log('⚙️ Paramètres ISTAM - Prêt');
    });

    // ========== COPIER CLÉ API ==========
    window.copierCleAPI = function() {
        const cle = '<?= PAYMENT_API_KEY ?>';
        navigator.clipboard?.writeText(cle).then(() => {
            showToast('✅ Clé API copiée !', 'success');
        }).catch(() => {
            prompt('Copiez manuellement :', cle);
        });
    };

    // ========== TOAST ==========
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position:fixed;bottom:30px;right:30px;
            background:${type==='success'?'#10b981':'#ef4444'};color:white;
            padding:14px 24px;border-radius:12px;font-weight:600;
            z-index:9999;box-shadow:0 10px 30px rgba(0,0,0,0.3);
            animation:toastIn 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

})();