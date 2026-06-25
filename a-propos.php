<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="À propos du système de paiement ISTAM - Découvrez notre mission, notre équipe et notre technologie">
    <title>À Propos - ISTAM Paiement</title>

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/about.css">
</head>

<body>
    <!-- Navbar (identique à index.php) -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo-istam.png" alt="ISTAM Logo" height="45">
                <span class="brand-text">ISTAM Paiement</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#comment-ca-marche">Comment ça marche</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="a-propos.php">À propos</a>
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

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <div class="row align-items-center min-vh-50">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="about-hero-title">À Propos d'ISTAM Paiement</h1>
                    <p class="about-hero-subtitle">Découvrez l'histoire et la technologie derrière notre solution de paiement académique</p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">À propos</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left">
                    <img src="assets/images/about-illustration.svg" alt="À propos ISTAM" class="about-hero-image floating">
                </div>
            </div>
        </div>
    </section>

    <!-- Notre Mission Section -->
    <section class="mission-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4" data-aos="fade-right">
                    <div class="mission-image-wrapper">
                        <img src="assets/images/mission.jpg" alt="Notre Mission" class="mission-image">
                        <div class="mission-experience">
                            <span class="experience-years">5+</span>
                            <span class="experience-text">Ans d'expérience</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="mission-content">
                        <span class="section-badge">Notre Mission</span>
                        <h2 class="section-title">Révolutionner le paiement des frais académiques</h2>
                        <p class="mission-text">
                            ISTAM Paiement est né de la vision de simplifier et de moderniser le processus de paiement des frais académiques. Notre mission est de fournir une plateforme sécurisée, accessible et intuitive qui permet aux étudiants de se concentrer sur l'essentiel : leurs études.
                        </p>
                        <p class="mission-text">
                            Nous croyons que la technologie doit servir l'éducation. C'est pourquoi nous avons développé une solution qui élimine les barrières administratives et permet un paiement rapide, traçable et sans stress.
                        </p>
                        <div class="mission-stats">
                            <div class="row">
                                <div class="col-4">
                                    <div class="mini-stat">
                                        <i class="fas fa-users"></i>
                                        <h4>15K+</h4>
                                        <p>Utilisateurs</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mini-stat">
                                        <i class="fas fa-shield-alt"></i>
                                        <h4>100%</h4>
                                        <p>Sécurisé</p>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="mini-stat">
                                        <i class="fas fa-clock"></i>
                                        <h4>24/7</h4>
                                        <p>Disponible</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Notre Histoire Timeline -->
    <section class="history-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <span class="section-badge">Notre Histoire</span>
                <h2 class="section-title">L'évolution d'ISTAM Paiement</h2>
                <p class="section-subtitle">De l'idée à la solution leader de paiement académique</p>
            </div>
            <div class="timeline">
                <div class="timeline-item" data-aos="fade-up">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">2020</div>
                        <h3>Conception du Projet</h3>
                        <p>Identification du besoin de digitalisation des paiements académiques. Premières études de faisabilité et conception de l'architecture système.</p>
                        <div class="timeline-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                    </div>
                </div>
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">2021</div>
                        <h3>Développement Initial</h3>
                        <p>Mise en place de la première version avec intégration Orange Money. Tests pilotes avec 500 étudiants.</p>
                        <div class="timeline-icon">
                            <i class="fas fa-code"></i>
                        </div>
                    </div>
                </div>
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">2022</div>
                        <h3>Expansion Multi-opérateurs</h3>
                        <p>Ajout d'Airtel Money et M-Pesa. Lancement des bordereaux PDF automatiques et du système de notification.</p>
                        <div class="timeline-icon">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">2023</div>
                        <h3>Optimisation & Sécurité</h3>
                        <p>Renforcement de la sécurité avec chiffrement avancé. Mise en place SPA pour une expérience utilisateur fluide.</p>
                        <div class="timeline-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                </div>
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-date">2024</div>
                        <h3>Version Actuelle</h3>
                        <p>Interface modernisée, nouveaux tableaux de bord, analytics avancés et intelligence artificielle pour la détection de fraude.</p>
                        <div class="timeline-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Nos Valeurs -->
    <section class="values-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <span class="section-badge">Nos Valeurs</span>
                <h2 class="section-title">Ce qui nous guide</h2>
                <p class="section-subtitle">Les principes fondamentaux qui définissent notre approche</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="value-card">
                        <div class="value-icon-wrapper">
                            <div class="value-icon">
                                <i class="fas fa-shield-haltered"></i>
                            </div>
                        </div>
                        <h3>Sécurité</h3>
                        <p>Protection maximale des données et des transactions avec chiffrement de bout en bout.</p>
                        <ul class="value-list">
                            <li><i class="fas fa-check"></i> Chiffrement SSL/TLS</li>
                            <li><i class="fas fa-check"></i> Authentification forte</li>
                            <li><i class="fas fa-check"></i> Anti-fraude IA</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="value-card">
                        <div class="value-icon-wrapper">
                            <div class="value-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                        </div>
                        <h3>Rapidité</h3>
                        <p>Transactions instantanées et interface optimisée pour un paiement en moins de 2 minutes.</p>
                        <ul class="value-list">
                            <li><i class="fas fa-check"></i> Paiement en 1 clic</li>
                            <li><i class="fas fa-check"></i> Confirmation immédiate</li>
                            <li><i class="fas fa-check"></i> SPA sans rechargement</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="value-card">
                        <div class="value-icon-wrapper">
                            <div class="value-icon">
                                <i class="fas fa-universal-access"></i>
                            </div>
                        </div>
                        <h3>Accessibilité</h3>
                        <p>Plateforme disponible 24/7 depuis n'importe quel appareil connecté à Internet.</p>
                        <ul class="value-list">
                            <li><i class="fas fa-check"></i> Responsive design</li>
                            <li><i class="fas fa-check"></i> Mobile first</li>
                            <li><i class="fas fa-check"></i> Multi-navigateurs</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="400">
                    <div class="value-card">
                        <div class="value-icon-wrapper">
                            <div class="value-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                        </div>
                        <h3>Transparence</h3>
                        <p>Traçabilité complète des transactions et historique détaillé pour chaque étudiant.</p>
                        <ul class="value-list">
                            <li><i class="fas fa-check"></i> Historique complet</li>
                            <li><i class="fas fa-check"></i> Bordereaux PDF</li>
                            <li><i class="fas fa-check"></i> Notifications email</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technologie Section -->
    <section class="tech-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <span class="section-badge">Technologie</span>
                <h2 class="section-title">Construit avec les meilleures technologies</h2>
                <p class="section-subtitle">Une stack technique moderne pour des performances optimales</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="100">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fab fa-php"></i>
                        </div>
                        <h4>PHP 8.2</h4>
                        <p>Backend robuste et sécurisé</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 95%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="200">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <h4>MySQL 8.0</h4>
                        <p>Base de données relationnelle</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 90%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="300">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fab fa-js"></i>
                        </div>
                        <h4>JavaScript ES6</h4>
                        <p>Frontend dynamique SPA</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 85%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="400">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fab fa-bootstrap"></i>
                        </div>
                        <h4>Bootstrap 5</h4>
                        <p>Design responsive moderne</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 88%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="500">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>API Mobile Money</h4>
                        <p>Orange, Airtel, Vodacom</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 92%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="600">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <h4>TCPDF</h4>
                        <p>Génération PDF avancée</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 87%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="700">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>PHPMailer</h4>
                        <p>Notifications email sécurisées</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 83%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="flip-left" data-aos-delay="800">
                    <div class="tech-card">
                        <div class="tech-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4>Chart.js</h4>
                        <p>Visualisation de données</p>
                        <div class="tech-progress">
                            <div class="progress-bar" style="width: 86%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Équipe Section -->
    <section class="team-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <span class="section-badge">Notre Équipe</span>
                <h2 class="section-title">L'équipe derrière le projet</h2>
                <p class="section-subtitle">Des passionnés de technologie au service de l'éducation</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="team-card">
                        <div class="team-image-wrapper">
                            <img src="assets/images/team1.jpg" alt="Développeur Principal" class="team-image">
                            <div class="team-social">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h4>Jean-Marie K.</h4>
                            <p class="team-role">Développeur Full Stack</p>
                            <p class="team-bio">Expert en PHP et systèmes de paiement mobile avec 8 ans d'expérience.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="team-card">
                        <div class="team-image-wrapper">
                            <img src="assets/images/team2.jpg" alt="Designer UI/UX" class="team-image">
                            <div class="team-social">
                                <a href="#"><i class="fab fa-dribbble"></i></a>
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#"><i class="fab fa-behance"></i></a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h4>Sarah M.</h4>
                            <p class="team-role">Designer UI/UX</p>
                            <p class="team-bio">Créative passionnée par les interfaces intuitives et l'expérience utilisateur.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="team-card">
                        <div class="team-image-wrapper">
                            <img src="assets/images/team3.jpg" alt="Chef de Projet" class="team-image">
                            <div class="team-social">
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-medium"></i></a>
                            </div>
                        </div>
                        <div class="team-info">
                            <h4>Patrick L.</h4>
                            <p class="team-role">Chef de Projet IT</p>
                            <p class="team-bio">Gestionnaire de projets technologiques avec une vision stratégique.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5">
        <div class="container text-center" data-aos="zoom-in">
            <h2>Prêt à découvrir notre solution ?</h2>
            <p class="mb-4">Rejoignez les milliers d'étudiants qui nous font confiance</p>
            <a href="auth/login_etudiant.php" class="btn btn-light btn-lg me-3">
                <i class="fas fa-user-graduate"></i> Accéder à mon espace
            </a>
            <a href="contact.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-envelope"></i> Nous contacter
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4" data-aos="fade-up">
                    <img src="assets/images/logo-istam-white.png" alt="ISTAM" height="50" class="mb-3">
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
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="index.php#services">Services</a></li>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js" defer></script>
    <script src="assets/js/performance.js" defer></script>
    <script src="assets/js/about.js" defer></script>
</body>

</html>