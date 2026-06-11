/**
 * ISTAM Paiement - Dashboard Admin JS
 * CORRIGÉ - Sidebar fonctionnel dans les deux sens
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initMobileSidebar();
        initCharts();
        initSearch();
        initNotifications();
        initUserMenu();
        initTheme();
        initRefresh();
        initNotifPolling();
        console.log('🛡️ Dashboard Admin ISTAM - Prêt');
    });

    // ========== SIDEBAR ==========
    function initSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        const toggle = document.getElementById('btnSidebarToggle');
        if (!sidebar || !toggle) return;

        // Restaurer l'état sauvegardé
        const saved = localStorage.getItem('istam_sidebar');
        if (saved === 'collapsed') {
            sidebar.classList.add('collapsed');
            updateToggleIcon(toggle, true);
        }

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            
            // Sauvegarder
            localStorage.setItem('istam_sidebar', isCollapsed ? 'collapsed' : 'expanded');
            
            // Mettre à jour l'icône
            updateToggleIcon(toggle, isCollapsed);
            
            // Redimensionner les graphiques
            setTimeout(resizeCharts, 400);
            
            console.log('Sidebar ' + (isCollapsed ? 'réduit' : 'étendu'));
        });
    }

    function updateToggleIcon(btn, isCollapsed) {
        const icon = btn.querySelector('i');
        if (isCollapsed) {
            icon.className = 'fas fa-indent';
        } else {
            icon.className = 'fas fa-outdent';
        }
    }

    function initMobileSidebar() {
        const btn = document.getElementById('sidebarMobileToggle');
        const sidebar = document.getElementById('adminSidebar');
        if (!btn || !sidebar) return;

        btn.addEventListener('click', function() {
            sidebar.classList.add('mobile-show');
            
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1049;cursor:pointer;';
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('mobile-show');
                overlay.remove();
            });
            
            document.body.appendChild(overlay);
        });
    }

    // ========== CHART.JS ==========
    function initCharts() {
        if (typeof Chart === 'undefined' || typeof chartData === 'undefined') return;

        const ctx1 = document.getElementById('chartMensuel');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: chartData.moisLabels || [],
                    datasets: [{
                        label: 'Paiements',
                        data: chartData.moisValeurs || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.06)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f59e0b',
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { precision: 0 }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('chartFacultes');
        if (ctx2) {
            const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'];
            const labels = chartData.facultesLabels || [];
            const values = chartData.facultesValeurs || [];
            
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values.length > 0 ? values : [1],
                        backgroundColor: values.length > 0 ? colors.slice(0, labels.length) : ['#e2e8f0'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8, font: { size: 11 } }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    }

    function resizeCharts() {
        if (typeof Chart !== 'undefined') {
            Chart.instances.forEach(c => {
                if (c && c.resize) c.resize();
            });
        }
    }

    // ========== RECHERCHE ==========
    function initSearch() {
        const input = document.getElementById('globalSearch');
        const resultsDiv = document.getElementById('searchResults');
        if (!input || !resultsDiv) return;

        let debounce;

        input.addEventListener('input', function() {
            clearTimeout(debounce);
            const q = this.value.trim();
            if (q.length < 2) {
                resultsDiv.classList.remove('show');
                return;
            }
            debounce = setTimeout(() => searchAPI(q, resultsDiv), 350);
        });

        input.addEventListener('focus', function() {
            if (input.value.trim().length >= 2) resultsDiv.classList.add('show');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box')) resultsDiv.classList.remove('show');
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                input.focus();
                input.select();
            }
        });
    }

    function searchAPI(query, container) {
        fetch(`../api/admin/search.php?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="search-empty">Aucun résultat</div>';
                } else {
                    container.innerHTML = data.map(item => `
                        <a href="${item.url}" class="search-result-item">
                            <i class="fas fa-${item.type === 'etudiant' ? 'user-graduate' : 'credit-card'}"></i>
                            <div class="result-info">
                                <span class="result-title">${item.title}</span>
                                <span class="result-sub">${item.subtitle}</span>
                            </div>
                            <span class="result-badge">${item.type}</span>
                        </a>
                    `).join('');
                }
                container.classList.add('show');
            })
            .catch(() => {
                container.innerHTML = '<div class="search-empty">Erreur</div>';
                container.classList.add('show');
            });
    }

    // ========== NOTIFICATIONS ==========
    function initNotifications() {
        const btn = document.getElementById('btnNotif');
        const menu = document.getElementById('menuNotif');
        if (!btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
            document.getElementById('menuUser')?.classList.remove('show');
        });

        document.addEventListener('click', () => menu.classList.remove('show'));
        menu.addEventListener('click', (e) => e.stopPropagation());

        const markAll = document.getElementById('btnMarkAllRead');
        if (markAll) {
            markAll.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('../api/admin/mark_notifications_read.php', { method: 'POST' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelectorAll('.notif-item.notif-unread').forEach(el => el.classList.remove('notif-unread'));
                            document.querySelectorAll('.notif-dot').forEach(el => el.remove());
                            const badge = document.getElementById('badgeNotif');
                            if (badge) badge.remove();
                            markAll.remove();
                        }
                    });
            });
        }

        menu.addEventListener('click', (e) => {
            const item = e.target.closest('.notif-item');
            if (item && item.dataset.notifId && item.classList.contains('notif-unread')) {
                fetch('../api/admin/mark_single_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: item.dataset.notifId })
                });
                item.classList.remove('notif-unread');
                const dot = item.querySelector('.notif-dot');
                if (dot) dot.remove();
                updateNotifBadgeCount();
            }
        });
    }

    function updateNotifBadgeCount() {
        const unread = document.querySelectorAll('.notif-item.notif-unread').length;
        const badge = document.getElementById('badgeNotif');
        if (unread === 0 && badge) {
            badge.remove();
        } else if (badge) {
            badge.textContent = unread > 99 ? '99+' : unread;
        }
    }

    // ========== MENU UTILISATEUR ==========
    function initUserMenu() {
        const btn = document.getElementById('btnUser');
        const menu = document.getElementById('menuUser');
        if (!btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
            document.getElementById('menuNotif')?.classList.remove('show');
        });

        document.addEventListener('click', () => menu.classList.remove('show'));
        menu.addEventListener('click', (e) => e.stopPropagation());
    }

    // ========== THÈME ==========
    function initTheme() {
        const btn = document.getElementById('btnTheme');
        if (!btn) return;

        const saved = localStorage.getItem('istam_theme') || 'light';
        applyTheme(saved, btn);

        btn.addEventListener('click', () => {
            const current = document.body.classList.contains('dark') ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next, btn);
            localStorage.setItem('istam_theme', next);
        });
    }

    function applyTheme(theme, btn) {
        if (theme === 'dark') {
            document.body.classList.add('dark');
            document.documentElement.style.setProperty('--bg-body', '#0f172a');
            document.documentElement.style.setProperty('--bg-card', '#1e293b');
            document.documentElement.style.setProperty('--bg-navbar', '#1e293b');
            document.documentElement.style.setProperty('--text-primary', '#f1f5f9');
            document.documentElement.style.setProperty('--text-secondary', '#94a3b8');
            document.documentElement.style.setProperty('--border-color', '#334155');
            if (btn) {
                btn.querySelector('i').className = 'fas fa-sun';
                btn.querySelector('i').style.color = '#f59e0b';
            }
        } else {
            document.body.classList.remove('dark');
            document.documentElement.style.removeProperty('--bg-body');
            document.documentElement.style.removeProperty('--bg-card');
            document.documentElement.style.removeProperty('--bg-navbar');
            document.documentElement.style.removeProperty('--text-primary');
            document.documentElement.style.removeProperty('--text-secondary');
            document.documentElement.style.removeProperty('--border-color');
            if (btn) {
                btn.querySelector('i').className = 'fas fa-moon';
                btn.querySelector('i').style.color = '';
            }
        }
    }

    // ========== ACTUALISER ==========
    function initRefresh() {
        const btn = document.getElementById('btnRefresh');
        if (!btn) return;
        btn.addEventListener('click', () => {
            const icon = btn.querySelector('i');
            icon.classList.add('fa-spin');
            setTimeout(() => location.reload(), 400);
        });
    }

    // ========== POLLING NOTIFICATIONS ==========
    function initNotifPolling() {
        setInterval(() => {
            fetch('../api/admin/check_notifications.php')
                .then(r => r.json())
                .then(data => {
                    const badge = document.getElementById('badgeNotif');
                    if (data.count > 0) {
                        if (!badge) {
                            const btn = document.getElementById('btnNotif');
                            if (btn) {
                                const span = document.createElement('span');
                                span.className = 'badge-notif';
                                span.id = 'badgeNotif';
                                span.textContent = data.count > 99 ? '99+' : data.count;
                                btn.appendChild(span);
                            }
                        } else {
                            badge.textContent = data.count > 99 ? '99+' : data.count;
                        }
                    }
                })
                .catch(() => {});
        }, 30000);
    }

})();