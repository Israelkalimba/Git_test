/**
 * ISTAM Paiement - Login Étudiant
 * Gestion du formulaire de connexion étudiant
 * Authentification par MATRICULE et mot de passe (min 4 chiffres)
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initTogglePassword();
        initFormValidation();
        initLoadingState();
        initRememberMatricule();
        initKeyboardSubmit();
        initFeatureAnimation();
        initMatriculeFormatting();
        initInputFocusEffects();
        console.log('🎓 Login Étudiant ISTAM (Matricule) - Prêt');
    });

    // ========== TOGGLE PASSWORD VISIBILITY ==========
    function initTogglePassword() {
        const toggleBtn = document.querySelector('.toggle-password');
        const passwordInput = document.getElementById('password');
        
        if (!toggleBtn || !passwordInput) return;
        
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            const icon = toggleBtn.querySelector('i');
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
            
            // Effet de rebond
            toggleBtn.style.transform = 'translateY(-50%) scale(1.3)';
            setTimeout(() => {
                toggleBtn.style.transform = 'translateY(-50%) scale(1)';
            }, 200);
            
            // Remettre le focus sur l'input
            passwordInput.focus();
        });
    }

    // ========== VALIDATION DU FORMULAIRE ==========
    function initFormValidation() {
        const form = document.getElementById('loginForm');
        const matriculeInput = document.getElementById('matricule');
        const passwordInput = document.getElementById('password');
        
        if (!form || !matriculeInput || !passwordInput) return;

        // Validation matricule en temps réel
        matriculeInput.addEventListener('input', () => validateMatricule(matriculeInput));
        matriculeInput.addEventListener('blur', () => validateMatricule(matriculeInput));
        
        // Validation password en temps réel
        passwordInput.addEventListener('input', () => validatePassword(passwordInput));
        passwordInput.addEventListener('blur', () => validatePassword(passwordInput));

        // Filtrer les caractères non numériques pour le matricule
        matriculeInput.addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
                e.preventDefault();
            }
        });

        // Filtrer les caractères non numériques pour le mot de passe
        passwordInput.addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete') {
                e.preventDefault();
            }
        });

        // Soumission du formulaire
        form.addEventListener('submit', (e) => {
            let isValid = true;
            if (!validateMatricule(matriculeInput)) isValid = false;
            if (!validatePassword(passwordInput)) isValid = false;
            
            if (!isValid) {
                e.preventDefault();
                // Secouer le formulaire
                form.style.animation = 'none';
                form.offsetHeight; // Trigger reflow
                form.style.animation = 'shake 0.5s ease';
            }
        });
    }

    function validateMatricule(input) {
        let value = input.value.trim();
        
        // Nettoyer les caractères non numériques
        value = value.replace(/[^0-9]/g, '');
        input.value = value;
        
        if (!value) {
            setFieldStatus(input, false, 'Veuillez entrer votre numéro matricule');
            return false;
        }
        if (value.length < 6) {
            setFieldStatus(input, false, 'Le matricule doit contenir au moins 6 chiffres');
            return false;
        }
        if (value.length > 20) {
            setFieldStatus(input, false, 'Le matricule ne doit pas dépasser 20 chiffres');
            return false;
        }
        setFieldStatus(input, true, '');
        return true;
    }

    function validatePassword(input) {
        let value = input.value.trim();
        
        // Nettoyer les caractères non numériques
        value = value.replace(/[^0-9]/g, '');
        input.value = value;
        
        if (!value) {
            setFieldStatus(input, false, 'Veuillez entrer votre mot de passe');
            return false;
        }
        if (value.length < 4) {
            setFieldStatus(input, false, 'Le mot de passe doit contenir au moins 4 chiffres');
            return false;
        }
        setFieldStatus(input, true, '');
        return true;
    }

    function setFieldStatus(input, isValid, message) {
        const field = input.closest('.input-field');
        const errorEl = field?.querySelector('.error-message');
        
        if (isValid) {
            input.classList.add('is-valid');
            input.classList.remove('is-invalid');
            if (errorEl) {
                errorEl.style.opacity = '0';
                errorEl.style.maxHeight = '0';
            }
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.opacity = '1';
                errorEl.style.maxHeight = '30px';
            }
        }
    }

    // ========== FORMATAGE DU MATRICULE (ESPACES TOUS LES 4 CHIFFRES) ==========
    function initMatriculeFormatting() {
        const matriculeInput = document.getElementById('matricule');
        if (!matriculeInput) return;

        matriculeInput.addEventListener('input', () => {
            let value = matriculeInput.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
            // Ajouter des espaces tous les 4 chiffres pour la lisibilité
            if (value.length > 4) {
                value = value.match(/.{1,4}/g).join(' ');
            }
            matriculeInput.value = value;
        });

        // Nettoyer les espaces avant soumission
        document.getElementById('loginForm')?.addEventListener('submit', () => {
            matriculeInput.value = matriculeInput.value.replace(/\s/g, '');
        });
    }

    // ========== LOADING STATE AU SUBMIT ==========
    function initLoadingState() {
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (!form || !submitBtn) return;
        
        form.addEventListener('submit', () => {
            const matriculeInput = document.getElementById('matricule');
            const passwordInput = document.getElementById('password');
            
            if (matriculeInput?.classList.contains('is-valid') && passwordInput?.classList.contains('is-valid')) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }

    // ========== SE SOUVENIR DU MATRICULE ==========
    function initRememberMatricule() {
        const matriculeInput = document.getElementById('matricule');
        const checkbox = document.getElementById('rememberMe');
        
        if (!matriculeInput || !checkbox) return;
        
        // Charger le matricule sauvegardé
        const savedMatricule = localStorage.getItem('istam_etu_matricule');
        if (savedMatricule) {
            matriculeInput.value = savedMatricule;
            checkbox.checked = true;
            validateMatricule(matriculeInput);
        }
        
        // Sauvegarder à la soumission
        document.getElementById('loginForm')?.addEventListener('submit', () => {
            const cleanMatricule = matriculeInput.value.replace(/\s/g, '');
            if (checkbox.checked && cleanMatricule) {
                localStorage.setItem('istam_etu_matricule', cleanMatricule);
            } else {
                localStorage.removeItem('istam_etu_matricule');
            }
        });
    }

    // ========== RACCOURCI CLAVIER ENTER ==========
    function initKeyboardSubmit() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.getElementById('loginForm');
                if (form) {
                    form.requestSubmit();
                }
            }
        });
    }

    // ========== ANIMATION DES FEATURES AU SCROLL ==========
    function initFeatureAnimation() {
        const items = document.querySelectorAll('.feature-item');
        if (items.length === 0) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        entry.target.style.transition = 'all 0.5s ease';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateX(0)';
                    }, index * 120);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        
        items.forEach(item => {
            item.style.opacity = '0';
            observer.observe(item);
        });
    }

    // ========== EFFETS DE FOCUS SUR LES INPUTS ==========
    function initInputFocusEffects() {
        const inputs = document.querySelectorAll('.input-wrapper input');
        
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                const wrapper = input.closest('.input-wrapper');
                if (wrapper) {
                    wrapper.style.transform = 'scale(1.02)';
                    wrapper.style.transition = 'transform 0.2s ease';
                }
            });
            
            input.addEventListener('blur', () => {
                const wrapper = input.closest('.input-wrapper');
                if (wrapper) {
                    wrapper.style.transform = 'scale(1)';
                }
            });
        });
    }

    // ========== GESTION DES ERREURS GLOBALES ==========
    window.addEventListener('error', (e) => {
        console.error('Erreur sur la page login étudiant:', e.message);
    });

})();