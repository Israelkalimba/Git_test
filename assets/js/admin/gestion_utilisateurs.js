/**
 * ISTAM Paiement - Gestion du Personnel JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initAutoFocus();
        console.log('👥 Gestion Personnel ISTAM - Prêt');
    });

    // ========== GÉNÉRER MOT DE PASSE ALÉATOIRE ==========
    window.genererMotDePasse = function() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@!';
        let mdp = 'Istam@';
        for (let i = 0; i < 6; i++) {
            mdp += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('passwordAjout').value = mdp;
    };

    // ========== MODAL AJOUTER ==========
    window.ouvrirModalAjouter = function() {
        const modal = new bootstrap.Modal(document.getElementById('modalAjouter'));
        modal.show();
    };

    // ========== MODAL MODIFIER ==========
    window.ouvrirModalModifier = function(user) {
        document.getElementById('modIdUtilisateur').value = user.id_utilisateur;
        document.getElementById('modNom').value = user.nom;
        document.getElementById('modEmail').value = user.email;
        document.getElementById('modRole').value = user.role;
        
        const modal = new bootstrap.Modal(document.getElementById('modalModifier'));
        modal.show();
    };

    // ========== CONFIRMATION SUPPRESSION ==========
    window.confirmerSuppression = function(id, nom, role) {
        const roleLabel = role === 'admin' ? 'Administrateur' : 'Secrétaire';
        document.getElementById('supprimerInfo').textContent = 
            `${roleLabel} : ${nom} (ID: #${id})`;
        document.getElementById('btnConfirmerSuppression').href = 
            `?action=supprimer&id=${id}`;
        
        const modal = new bootstrap.Modal(document.getElementById('modalSupprimer'));
        modal.show();
    };

    function initAutoFocus() {
        const alert = document.querySelector('.alert');
        if (alert) alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

})();