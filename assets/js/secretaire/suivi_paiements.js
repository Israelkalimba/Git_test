/**
 * ISTAM Paiement - Suivi Paiements Secrétaire JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initCopyRefs();
        initSearchFilter();
        console.log('🔍 Suivi Paiements Secrétaire - Prêt');
    });

    // ========== COPIER RÉFÉRENCE AU CLIC ==========
    function initCopyRefs() {
        document.querySelectorAll('.ref-code').forEach(el => {
            el.addEventListener('click', function() {
                const fullRef = this.getAttribute('title') || this.textContent.replace('...', '');
                navigator.clipboard?.writeText(fullRef).then(() => {
                    const original = this.textContent;
                    this.textContent = '✓ Copié !';
                    this.style.color = '#10b981';
                    setTimeout(() => {
                        this.textContent = original;
                        this.style.color = '';
                    }, 1500);
                });
            });
            el.title = 'Cliquez pour copier la référence complète';
            el.style.cursor = 'pointer';
        });
    }

    // ========== RECHERCHE RAPIDE ==========
    function initSearchFilter() {
        const searchInput = document.querySelector('input[name="recherche"]');
        if (!searchInput) return;

        // Permettre la soumission avec Entrée
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }

    // ========== EXPORT CSV ==========
    window.exportToCSV = function() {
        const table = document.getElementById('tablePaiements');
        if (!table) return;
        
        let csv = '\uFEFF'; // BOM pour Excel
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('th, td');
            const rowData = Array.from(cols).map(col => {
                let text = col.textContent.trim().replace(/"/g, '""');
                return '"' + text + '"';
            });
            csv += rowData.join(',') + '\n';
        });
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'paiements_istam_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    };

})();