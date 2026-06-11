// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
    initAOS();
    initNavbarScroll();
    initSmoothScroll();
    initStatCounter();
    initCarouselAutoPlay();
});

// ========== AOS INITIALIZATION ==========
function initAOS() {
    AOS.init({
        duration: 1000,
        easing: 'ease-in-out',
        once: true,
        mirror: false
    });
}

// ========== NAVBAR SCROLL EFFECT ==========
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

// ========== SMOOTH SCROLL ==========
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
            // Fermer le menu mobile si ouvert
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse.classList.contains('show')) {
                navbarCollapse.classList.remove('show');
            }
        });
    });
}

// ========== STAT COUNTER ANIMATION ==========
function initStatCounter() {
    const statValues = document.querySelectorAll('.stat-value');
    let started = false;
    
    function animateStats() {
        if (started) return;
        
        statValues.forEach(stat => {
            const target = parseInt(stat.getAttribute('data-count'));
            const duration = 2000;
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            const stepTime = duration / steps;
            
            const counter = setInterval(() => {
                current += increment;
                if (current >= target) {
                    stat.textContent = target.toLocaleString();
                    clearInterval(counter);
                } else {
                    stat.textContent = Math.floor(current).toLocaleString();
                }
            }, stepTime);
        });
        
        started = true;
    }
    
    // Intersection Observer pour déclencher l'animation quand visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateStats();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    const statsSection = document.querySelector('#statistiques');
    if (statsSection) {
        observer.observe(statsSection);
    }
}

// ========== CAROUSEL MANUAL CONTROLS ==========
function initCarouselAutoPlay() {
    const carousel = document.getElementById('mainCarousel');
    
    if (carousel) {
        // Pause au survol
        carousel.addEventListener('mouseenter', function() {
            const bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) {
                bsCarousel.pause();
            }
        });
        
        // Reprendre quand la souris quitte
        carousel.addEventListener('mouseleave', function() {
            const bsCarousel = bootstrap.Carousel.getInstance(carousel);
            if (bsCarousel) {
                bsCarousel.cycle();
            }
        });
    }
}

// ========== SERVICE CARD HOVER EFFECT ==========
document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px)';
    });
    
    card.addEventListener('mouseleave', function() {
        if (!this.classList.contains('featured')) {
            this.style.transform = 'translateY(0)';
        } else {
            this.style.transform = 'scale(1.05)';
        }
    });
});

// ========== PARALLAX EFFECT ON CAROUSEL ==========
window.addEventListener('scroll', function() {
    const scrollPosition = window.pageYOffset;
    const carouselItems = document.querySelectorAll('.carousel-bg');
    
    carouselItems.forEach(bg => {
        const speed = 0.5;
        bg.style.transform = `translateY(${scrollPosition * speed}px)`;
    });
});

// ========== PRELOADER (OPTIONNEL) ==========
window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

// ========== KEYBOARD NAVIGATION ==========
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const navbarCollapse = document.querySelector('.navbar-collapse');
        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            navbarCollapse.classList.remove('show');
        }
    }
});

// ========== ACTIVE NAV LINK UPDATE ==========
function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    
    let currentSection = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop - 100;
        const sectionHeight = section.offsetHeight;
        
        if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
            currentSection = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + currentSection) {
            link.classList.add('active');
        }
    });
}

window.addEventListener('scroll', updateActiveNavLink);

// ========== BACK TO TOP BUTTON ==========
// Créer le bouton
const backToTopBtn = document.createElement('button');
backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
backToTopBtn.className = 'back-to-top';
backToTopBtn.setAttribute('aria-label', 'Retour en haut');
document.body.appendChild(backToTopBtn);

// Afficher/Cacher le bouton
window.addEventListener('scroll', function() {
    if (window.scrollY > 500) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

// Action de retour en haut
backToTopBtn.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Ajouter le style du bouton dynamiquement
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

console.log('🚀 ISTAM Paiement - Système initialisé avec succès');
console.log('📱 Prêt pour les paiements mobiles');