/**
 * ISTAM Paiement - Rapports & Statistiques JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initCharts();
        console.log('📊 Rapports ISTAM - Prêt');
    });

    function initCharts() {
        if (typeof Chart === 'undefined') return;

        // Graphique d'évolution
        const ctx1 = document.getElementById('chartEvolution');
        if (ctx1 && typeof chartEvolutionLabels !== 'undefined') {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: chartEvolutionLabels,
                    datasets: [{
                        label: 'Montant collecté ($)',
                        data: chartEvolutionValues,
                        backgroundColor: 'rgba(59,130,246,0.7)',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => '$' + ctx.raw.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Camembert des statuts
        const ctx2 = document.getElementById('chartStatuts');
        if (ctx2 && typeof chartStatutsLabels !== 'undefined') {
            const colors = {
                'succes': '#10b981',
                'echec': '#ef4444',
                'en_attente': '#f59e0b'
            };
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: chartStatutsLabels.map(s => s === 'succes' ? 'Réussi' : s === 'echec' ? 'Échec' : 'En attente'),
                    datasets: [{
                        data: chartStatutsValues,
                        backgroundColor: chartStatutsLabels.map(s => colors[s] || '#94a3b8'),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } }
                    },
                    cutout: '60%'
                }
            });
        }
    }

    // ========== EXPORT CSV ==========
    window.exportToCSV = function() {
        const table = document.getElementById('tableTransactions');
        if (!table) return;
        
        let csv = '';
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
        a.download = 'rapport_istam_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    };

})();