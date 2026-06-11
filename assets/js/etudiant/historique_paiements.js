/**
 * ISTAM Paiement - Historique Paiements JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initChart();
        initCopyRefs();
        console.log('📊 Historique Paiements - Prêt');
    });

    // ========== GRAPHIQUE ÉVOLUTION ==========
    function initChart() {
        if (typeof Chart === 'undefined') return;
        if (typeof evolutionLabelsHisto === 'undefined') return;

        const ctx = document.getElementById('chartEvolutionHisto');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: evolutionLabelsHisto,
                datasets: [{
                    label: 'Montant payé ($)',
                    data: evolutionValuesHisto,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.06)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { callback: (value) => '$' + value.toLocaleString() }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ========== COPIER RÉFÉRENCE ==========
    function initCopyRefs() {
        document.querySelectorAll('.ref-code').forEach(el => {
            el.addEventListener('click', function() {
                const ref = this.getAttribute('title') || this.textContent.replace('...', '');
                navigator.clipboard?.writeText(ref).then(() => {
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

})();