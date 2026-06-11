/**
 * ISTAM Paiement - API PayLedger JS
 * Gestion complète des configurations API et tests
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initCopyButtons();
        initTestAnimation();
        console.log('🔌 API PayLedger ISTAM - Initialisé');
        console.log('📍 Endpoint: https://pay-ledger.b-manage.net/api/v1/gateway');
        console.log('🔑 Clé: pl_htSEOb8G7VojrKRHKNHEcQySHqHKYxzldZkLsBU3');
        console.log('📅 Expire le: 05/06/2026 08:42');
    });

    // ========== COPIER UNE CLÉ API ==========
    window.copierCle = function(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        input.select();
        input.setSelectionRange(0, 99999);
        
        try {
            navigator.clipboard.writeText(input.value).then(() => {
                showToast('✅ Clé API copiée dans le presse-papier !', 'success');
            }).catch(() => {
                document.execCommand('copy');
                showToast('✅ Clé API copiée !', 'success');
            });
        } catch (e) {
            document.execCommand('copy');
            showToast('✅ Clé API copiée !', 'success');
        }
    };

    // ========== TOGGLE VISIBILITÉ CLÉ API ==========
    window.toggleAPIKey = function(id, fullKey) {
        const el = document.getElementById('key-' + id);
        if (!el) return;
        
        const isMasked = el.textContent.includes('••••');
        el.textContent = isMasked ? fullKey : fullKey.substring(0, 8) + '••••••••••••••••';
    };

    // ========== COPIER UNE CLÉ API (VERSION TABLEAU) ==========
    window.copierCleAPI = function(key) {
        try {
            navigator.clipboard.writeText(key).then(() => {
                showToast('✅ Clé API copiée !', 'success');
            });
        } catch (e) {
            prompt('Copiez manuellement la clé API :', key);
        }
    };

    // ========== GÉNÉRER UNE CLÉ API ALÉATOIRE ==========
    window.genererCleAPI = function() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let key = 'pl_';
        for (let i = 0; i < 32; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        const input = document.getElementById('apiKeyInput');
        if (input) input.value = key;
    };

    // ========== MODAL AJOUTER ==========
    window.ouvrirModalAjouter = function() {
        const modal = new bootstrap.Modal(document.getElementById('modalAjouter'));
        modal.show();
    };

    // ========== MODAL MODIFIER ==========
    window.ouvrirModalModifier = function(api) {
        document.getElementById('modIdApi').value = api.id_api;
        document.getElementById('modNomApi').value = api.nom_api;
        document.getElementById('modApiKey').value = api.api_key;
        document.getElementById('modEndpoint').value = api.endpoint;
        const modal = new bootstrap.Modal(document.getElementById('modalModifier'));
        modal.show();
    };

    // ========== CONFIRMATION SUPPRESSION ==========
    window.confirmerSuppression = function(id, nom) {
        document.getElementById('supprimerInfo').textContent = 
            'API : « ' + nom + ' » (ID: #' + id + ')';
        document.getElementById('btnConfirmerSuppression').href = 
            '?action=supprimer&id=' + id;
        const modal = new bootstrap.Modal(document.getElementById('modalSupprimer'));
        modal.show();
    };

    // ========== INIT BOUTONS COPIER ==========
    function initCopyButtons() {
        document.querySelectorAll('.btn-copy-key').forEach(btn => {
            btn.addEventListener('click', function() {
                const wrapper = this.closest('.api-key-wrapper');
                const maskedEl = wrapper?.querySelector('.api-key-masked');
                if (maskedEl) {
                    const fullKey = maskedEl.getAttribute('data-full-key') || '';
                    copierCleAPI(fullKey);
                }
            });
        });
    }

    // ========== ANIMATION TEST ==========
    function initTestAnimation() {
        const testLinks = document.querySelectorAll('a[href*="action=tester"]');
        testLinks.forEach(link => {
            link.addEventListener('click', function() {
                const icon = this.querySelector('i.fa-play');
                if (icon) {
                    icon.classList.add('fa-spin');
                    setTimeout(() => icon.classList.remove('fa-spin'), 3000);
                }
            });
        });
    }

    // ========== TOAST NOTIFICATION ==========
    function showToast(message, type) {
        // Supprimer les toasts existants
        document.querySelectorAll('.api-toast').forEach(el => el.remove());
        
        const toast = document.createElement('div');
        toast.className = 'api-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 9999;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: toastIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    // Injecter les animations toast si pas déjà présentes
    if (!document.getElementById('toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
        style.textContent = `
            @keyframes toastIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes toastOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(20px); }
            }
        `;
        document.head.appendChild(style);
    }

    // ========== SURVEILLANCE EXPIRATION CLÉ ==========
    function checkExpiration() {
        const dateExpiration = new Date('2026-06-05T08:42:00');
        const maintenant = new Date();
        const joursRestants = Math.ceil((dateExpiration - maintenant) / (1000 * 60 * 60 * 24));
        
        if (joursRestants <= 30 && joursRestants > 0) {
            console.warn(`⚠️ La clé API expire dans ${joursRestants} jours ! (${dateExpiration.toLocaleDateString('fr-FR')})`);
        } else if (joursRestants <= 0) {
            console.error('❌ La clé API a expiré ! Veuillez contacter PayLedger pour la renouveler.');
        }
    }
    
    checkExpiration();

})();