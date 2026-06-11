<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ISTAM - Paiement des frais académiques en ligne. Solution sécurisée de paiement par mobile money pour les étudiants.">
    <title>ISTAM Paiement - Portail Officiel</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo_istam.jpeg" alt="ISTAM Logo" height="45">
                <span class="brand-text">ISTAM Paiement</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#accueil">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#comment-ca-marche">Comment ça marche</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#statistiques">Statistiques</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="a-propos.php">À propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link btn-connexion dropdown-toggle" href="#" id="connexionDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="auth/login_etudiant.php"><i class="fas fa-user-graduate"></i> Espace Étudiant</a></li>
                            <li><a class="dropdown-item" href="auth/login_secretaire.php"><i class="fas fa-user-tie"></i> Espace Secrétaire</a></li>
                            <li><a class="dropdown-item" href="auth/login_admin.php"><i class="fas fa-user-shield"></i> Espace Administrateur</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Carousel Section -->
    <section id="accueil" class="hero-carousel">
        <div id="mainCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="3"></button>
                <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="4"></button>
            </div>
            <div class="carousel-inner">
                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <div class="carousel-bg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="overlay-pattern"></div>
                    </div>
                    <div class="container">
                        <div class="carousel-content">
                            <div class="row align-items-center">
                                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                                    <h1 class="hero-title">Payez vos frais académiques en toute simplicité</h1>
                                    <p class="hero-subtitle">Solution digitale sécurisée pour le paiement des frais universitaires via Mobile Money</p>
                                    <div class="hero-buttons">
                                        <a href="auth/login_etudiant.php" class="btn btn-primary btn-lg me-3">
                                            <i class="fas fa-credit-card"></i> Payer maintenant
                                        </a>
                                        <a href="#comment-ca-marche" class="btn btn-outline-light btn-lg">
                                            <i class="fas fa-play-circle"></i> Comment ça marche
                                        </a>
                                    </div>
                                    <div class="hero-stats mt-4">
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="stat-item">
                                                    <h3>15K+</h3>
                                                    <p>Étudiants</p>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stat-item">
                                                    <h3>50K+</h3>
                                                    <p>Transactions</p>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stat-item">
                                                    <h3>99.9%</h3>
                                                    <p>Sécurité</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 text-center" data-aos="fade-left" data-aos-duration="1000">
                                    <img src="assets/images/logo_istam.jpeg" alt="Paiement mobile" class="hero-image floating">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="carousel-item">
                    <div class="carousel-bg" style="background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);">
                        <div class="overlay-pattern"></div>
                    </div>
                    <div class="container">
                        <div class="carousel-content">
                            <div class="row align-items-center">
                                <div class="col-lg-6" data-aos="fade-up">
                                    <h1 class="hero-title">Paiement Mobile Money instantané</h1>
                                    <p class="hero-subtitle">Compatibilité avec Orange Money, Airtel Money et Vodacom M-Pesa</p>
                                    <div class="mobile-operators mt-4">
                                        <span class="operator-badge"><i class="fas fa-wifi"></i> Orange Money</span>
                                        <span class="operator-badge"><i class="fas fa-signal"></i> Airtel Money</span>
                                        <span class="operator-badge"><i class="fas fa-mobile-alt"></i> M-Pesa</span>
                                    </div>
                                </div>
                                <div class="col-lg-6 text-center" data-aos="fade-up" data-aos-delay="200">
                                    <img src="assets/images/payment.jpg" alt="Mobile Money" class="hero-image">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="carousel-item">
                    <div class="carousel-bg" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
                        <div class="overlay-pattern"></div>
                    </div>
                    <div class="container">
                        <div class="carousel-content">
                            <div class="row align-items-center">
                                <div class="col-lg-6" data-aos="zoom-in">
                                    <h1 class="hero-title">Bordereaux PDF automatiques</h1>
                                    <p class="hero-subtitle">Recevez instantanément votre reçu de paiement par email et téléchargez-le depuis votre espace</p>
                                </div>
                                <div class="col-lg-6 text-center" data-aos="zoom-in" data-aos-delay="200">
                                    <img src="assets/images/rse.gif" alt="Bordereau PDF" class="hero-image">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 4 -->
                <div class="carousel-item">
                    <div class="carousel-bg" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                        <div class="overlay-pattern"></div>
                    </div>
                    <div class="container">
                        <div class="carousel-content text-center">
                            <div class="row justify-content-center">
                                <div class="col-lg-8" data-aos="flip-left">
                                    <h1 class="hero-title">Plus besoin de faire la file !</h1>
                                    <p class="hero-subtitle">Payez depuis chez vous, 24h/24 et 7j/7. Évitez les longues files d'attente à la banque.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slide 5 -->
                <div class="carousel-item">
                    <div class="carousel-bg" style="background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);">
                        <div class="overlay-pattern"></div>
                    </div>
                    <div class="container">
                        <div class="carousel-content">
                            <div class="row align-items-center">
                                <div class="col-lg-6" data-aos="slide-right">
                                    <h1 class="hero-title">Suivi en temps réel</h1>
                                    <p class="hero-subtitle">Historique complet de vos transactions, notifications instantanées et traçabilité garantie</p>
                                </div>
                                <div class="col-lg-6 text-center" data-aos="slide-left">
                                    <img src="assets/images/df.jpg" alt="Suivi temps réel" class="hero-image">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Nos Services</h2>
                <p class="section-subtitle">Une plateforme complète pour gérer vos paiements académiques</p>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Paiement Mobile</h3>
                        <p>Payez directement depuis votre téléphone via Orange Money, Airtel Money ou M-Pesa. Simple, rapide et sécurisé.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check-circle"></i> Transaction instantanée</li>
                            <li><i class="fas fa-check-circle"></i> Confirmation en temps réel</li>
                            <li><i class="fas fa-check-circle"></i> Code secret protégé</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-card featured">
                        <div class="service-badge">Populaire</div>
                        <div class="service-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h3>Bordereaux Automatiques</h3>
                        <p>Recevez vos reçus de paiement en PDF immédiatement après chaque transaction réussie.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check-circle"></i> Bordereau PDF officiel</li>
                            <li><i class="fas fa-check-circle"></i> Envoi par email</li>
                            <li><i class="fas fa-check-circle"></i> Téléchargement illimité</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3>Historique Complet</h3>
                        <p>Consultez l'historique de tous vos paiements, téléchargez vos anciens reçus et suivez votre progression.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check-circle"></i> Traçabilité totale</li>
                            <li><i class="fas fa-check-circle"></i> Filtres par date</li>
                            <li><i class="fas fa-check-circle"></i> Export des données</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section id="comment-ca-marche" class="how-it-works py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Comment ça marche ?</h2>
                <p class="section-subtitle">4 étapes simples pour payer vos frais académiques</p>
            </div>
            <div class="steps-timeline">
                <div class="step-item" data-aos="fade-right" data-aos-delay="100">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Connectez-vous</h3>
                        <p>Accédez à votre espace étudiant avec votre matricule et mot de passe. Votre profil et vos frais s'affichent automatiquement.</p>
                        <div class="step-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="step-item" data-aos="fade-left" data-aos-delay="200">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Sélectionnez le frais</h3>
                        <p>Choisissez le type de frais à payer (tranche, examen, défense...). Le montant est automatiquement défini par le système.</p>
                        <div class="step-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                </div>
                <div class="step-item" data-aos="fade-right" data-aos-delay="300">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Confirmez le paiement</h3>
                        <p>Validez via votre téléphone en saisissant votre code secret Mobile Money. Transaction 100% sécurisée.</p>
                        <div class="step-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="step-item" data-aos="fade-left" data-aos-delay="400">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Recevez votre reçu</h3>
                        <p>Téléchargez instantanément votre bordereau PDF et recevez une notification par email. C'est fait !</p>
                        <div class="step-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section id="statistiques" class="stats-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <h2 class="section-title text-white">ISTAM en chiffres</h2>
                <p class="section-subtitle text-white-50">La confiance de milliers d'étudiants</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-value" data-count="15234">0</div>
                        <div class="stat-label">Étudiants actifs</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-transaction"></i>
                        </div>
                        <div class="stat-value" data-count="48750">0</div>
                        <div class="stat-label">Transactions réussies</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-value" data-count="12">0</div>
                        <div class="stat-label">Facultés</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="400">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value" data-count="999">0</div>
                        <div class="stat-label">% de disponibilité</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Ce que disent nos étudiants</h2>
                <p class="section-subtitle">Témoignages de la communauté ISTAM</p>
            </div>
            <div class="row">
                <div class="col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Fini les longues files d'attente à la banque ! Je paye mes frais directement depuis ma chambre. C'est rapide et sécurisé."</p>
                        <div class="testimonial-author">
                            <img src="assets/images/PSX_20220324_160123.jpg" alt="Étudiant">
                            <div>
                                <h5>Marie K.</h5>
                                <small>Licence 3 Informatique</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Les bordereaux PDF sont très pratiques. Je peux les télécharger à tout moment pour mes dossiers administratifs."</p>
                        <div class="testimonial-author">
                            <img src="assets/images/paiement-en-ligne.jpg" alt="Étudiant">
                            <div>
                                <h5>Jean-Pierre M.</h5>
                                <small>Master 2 Droit</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="testimonial-text">"Système très intuitif. Même sans être un expert en technologie, j'ai pu payer mes frais en moins de 2 minutes."</p>
                        <div class="testimonial-author">
                            <img src="assets/images/rse.gif" alt="Étudiant">
                            <div>
                                <h5>Alice B.</h5>
                                <small>Licence 1 Économie</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Questions fréquentes</h2>
                <p class="section-subtitle">Tout ce que vous devez savoir sur le paiement en ligne</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="100">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="fas fa-question-circle me-2"></i> Quels sont les moyens de paiement acceptés ?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Nous acceptons les paiements via <strong>Orange Money</strong>, <strong>Airtel Money</strong> et <strong>Vodacom M-Pesa</strong>. Les cartes bancaires seront bientôt disponibles.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="200">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-question-circle me-2"></i> Le paiement est-il sécurisé ?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolument ! Toutes les transactions sont chiffrées avec un protocole SSL/TLS. Votre code secret n'est jamais stocké sur nos serveurs et les paiements sont validés directement par votre opérateur mobile.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="300">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-question-circle me-2"></i> Puis-je payer en plusieurs fois ?
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Oui ! Le système est configuré avec des tranches de paiement. Vous pouvez payer votre minerval en plusieurs fois selon le calendrier académique défini par l'administration.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="400">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class="fas fa-question-circle me-2"></i> Que faire si le paiement échoue ?
                                </button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    En cas d'échec, votre compte ne sera pas débité. Vous pouvez réessayer immédiatement. Si le problème persiste, contactez le secrétariat via la page de contact pour une assistance rapide.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section py-5">
        <div class="container text-center" data-aos="zoom-in">
            <h2>Prêt à payer vos frais académiques ?</h2>
            <p class="mb-4">Rejoignez les milliers d'étudiants qui utilisent déjà notre plateforme</p>
            <a href="auth/login_etudiant.php" class="btn btn-light btn-lg me-3">
                <i class="fas fa-user-graduate"></i> Accéder à mon espace
            </a>
            <a href="a-propos.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-info-circle"></i> En savoir plus
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4" data-aos="fade-up">
                    <img src="assets/images/logo_istam.jpeg" alt="ISTAM" height="50" class="mb-3">
                    <p class="footer-description">
                        Institut Supérieur des Techniques Appliquées et de Management. La référence en matière d'enseignement supérieur.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <h5>Liens rapides</h5>
                    <ul class="footer-links">
                        <li><a href="#accueil">Accueil</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="a-propos.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <h5>Espaces</h5>
                    <ul class="footer-links">
                        <li><a href="auth/login_etudiant.php">Espace Étudiant</a></li>
                        <li><a href="auth/login_secretaire.php">Espace Secrétaire</a></li>
                        <li><a href="auth/login_admin.php">Espace Admin</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <h5>Contact</h5>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Avenue de l'Université, Kinshasa</li>
                        <li><i class="fas fa-phone"></i> +243 123 456 789</li>
                        <li><i class="fas fa-envelope"></i> paiement@istam.ac.cd</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom text-center">
                <p>&copy; 2024 ISTAM - Tous droits réservés. Système de Paiement Académique v1.0</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="assets/js/index.js"></script>
</body>
</html>