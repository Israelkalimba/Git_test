/**
 * ISTAM Paiement - Validation Paiements JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        console.log('✅ Interface Validation Paiements - Prête');
    });

    // ========== FORCER SUCCÈS AVEC JUSTIFICATIF ==========
    window.forcerSucces = function(id, nom, matricule) {
        const justificatif = prompt(
            '📋 VALIDATION MANUELLE\n\n' +
            'Étudiant : ' + nom + ' (' + matricule + ')\n' +
            'Paiement #' + id + '\n\n' +
            '⚠️ Veuillez fournir un JUSTIFICATIF obligatoire :\n' +
            '(SMS de confirmation, référence bancaire, motif de validation...)'
        );
        
        if (justificatif && justificatif.trim() !== '') {
            if (confirm(
                '✅ CONFIRMATION FINALE\n\n' +
                'Valider le paiement #' + id + ' pour ' + nom + ' ?\n\n' +
                'Justificatif : ' + justificatif + '\n\n' +
                'Cette action sera enregistrée dans le journal d\'audit.'
            )) {
                window.location.href = '?action=forcer_succes&id=' + id + 
                    '&justificatif=' + encodeURIComponent(justificatif);
            }
        } else if (justificatif !== null) {
            alert('❌ Le justificatif est obligatoire pour forcer une validation manuelle.');
        }
    };

    // ========== REJETER AVEC MOTIF ==========
    window.rejeterPaiement = function(id, nom) {
        const motif = prompt(
            '❌ REJET DE PAIEMENT\n\n' +
            'Étudiant : ' + nom + '\n' +
            'Paiement #' + id + '\n\n' +
            'Veuillez fournir un MOTIF de rejet :\n' +
            '(doublon, fraude, erreur montant, étudiant inconnu...)'
        );
        
        if (motif && motif.trim() !== '') {
            if (confirm(
                '⚠️ CONFIRMATION DE REJET\n\n' +
                'Rejeter le paiement #' + id + ' pour ' + nom + ' ?\n\n' +
                'Motif : ' + motif + '\n\n' +
                'Cette action est IRRÉVERSIBLE.'
            )) {
                window.location.href = '?action=rejeter&id=' + id + 
                    '&motif=' + encodeURIComponent(motif);
            }
        } else if (motif !== null) {
            alert('❌ Le motif de rejet est obligatoire.');
        }
    };

    // ========== CONFIRMATION VÉRIFIER TOUT ==========
    document.querySelectorAll('a[href*="verifier_tout"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm(
                '🔍 VÉRIFICATION EN MASSE\n\n' +
                'Cette action va interroger PayLedger pour toutes les transactions en attente.\n\n' +
                'Cela peut prendre quelques secondes.\n\n' +
                'Continuer ?'
            )) {
                e.preventDefault();
            }
        });
    });

    // ========== COPIER RÉFÉRENCE ==========
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

})();