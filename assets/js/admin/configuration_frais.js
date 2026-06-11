/**
 * ISTAM Paiement - Configuration des Frais JS
 * Gestion dynamique des conversions USD/FC
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        console.log('⚙️ Configuration Frais ISTAM - Prêt');
        
        // Initialiser les conversions si nécessaire
        if (document.getElementById('nouveauTauxGlobal')) {
            previewImpactGlobal();
        }
    });

    // ========== CONVERSION AJOUT ==========
    window.calculerConversionAjout = function() {
        const usd = parseFloat(document.getElementById('montantUsdAjout')?.value) || 0;
        const taux = parseFloat(document.getElementById('tauxChangeAjout')?.value) || 0;
        const fc = usd * taux;
        const el = document.getElementById('montantFcAjout');
        if (el) {
            el.value = fc.toLocaleString('fr-FR', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }) + ' FC';
        }
    };

    // ========== CONVERSION MODIF ==========
    window.calculerConversionModif = function() {
        const usd = parseFloat(document.getElementById('modMontantUsd')?.value) || 0;
        const taux = parseFloat(document.getElementById('modTauxChange')?.value) || 0;
        const fc = usd * taux;
        const el = document.getElementById('modMontantFc');
        if (el) {
            el.value = fc.toLocaleString('fr-FR', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }) + ' FC';
        }
    };

    // ========== CHARGER FILIÈRES AJOUT ==========
    window.chargerFilieresAjout = function() {
        const idFaculte = document.getElementById('selectFaculteAjout')?.value;
        const selectFiliere = document.getElementById('selectFiliereAjout');
        if (!selectFiliere) return;
        
        if (!idFaculte) {
            selectFiliere.innerHTML = '<option value="">-- Sélectionnez d\'abord une faculté --</option>';
            return;
        }
        
        selectFiliere.innerHTML = '<option value="">Chargement...</option>';
        
        fetch(`../api/admin/get_filieres.php?faculte=${idFaculte}`)
            .then(r => r.json())
            .then(data => {
                selectFiliere.innerHTML = '<option value="">-- Choisir une filière --</option>';
                if (data && data.length > 0) {
                    data.forEach(f => {
                        selectFiliere.innerHTML += `<option value="${f.id_filiere}">${f.nom_filiere}</option>`;
                    });
                }
            })
            .catch(() => {
                selectFiliere.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    };

    // ========== CHARGER FILIÈRES MODIF ==========
    window.chargerFilieresModif = function() {
        const idFaculte = document.getElementById('selectFaculteModif')?.value;
        const selectFiliere = document.getElementById('selectFiliereModif');
        if (!selectFiliere) return;
        
        if (!idFaculte) {
            selectFiliere.innerHTML = '<option value="">-- Sélectionnez d\'abord une faculté --</option>';
            return;
        }
        
        selectFiliere.innerHTML = '<option value="">Chargement...</option>';
        
        fetch(`../api/admin/get_filieres.php?faculte=${idFaculte}`)
            .then(r => r.json())
            .then(data => {
                selectFiliere.innerHTML = '<option value="">-- Choisir une filière --</option>';
                if (data && data.length > 0) {
                    data.forEach(f => {
                        selectFiliere.innerHTML += `<option value="${f.id_filiere}">${f.nom_filiere}</option>`;
                    });
                }
            })
            .catch(() => {
                selectFiliere.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    };

    // ========== MODAL AJOUTER ==========
    window.ouvrirModalAjouter = function() {
        const form = document.getElementById('formAjouter');
        if (form) form.reset();
        
        const selectFiliere = document.getElementById('selectFiliereAjout');
        if (selectFiliere) {
            selectFiliere.innerHTML = '<option value="">-- Sélectionnez d\'abord une faculté --</option>';
        }
        
        const modalAjouter = new bootstrap.Modal(document.getElementById('modalAjouter'));
        modalAjouter.show();
        
        setTimeout(() => calculerConversionAjout(), 300);
    };

    // ========== MODAL MODIFIER ==========
    window.ouvrirModalModifier = function(frais) {
        // Remplir les champs
        document.getElementById('modIdFrais').value = frais.id_frais;
        document.getElementById('modTypeFrais').value = frais.type_frais;
        document.getElementById('modPromotion').value = frais.id_promotion;
        document.getElementById('modMontantUsd').value = frais.montant;
        document.getElementById('modTauxChange').value = frais.taux_change || 2300;
        
        // Définir la faculté et charger les filières
        const selectFaculte = document.getElementById('selectFaculteModif');
        const selectFiliere = document.getElementById('selectFiliereModif');
        
        // Trouver la faculté correspondante via les données PHP
        if (frais.id_faculte) {
            selectFaculte.value = frais.id_faculte;
            
            // Charger les filières de cette faculté
            chargerFilieresModif();
            
            // Une fois chargé, sélectionner la filière
            setTimeout(() => {
                if (selectFiliere && frais.id_filiere) {
                    selectFiliere.value = frais.id_filiere;
                }
            }, 500);
        }
        
        // Calculer la conversion
        setTimeout(() => calculerConversionModif(), 400);
        
        const modalModifier = new bootstrap.Modal(document.getElementById('modalModifier'));
        modalModifier.show();
    };

    // ========== MODAL TAUX GLOBAL ==========
    window.ouvrirModalTauxGlobal = function() {
        const inputTaux = document.getElementById('nouveauTauxGlobal');
        if (inputTaux) {
            inputTaux.value = tauxDefaut;
        }
        previewImpactGlobal();
        
        const modalTauxGlobal = new bootstrap.Modal(document.getElementById('modalTauxGlobal'));
        modalTauxGlobal.show();
    };

    // ========== APERÇU DE L'IMPACT DU CHANGEMENT DE TAUX ==========
    window.previewImpactGlobal = function() {
        const nouveauTaux = parseFloat(document.getElementById('nouveauTauxGlobal')?.value) || 0;
        const ancienTaux = parseFloat(tauxDefaut) || 0;
        const impactDiv = document.getElementById('impactPreview');
        const tableBody = document.getElementById('impactTableBody');
        
        if (!impactDiv || !tableBody) return;
        
        if (nouveauTaux <= 0 || nouveauTaux === ancienTaux) {
            impactDiv.style.display = 'none';
            return;
        }
        
        impactDiv.style.display = 'block';
        
        // Exemples de montants pour l'aperçu
        const exemples = [50, 100, 150, 200, 300, 500];
        
        let html = '';
        exemples.forEach(montant => {
            const ancienFc = montant * ancienTaux;
            const nouveauFc = montant * nouveauTaux;
            const difference = nouveauFc - ancienFc;
            const diffClass = difference >= 0 ? 'diff-positive' : 'diff-negative';
            const diffSigne = difference >= 0 ? '+' : '';
            
            html += `
                <tr>
                    <td><strong>$${montant}</strong></td>
                    <td>${ancienFc.toLocaleString('fr-FR')} FC</td>
                    <td>${nouveauFc.toLocaleString('fr-FR')} FC</td>
                    <td class="${diffClass}">${diffSigne}${difference.toLocaleString('fr-FR')} FC</td>
                </tr>
            `;
        });
        
        // Ajouter une ligne de résumé
        const totalImpact = exemples.reduce((sum, m) => sum + (m * nouveauTaux - m * ancienTaux), 0);
        html += `
            <tr class="table-warning fw-bold">
                <td colspan="3">Impact total sur ces exemples</td>
                <td class="${totalImpact >= 0 ? 'diff-positive' : 'diff-negative'}">
                    ${totalImpact >= 0 ? '+' : ''}${totalImpact.toLocaleString('fr-FR')} FC
                </td>
            </tr>
        `;
        
        tableBody.innerHTML = html;
    };

    // ========== SUPPRESSION ==========
    window.confirmerSuppression = function(id, typeFrais) {
        document.getElementById('supprimerInfo').textContent = 
            'Frais : « ' + typeFrais + ' » (ID: #' + id + ')';
        document.getElementById('btnConfirmerSuppression').href = 
            '?action=supprimer&id=' + id;
        
        const modalSupprimer = new bootstrap.Modal(document.getElementById('modalSupprimer'));
        modalSupprimer.show();
    };

})();