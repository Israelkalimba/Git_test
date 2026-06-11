/**
 * ISTAM Paiement - Consultation Frais JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        console.log('👁️ Consultation Frais - Prêt');
    });

    // ========== EXPORT CSV ==========
    window.exportToCSV = function() {
        const table = document.getElementById('tableConfig');
        if (!table) return;
        
        let csv = '\uFEFF';
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
        a.download = 'configuration_frais_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    };

})();