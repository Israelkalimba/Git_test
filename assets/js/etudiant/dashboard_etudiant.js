/**
 * ISTAM Paiement - Dashboard Étudiant JS
 * Progression animée + Graphiques Chart.js + Thème + Sidebar
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initMobileSidebar();
        initProgressionAnimation();
        initCharts();
        initStatAnimation();
        console.log('🎓 Dashboard Étudiant ISTAM - Prêt');
    });

    // ========== SIDEBAR ==========
    function initSidebar() {
        const sidebar = document.getElementById('etudiantSidebar');
        const toggle = document.getElementById('btnSidebarToggle');
        if (!sidebar || !toggle) return;

        const saved = localStorage.getItem('istam_etu_sidebar');
        if (saved === 'collapsed') sidebar.classList.add('collapsed');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('istam_etu_sidebar', 
                sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
            setTimeout(() => { if (typeof Chart !== 'undefined') Chart.instances.forEach(c => c.resize()); }, 400);
        });
    }

    function initMobileSidebar() {
        const btn = document.getElementById('sidebarMobileToggle');
        const sidebar = document.getElementById('etudiantSidebar');
        if (!btn || !sidebar) return;

        btn.addEventListener('click', () => {
            sidebar.classList.add('mobile-show');
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1049;cursor:pointer;';
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('mobile-show');
                overlay.remove();
            });
            document.body.appendChild(overlay);
        });
    }

    // ========== PROGRESSION ANIMÉE ==========
    function initProgressionAnimation() {
        if (typeof progressionPourcentage === 'undefined') return;
        
        const bar = document.getElementById('progressionBar');
        const label = document.getElementById('progressionLabel');
        const pourcentageEl = document.getElementById('progressionPourcentage');
        
        if (!bar || !label) return;
        
        let current = 0;
        const target = progressionPourcentage;
        const duration = 1500;
        const steps = 60;
        const increment = target / steps;
        const interval = duration / steps;
        
        const anim = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(anim);
            }
            
            bar.style.width = current + '%';
            label.textContent = Math.round(current) + '%';
            if (pourcentageEl) pourcentageEl.textContent = Math.round(current) + '%';
            
            // Couleur selon progression
            if (current < 30) {
                bar.style.background = 'linear-gradient(90deg, #ef4444, #f97316)';
            } else if (current < 60) {
                bar.style.background = 'linear-gradient(90deg, #f59e0b, #f97316)';
            } else if (current < 90) {
                bar.style.background = 'linear-gradient(90deg, #3b82f6, #6366f1)';
            } else {
                bar.style.background = 'linear-gradient(90deg, #10b981, #14b8a6)';
            }
        }, interval);
    }

    // ========== ANIMATION STATS ==========
    function initStatAnimation() {
        const statEl = document.getElementById('statPourcentage');
        if (!statEl || typeof progressionPourcentage === 'undefined') return;
        
        let current = 0;
        const target = progressionPourcentage;
        const duration = 1500;
        const steps = 60;
        const increment = target / steps;
        const interval = duration / steps;
        
        const anim = setInterval(() => {
            current += increment;
            if (current >= target) { current = target; clearInterval(anim); }
            statEl.textContent = Math.round(current) + '%';
        }, interval);
    }

    // ========== GRAPHIQUES CHART.JS ==========
    function initCharts() {
        if (typeof Chart === 'undefined') return;
        if (typeof fraisLabels === 'undefined') return;

        // Graphique 1 : Répartition (Doughnut)
        const ctx1 = document.getElementById('chartRepartition');
        if (ctx1) {
            const reste = Math.max(0, totalAPayer - totalPaye);
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: ['Payé', 'Restant'],
                    datasets: [{
                        data: [totalPaye, reste],
                        backgroundColor: totalPaye > 0 ? ['#10b981', '#e2e8f0'] : ['#e2e8f0', '#e2e8f0'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        // Graphique 2 : Barres par type de frais
        const ctx2 = document.getElementById('chartFrais');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: fraisLabels,
                    datasets: [
                        {
                            label: 'Montant à payer',
                            data: fraisMontants,
                            backgroundColor: 'rgba(59,130,246,0.15)',
                            borderColor: '#3b82f6',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false
                        },
                        {
                            label: 'Déjà payé',
                            data: fraisPayesData,
                            backgroundColor: 'rgba(16,185,129,0.6)',
                            borderColor: '#10b981',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 14, usePointStyle: true } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { callback: (value) => '$' + value.toLocaleString() }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } }
                        }
                    }
                }
            });
        }

        // Graphique 3 : Évolution mensuelle
        const ctx3 = document.getElementById('chartEvolution');
        if (ctx3 && typeof evolutionLabels !== 'undefined') {
            new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: evolutionLabels,
                    datasets: [{
                        label: 'Montant payé ($)',
                        data: evolutionValues,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.06)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#3b82f6',
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
    }

})();