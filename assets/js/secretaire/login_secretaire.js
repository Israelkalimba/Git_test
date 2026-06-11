/**
 * ISTAM Paiement - Login Secrétaire
 * Gestion du formulaire de connexion secrétaire
 */
document.addEventListener('DOMContentLoaded', () => {
    initTogglePassword();
    initFormValidation();
    initLoadingState();
    initRememberMe();
    initKeyboardSubmit();
    initFeatureAnimation();
    initInputFocusEffects();
    initProfessionalGreeting();
    console.log('💼 Login Secrétaire ISTAM - Prêt');
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
        
        toggleBtn.style.transform = 'translateY(-50%) scale(1.3)';
        setTimeout(() => {
            toggleBtn.style.transform = 'translateY(-50%) scale(1)';
        }, 200);
        
        passwordInput.focus();
    });
}

// ========== VALIDATION DU FORMULAIRE ==========
function initFormValidation() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    if (!form || !emailInput || !passwordInput) return;

    emailInput.addEventListener('input', () => validateEmail(emailInput));
    emailInput.addEventListener('blur', () => validateEmail(emailInput));
    
    passwordInput.addEventListener('input', () => validatePassword(passwordInput));
    passwordInput.addEventListener('blur', () => validatePassword(passwordInput));

    form.addEventListener('submit', (e) => {
        let isValid = true;
        if (!validateEmail(emailInput)) isValid = false;
        if (!validatePassword(passwordInput)) isValid = false;
        
        if (!isValid) {
            e.preventDefault();
            form.style.animation = 'none';
            form.offsetHeight;
            form.style.animation = 'shake 0.5s ease';
        }
    });
}

function validateEmail(input) {
    const value = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!value) {
        setFieldStatus(input, false, 'Veuillez entrer votre email professionnel');
        return false;
    }
    if (!emailRegex.test(value)) {
        setFieldStatus(input, false, 'Format d\'email invalide');
        return false;
    }
    setFieldStatus(input, true, '');
    return true;
}

function validatePassword(input) {
    const value = input.value;
    
    if (!value) {
        setFieldStatus(input, false, 'Veuillez entrer votre mot de passe');
        return false;
    }
    if (value.length < 4) {
        setFieldStatus(input, false, 'Minimum 4 caractères requis');
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

// ========== LOADING STATE ==========
function initLoadingState() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!form || !submitBtn) return;
    
    form.addEventListener('submit', () => {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        
        if (emailInput?.classList.contains('is-valid') && passwordInput?.classList.contains('is-valid')) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }
    });
}

// ========== SE SOUVENIR DE MOI ==========
function initRememberMe() {
    const emailInput = document.getElementById('email');
    const checkbox = document.getElementById('rememberMe');
    
    if (!emailInput || !checkbox) return;
    
    // Charge l'email sauvegardé du secrétaire
    const savedEmail = localStorage.getItem('istam_sec_email');
    if (savedEmail) {
        emailInput.value = savedEmail;
        checkbox.checked = true;
        validateEmail(emailInput);
    }
    
    document.getElementById('loginForm')?.addEventListener('submit', () => {
        if (checkbox.checked && emailInput.value.trim()) {
            localStorage.setItem('istam_sec_email', emailInput.value.trim());
        } else {
            localStorage.removeItem('istam_sec_email');
        }
    });
}

// ========== CTRL+ENTER ==========
function initKeyboardSubmit() {
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            document.getElementById('loginForm')?.requestSubmit();
        }
    });
}

// ========== ANIMATION FEATURES ==========
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
                }, index * 150);
                
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    items.forEach(item => {
        item.style.opacity = '0';
        observer.observe(item);
    });
}

// ========== EFFETS INPUT FOCUS ==========
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

// ========== MESSAGE D'ACCUEIL PROFESSIONNEL ==========
function initProfessionalGreeting() {
    const hour = new Date().getHours();
    let greeting = '';
    
    if (hour < 12) greeting = 'Bonjour';
    else if (hour < 18) greeting = 'Bon après-midi';
    else greeting = 'Bonsoir';
    
    // Stocker le message dans le sessionStorage pour le dashboard
    sessionStorage.setItem('istam_greeting', `${greeting}, prêt(e) pour la journée ?`);
}