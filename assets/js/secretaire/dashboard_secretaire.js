/**
 * ISTAM Paiement - Dashboard Secrétaire JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initMobileSidebar();
        initUserMenu();
        console.log('💼 Dashboard Secrétaire ISTAM - Prêt');
    });

    function initSidebar() {
        const sidebar = document.getElementById('secretaireSidebar');
        const toggle = document.getElementById('btnSidebarToggle');
        if (!sidebar || !toggle) return;

        const saved = localStorage.getItem('istam_sec_sidebar');
        if (saved === 'collapsed') sidebar.classList.add('collapsed');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('istam_sec_sidebar', 
                sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
        });
    }

    function initMobileSidebar() {
        const btn = document.getElementById('sidebarMobileToggle');
        const sidebar = document.getElementById('secretaireSidebar');
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

    function initUserMenu() {
        const btn = document.getElementById('btnUser');
        const menu = document.getElementById('menuUser');
        if (!btn || !menu) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
        });
        document.addEventListener('click', () => menu.classList.remove('show'));
    }

})();
