/**
 * ISTAM Paiement - Journal d'Audit JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initCopyIP();
        console.log('📋 Journal d\'Audit ISTAM - Prêt');
    });

    // ========== COPIER L'ADRESSE IP AU CLIC ==========
    function initCopyIP() {
        document.querySelectorAll('.log-ip').forEach(el => {
            el.addEventListener('click', function() {
                const ip = this.textContent.replace(/[^0-9.]/g, '').trim();
                if (ip) {
                    navigator.clipboard?.writeText(ip).then(() => {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check text-success"></i> Copié !';
                        setTimeout(() => {
                            this.innerHTML = originalText;
                        }, 1500);
                    });
                }
            });
            el.title = 'Cliquez pour copier l\'IP';
            el.style.cursor = 'pointer';
        });
    }

    // ========== PURGER LES LOGS (CONFIRMATION) ==========
    window.viderLogs = function() {
        if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer TOUS les logs d\'audit ?\n\nCette action est IRRÉVERSIBLE !')) {
            if (confirm('🔴 DERNIÈRE CONFIRMATION : Supprimer définitivement tous les logs ?')) {
                window.location.href = '../api/admin/purge_logs.php';
            }
        }
    };

})();