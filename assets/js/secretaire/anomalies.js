/**
 * ISTAM Paiement - Anomalies Secrétaire JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        console.log('🔧 Anomalies Secrétaire - Prêt');
    });

    // ========== TRAITER UNE ANOMALIE ==========
    window.traiterAnomalie = function(id, nom, matricule) {
        const commentaire = prompt(
            '📋 TRAITEMENT D\'ANOMALIE\n\n' +
            'Anomalie #' + id + '\n' +
            'Étudiant : ' + nom + ' (' + matricule + ')\n\n' +
            'Veuillez décrire l\'action réalisée ou le commentaire de résolution :\n' +
            '(ex: "Contacté l\'étudiant, paiement confirmé par SMS", "Doublon identifié et corrigé"...)'
        );
        
        if (commentaire && commentaire.trim() !== '') {
            if (confirm(
                '✅ CONFIRMATION\n\n' +
                'Marquer l\'anomalie #' + id + ' comme traitée ?\n\n' +
                'Commentaire : ' + commentaire + '\n\n' +
                'Cette action sera journalisée et l\'administrateur sera notifié.'
            )) {
                window.location.href = '?action=traiter&id=' + id + 
                    '&commentaire=' + encodeURIComponent(commentaire);
            }
        } else if (commentaire !== null) {
            alert('❌ Un commentaire est obligatoire pour documenter la résolution.');
        }
    };

    // ========== ANIMATION LIGNES ==========
    document.querySelectorAll('.anomalie-row').forEach((row, index) => {
        row.style.animation = `fadeInLeft 0.3s ease ${index * 0.03}s both`;
    });

    if (!document.getElementById('anim-anomalie')) {
        const style = document.createElement('style');
        style.id = 'anim-anomalie';
        style.textContent = `
            @keyframes fadeInLeft {
                from { opacity: 0; transform: translateX(-10px); }
                to { opacity: 1; transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);
    }

})();