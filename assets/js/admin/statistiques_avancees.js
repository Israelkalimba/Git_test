/**
 * ISTAM Paiement - Statistiques Avancées JS
 * 5 graphiques interactifs Chart.js
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initAllCharts();
        console.log('📈 Statistiques Avancées ISTAM - Prêt');
    });

    function initAllCharts() {
        if (typeof Chart === 'undefined') return;

        // 1. Évolution mensuelle (ligne double)
        const ctx1 = document.getElementById('chartEvolution');
        if (ctx1 && typeof dataEvolutionLabels !== 'undefined') {
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: dataEvolutionLabels,
                    datasets: [
                        {
                            label: 'Montant ($)',
                            data: dataEvolutionMontants,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.05)',
                            borderWidth: 3, fill: true, tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Transactions réussies',
                            data: dataEvolutionSucces,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.05)',
                            borderWidth: 2, fill: true, tension: 0.4,
                            borderDash: [5,3], yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: true, position: 'left', grid: { color: '#f1f5f9' },
                            ticks: { callback: v => '$' + v.toLocaleString() } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false },
                            ticks: { precision: 0 } }
                    }
                }
            });
        }

        // 2. Comparaison annuelle (barres)
        const ctx2 = document.getElementById('chartComparaison');
        if (ctx2 && typeof dataComparaisonLabels !== 'undefined') {
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: dataComparaisonLabels,
                    datasets: [{
                        label: 'Total collecté ($)',
                        data: dataComparaisonValues,
                        backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6'],
                        borderRadius: 8, borderWidth: 1, borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } } }
                }
            });
        }

        // 3. Répartition facultés (camembert)
        const ctx3 = document.getElementById('chartFacultes');
        if (ctx3 && typeof dataFacultesLabels !== 'undefined') {
            const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16'];
            new Chart(ctx3, {
                type: 'doughnut',
                data: {
                    labels: dataFacultesLabels,
                    datasets: [{
                        data: dataFacultesValues,
                        backgroundColor: colors.slice(0, dataFacultesLabels.length),
                        borderWidth: 2, borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { padding: 14, usePointStyle: true } } },
                    cutout: '55%'
                }
            });
        }

        // 4. Types de frais (barres horizontales)
        const ctx4 = document.getElementById('chartFrais');
        if (ctx4 && typeof dataFraisLabels !== 'undefined') {
            new Chart(ctx4, {
                type: 'bar',
                data: {
                    labels: dataFraisLabels,
                    datasets: [{
                        label: 'Total ($)',
                        data: dataFraisValues,
                        backgroundColor: 'rgba(139,92,246,0.7)',
                        borderColor: '#8b5cf6',
                        borderWidth: 1, borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, grid: { color: '#f1f5f9' } } }
                }
            });
        }

        // 5. Performance opérateurs (barres empilées)
        const ctx5 = document.getElementById('chartOperateurs');
        if (ctx5 && typeof dataOperateursLabels !== 'undefined') {
            new Chart(ctx5, {
                type: 'bar',
                data: {
                    labels: dataOperateursLabels,
                    datasets: [
                        { label: 'Succès', data: dataOperateursSucces, backgroundColor: '#10b981', borderRadius: 6 },
                        { label: 'Échecs', data: dataOperateursEchec, backgroundColor: '#ef4444', borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' } } }
                }
            });
        }
    }

})();