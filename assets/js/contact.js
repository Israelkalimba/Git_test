// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function () {
    scheduleAOSInit();
    initNavbarScroll();
    initBackToTop();
    initFormValidation();
    initStatusSimulation();
});

// ========== AOS ==========
function scheduleAOSInit() {
    const start = () => {
        if (typeof AOS === 'undefined') return;
        AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
    };

    if ('requestIdleCallback' in window) {
        requestIdleCallback(start, { timeout: 1200 });
    } else {
        setTimeout(start, 200);
    }
}

// ========== NAVBAR SCROLL ==========
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function () {
        if (window.scrollY > 50) {
            navbar.style.padding = '10px 0';
            navbar.style.boxShadow = '0 5px 30px rgba(0, 0, 0, 0.15)';
        } else {
            navbar.style.padding = '15px 0';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        }
    }, { passive: true });
}

// ========== FORM HANDLER ==========
function handleFormSubmit(event) {
    event.preventDefault();

    const form = document.getElementById('contactForm');
    const successMessage = document.getElementById('successMessage');

    // Simuler un chargement
    const submitBtn = form.querySelector('.btn-submit');
    const originalText = submitBtn.querySelector('.btn-text').textContent;
    submitBtn.querySelector('.btn-text').textContent = 'Envoi en cours...';
    submitBtn.disabled = true;

    setTimeout(() => {
        // Masquer le formulaire
        form.style.display = 'none';

        // Afficher le message de succès
        successMessage.style.display = 'block';

        // Réinitialiser le bouton (au cas où)
        submitBtn.querySelector('.btn-text').textContent = originalText;
        submitBtn.disabled = false;

        // Animation de scroll vers le message
        successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });

        console.log('📨 Formulaire soumis avec succès (démonstration)');
    }, 1500);
}

// ========== RESET FORM ==========
function resetForm() {
    const form = document.getElementById('contactForm');
    const successMessage = document.getElementById('successMessage');

    form.reset();
    form.style.display = 'block';
    successMessage.style.display = 'none';

    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ========== FORM VALIDATION (en direct) ==========
function initFormValidation() {
    const form = document.getElementById('contactForm');
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        input.addEventListener('blur', function () {
            validateField(this);
        });

        input.addEventListener('input', function () {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    if (field.checkValidity()) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
    }
}

// ========== STATUS SIMULATION (Ouvert/Fermé) ==========
function initStatusSimulation() {
    updateOpenStatusBadge();

    // Un seul timer global pour eviter l'accumulation d'intervalles.
    setInterval(updateOpenStatusBadge, 60000);
}

function updateOpenStatusBadge() {
    const statusBadge = document.querySelector('.status-badge');
    if (!statusBadge) return;

    const now = new Date();
    const day = now.getDay(); // 0 = Dimanche, 6 = Samedi
    const hour = now.getHours();

    let isOpen = false;

    if (day >= 1 && day <= 5) {
        // Lundi à Vendredi : 8h-17h
        isOpen = (hour >= 8 && hour < 17);
    } else if (day === 6) {
        // Samedi : 9h-13h
        isOpen = (hour >= 9 && hour < 13);
    }

    if (!isOpen) {
        statusBadge.classList.remove('open');
        statusBadge.classList.add('closed');
        statusBadge.innerHTML = '<span class="status-dot closed-dot"></span> Fermé actuellement';

        // Ajouter le style pour le point fermé
        const style = document.createElement('style');
        style.textContent = `
            .status-badge.closed {
                background: rgba(220, 53, 69, 0.1);
                color: var(--danger);
            }
            .closed-dot {
                width: 8px;
                height: 8px;
                background: var(--danger);
                border-radius: 50%;
                display: inline-block;
            }
        `;
        document.head.appendChild(style);
    } else {
        statusBadge.classList.remove('closed');
        statusBadge.classList.add('open');
        statusBadge.innerHTML = '<span class="status-dot"></span> Ouvert actuellement';
    }
}

// ========== BACK TO TOP ==========
function initBackToTop() {
    const backToTopBtn = document.createElement('button');
    backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.setAttribute('aria-label', 'Retour en haut');
    document.body.appendChild(backToTopBtn);

    const style = document.createElement('style');
    style.textContent = `
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--gradient-2, linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%));
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(13, 110, 253, 0.4);
        }
        
        .back-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.6);
        }
    `;
    document.head.appendChild(style);

    window.addEventListener('scroll', function () {
        if (window.scrollY > 500) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    }, { passive: true });

    backToTopBtn.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ========== COPY TO CLIPBOARD (pour les numéros de téléphone) ==========
document.querySelectorAll('.contact-info-content p').forEach(info => {
    info.addEventListener('click', function () {
        const phoneRegex = /\+243 \d{3} \d{3} \d{3}/;
        const match = this.textContent.match(phoneRegex);
        if (match) {
            navigator.clipboard.writeText(match[0]).then(() => {
                showToast('Numéro copié : ' + match[0]);
            });
        }
    });
});

// ========== TOAST NOTIFICATION ==========
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Style du toast
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    .toast-notification {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: var(--dark);
        color: white;
        padding: 15px 30px;
        border-radius: 50px;
        z-index: 10000;
        opacity: 0;
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .toast-notification.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    
    .toast-notification i {
        color: var(--success);
    }
`;
document.head.appendChild(toastStyle);

console.log('📞 Page Contact ISTAM initialisée avec succès');