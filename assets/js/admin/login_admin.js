/**
 * ISTAM Paiement - Dashboard Administrateur
 * Gestion complète du tableau de bord admin
 */
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initCharts();
    initNotifications();
    initUserMenu();
    initSearchFunctionality();
    initThemeToggle();
    initRefreshButton();
    initMobileSidebar();
    initUpdateNotifications();
    console.log('🛡️ Dashboard Admin ISTAM - Initialisé');
});

// ========== SIDEBAR TOGGLE ==========
function initSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    if (!sidebar || !toggleBtn) return;
    
    const savedState = localStorage.getItem('istam_admin_sidebar');
    if (savedState === 'collapsed') {
        sidebar.classList.add('collapsed');
    }
    
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('istam_admin_sidebar', isCollapsed ? 'collapsed' : 'expanded');
        setTimeout(resizeCharts, 350);
    });
}

// ========== MOBILE SIDEBAR ==========
function initMobileSidebar() {
    const mobileToggle = document.getElementById('sidebarMobileToggle');
    const sidebar = document.getElementById('adminSidebar');
    
    if (!mobileToggle || !sidebar) return;
    
    mobileToggle.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-show');
        
        if (sidebar.classList.contains('mobile-show')) {
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0, 0, 0, 0.5); z-index: 1035; cursor: pointer;
            `;
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('mobile-show');
                overlay.remove();
            });
            document.body.appendChild(overlay);
        }
    });
}

// ========== GRAPHIQUES CHART.JS (Données réelles) ==========
function initCharts() {
    if (typeof chartData === 'undefined') return;
    
    // Graphique des paiements mensuels
    const ctx1 = document.getElementById('monthlyPaymentsChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: chartData.monthlyLabels || [],
                datasets: [{
                    label: 'Paiements',
                    data: chartData.monthlyValues || [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 8,
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
                        backgroundColor: '#1a1a2e',
                        titleColor: '#ffd700',
                        bodyColor: '#ffffff',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f3f4f6' },
                        ticks: { 
                            callback: (value) => value.toLocaleString(),
                            stepSize: 1
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    
    // Graphique par faculté
    const ctx2 = document.getElementById('facultyDistributionChart');
    if (ctx2) {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];
        
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: chartData.facultyLabels || [],
                datasets: [{
                    data: chartData.facultyValues || [],
                    backgroundColor: colors.slice(0, (chartData.facultyLabels || []).length),
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true, pointStyleWidth: 10 }
                    }
                },
                cutout: '65%'
            }
        });
    }
}

function resizeCharts() {
    if (typeof Chart !== 'undefined') {
        Chart.instances.forEach(chart => chart.resize());
    }
}

// ========== NOTIFICATIONS ==========
function initNotifications() {
    const bell = document.getElementById('notificationBell');
    const menu = document.getElementById('notificationMenu');
    
    if (!bell || !menu) return;
    
    bell.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('show');
        document.getElementById('userMenu')?.classList.remove('show');
    });
    
    document.addEventListener('click', () => {
        menu.classList.remove('show');
    });
    
    menu.addEventListener('click', (e) => e.stopPropagation());
    
    // Marquer tout comme lu
    const markAllBtn = document.getElementById('markAllRead');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('../api/admin/mark_notifications_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ admin_id: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    const badge = document.querySelector('.notification-badge');
                    if (badge) badge.remove();
                    
                    // Recharger après un court délai
                    setTimeout(() => location.reload(), 500);
                }
            })
            .catch(err => console.error('Erreur:', err));
        });
    }
    
    // Marquer une notification comme lue au clic
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            const notifId = this.dataset.id;
            if (notifId && this.classList.contains('unread')) {
                fetch('../api/admin/mark_single_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: notifId })
                });
                this.classList.remove('unread');
            }
        });
    });
}

// ========== MENU UTILISATEUR ==========
function initUserMenu() {
    const toggle = document.getElementById('userDropdownToggle');
    const menu = document.getElementById('userMenu');
    
    if (!toggle || !menu) return;
    
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('show');
        document.getElementById('notificationMenu')?.classList.remove('show');
    });
    
    document.addEventListener('click', () => {
        menu.classList.remove('show');
    });
    
    menu.addEventListener('click', (e) => e.stopPropagation());
}

// ========== RECHERCHE GLOBALE FONCTIONNELLE ==========
function initSearchFunctionality() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;
    
    let debounceTimer;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            clearSearchResults();
            return;
        }
        
        debounceTimer = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Raccourci Ctrl+K
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
        // Échap pour fermer les résultats
        if (e.key === 'Escape') {
            clearSearchResults();
            searchInput.blur();
        }
    });
    
    // Fermer les résultats en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-box') && !e.target.closest('.search-results')) {
            clearSearchResults();
        }
    });
}

function performSearch(query) {
    fetch(`../api/admin/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data, query);
        })
        .catch(err => {
            console.error('Erreur recherche:', err);
        });
}

function displaySearchResults(results, query) {
    let resultsContainer = document.querySelector('.search-results');
    
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results';
        document.querySelector('.search-box').appendChild(resultsContainer);
    }
    
    if (results.length === 0) {
        resultsContainer.innerHTML = `
            <div class="search-result-empty">
                <i class="fas fa-search"></i>
                <p>Aucun résultat pour "${query}"</p>
            </div>
        `;
    } else {
        resultsContainer.innerHTML = results.map(item => `
            <a href="${item.url}" class="search-result-item">
                <i class="fas fa-${item.type === 'etudiant' ? 'user-graduate' : (item.type === 'transaction' ? 'credit-card' : 'file')}"></i>
                <div>
                    <strong>${item.title}</strong>
                    <small>${item.subtitle}</small>
                </div>
                <span class="badge bg-light text-dark">${item.type}</span>
            </a>
        `).join('');
    }
}

function clearSearchResults() {
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) resultsContainer.remove();
}

// ========== TOGGLE THÈME (FONCTIONNEL) ==========
function initThemeToggle() {
    const toggle = document.getElementById('themeToggle');
    if (!toggle) return;
    
    // Appliquer le thème sauvegardé
    const savedTheme = localStorage.getItem('istam_admin_theme') || 'light';
    applyTheme(savedTheme, toggle);
    
    toggle.addEventListener('click', () => {
        const currentTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        applyTheme(newTheme, toggle);
        localStorage.setItem('istam_admin_theme', newTheme);
    });
}

function applyTheme(theme, toggleBtn) {
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        document.documentElement.style.setProperty('--bg-color', '#111827');
        document.documentElement.style.setProperty('--text-color', '#f9fafb');
        document.documentElement.style.setProperty('--card-bg', '#1f2937');
        document.documentElement.style.setProperty('--border-color', '#374151');
        
        // Ajouter styles dark mode
        if (!document.getElementById('dark-mode-styles')) {
            const darkStyles = document.createElement('style');
            darkStyles.id = 'dark-mode-styles';
            darkStyles.textContent = `
                body.dark-mode { background: #111827; color: #f9fafb; }
                body.dark-mode .admin-navbar { background: #1f2937; border-color: #374151; }
                body.dark-mode .stat-card,
                body.dark-mode .chart-card,
                body.dark-mode .info-card,
                body.dark-mode .table-card { background: #1f2937; border-color: #374151; }
                body.dark-mode .stat-value { color: #f9fafb; }
                body.dark-mode .stat-label,
                body.dark-mode .dashboard-subtitle { color: #9ca3af; }
                body.dark-mode .admin-table th { background: #374151; color: #d1d5db; }
                body.dark-mode .admin-table td { color: #e5e7eb; border-color: #374151; }
                body.dark-mode .search-box input { background: #374151; border-color: #4b5563; color: #f9fafb; }
                body.dark-mode .btn-user,
                body.dark-mode .btn-notification,
                body.dark-mode .btn-message,
                body.dark-mode .btn-theme { background: #374151; border-color: #4b5563; color: #d1d5db; }
                body.dark-mode .btn-refresh { background: #374151; border-color: #4b5563; color: #d1d5db; }
                body.dark-mode .search-results { background: #1f2937; border-color: #374151; }
                body.dark-mode .search-result-item { border-color: #374151; color: #e5e7eb; }
                body.dark-mode .search-result-item:hover { background: #374151; }
            `;
            document.head.appendChild(darkStyles);
        }
        
        if (toggleBtn) {
            toggleBtn.querySelector('i').classList.replace('fa-moon', 'fa-sun');
            toggleBtn.querySelector('i').style.color = '#ffd700';
        }
    } else {
        document.body.classList.remove('dark-mode');
        document.documentElement.style.removeProperty('--bg-color');
        document.documentElement.style.removeProperty('--text-color');
        document.documentElement.style.removeProperty('--card-bg');
        document.documentElement.style.removeProperty('--border-color');
        
        const darkStyles = document.getElementById('dark-mode-styles');
        if (darkStyles) darkStyles.remove();
        
        if (toggleBtn) {
            toggleBtn.querySelector('i').classList.replace('fa-sun', 'fa-moon');
            toggleBtn.querySelector('i').style.color = '';
        }
    }
}

// ========== BOUTON ACTUALISER ==========
function initRefreshButton() {
    const refreshBtn = document.querySelector('.btn-refresh');
    if (!refreshBtn) return;
    
    refreshBtn.addEventListener('click', function() {
        const icon = this.querySelector('i');
        icon.classList.add('fa-spin');
        setTimeout(() => location.reload(), 500);
    });
}

// ========== MISE À JOUR PÉRIODIQUE DES NOTIFICATIONS ==========
function initUpdateNotifications() {
    // Vérifier les nouvelles notifications toutes les 30 secondes
    setInterval(() => {
        fetch('../api/admin/check_notifications.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                    } else {
                        const bell = document.getElementById('notificationBell');
                        if (bell) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.count > 99 ? '99+' : data.count;
                            bell.appendChild(newBadge);
                        }
                    }
                }
            })
            .catch(() => {});
    }, 30000);
}

// ========== GESTION ERREURS ==========
window.addEventListener('error', (e) => {
    console.error('Erreur Dashboard Admin:', e.message);
});