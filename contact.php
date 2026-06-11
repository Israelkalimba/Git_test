<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contactez l'équipe ISTAM Paiement - Support, assistance et informations">
    <title>Contact - ISTAM Paiement</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/contact.css">
</head>
<body>
    <!-- Navbar -->
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
                        <a class="nav-link" href="a-propos.php">À propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact</a>
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
    <section class="contact-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="contact-hero-title">Contactez-nous</h1>
                    <p class="contact-hero-subtitle">Notre équipe est à votre écoute pour toute question ou assistance</p>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                            <li class="breadcrumb-item active">Contact</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left">
                    <img src="assets/images/contact-illustration.svg" alt="Contact ISTAM" class="contact-hero-image floating">
                </div>
            </div>
        </div>
    </section>

    <!-- Main Contact Section -->
    <section class="contact-main py-5">
        <div class="container">
            <div class="row">
                <!-- Informations de contact -->
                <div class="col-lg-5 mb-4" data-aos="fade-right">
                    <div class="contact-info-wrapper">
                        <h2 class="contact-info-title">Nos Coordonnées</h2>
                        <p class="contact-info-subtitle">Plusieurs moyens de nous joindre selon vos besoins</p>
                        
                        <!-- Carte Adresse -->
                        <div class="contact-info-card">
                            <div class="contact-info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Adresse Principale</h4>
                                <p>123 Avenue de l'Université<br>Commune de la Gombe<br>Kinshasa, République Démocratique du Congo</p>
                            </div>
                        </div>

                        <!-- Carte Téléphone -->
                        <div class="contact-info-card">
                            <div class="contact-info-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Téléphone</h4>
                                <p>
                                    <strong>Standard :</strong> +243 123 456 789<br>
                                    <strong>Support technique :</strong> +243 987 654 321<br>
                                    <strong>WhatsApp :</strong> +243 555 123 456
                                </p>
                            </div>
                        </div>

                        <!-- Carte Email -->
                        <div class="contact-info-card">
                            <div class="contact-info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Emails</h4>
                                <p>
                                    <strong>Support :</strong> support@istam.ac.cd<br>
                                    <strong>Paiement :</strong> paiement@istam.ac.cd<br>
                                    <strong>Administration :</strong> admin@istam.ac.cd
                                </p>
                            </div>
                        </div>

                        <!-- Carte Horaires -->
                        <div class="contact-info-card">
                            <div class="contact-info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Horaires d'ouverture</h4>
                                <p>
                                    <strong>Lundi - Vendredi :</strong> 08h00 - 17h00<br>
                                    <strong>Samedi :</strong> 09h00 - 13h00<br>
                                    <strong>Dimanche :</strong> Fermé
                                </p>
                                <div class="status-badge open">
                                    <span class="status-dot"></span> Ouvert actuellement
                                </div>
                            </div>
                        </div>

                        <!-- Réseaux Sociaux -->
                        <div class="social-contact">
                            <h4>Suivez-nous</h4>
                            <div class="social-contact-links">
                                <a href="#" class="social-contact-link" title="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-contact-link" title="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-contact-link" title="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-contact-link" title="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-contact-link" title="YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire et Carte -->
                <div class="col-lg-7" data-aos="fade-left">
                    <!-- Formulaire de contact (démonstratif uniquement) -->
                    <div class="contact-form-wrapper">
                        <h2 class="form-title">Envoyez-nous un message</h2>
                        <p class="form-subtitle">Notre équipe vous répondra dans les plus brefs délais</p>
                        
                        <form id="contactForm" class="contact-form" onsubmit="handleFormSubmit(event)">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="fullName" placeholder="Votre nom complet" required>
                                        <label for="fullName">
                                            <i class="fas fa-user"></i> Nom complet
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" placeholder="Votre email" required>
                                        <label for="email">
                                            <i class="fas fa-envelope"></i> Adresse email
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" placeholder="Votre téléphone">
                                        <label for="phone">
                                            <i class="fas fa-phone"></i> Téléphone
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="subject" required>
                                            <option value="" selected disabled>Choisir un sujet</option>
                                            <option value="support">Support technique</option>
                                            <option value="paiement">Problème de paiement</option>
                                            <option value="compte">Problème de compte</option>
                                            <option value="information">Demande d'information</option>
                                            <option value="reclamation">Réclamation</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                        <label for="subject">
                                            <i class="fas fa-tag"></i> Sujet
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-floating">
                                    <textarea class="form-control" id="message" style="height: 150px" placeholder="Votre message" required></textarea>
                                    <label for="message">
                                        <i class="fas fa-comment"></i> Votre message
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="privacy" required>
                                    <label class="form-check-label" for="privacy">
                                        J'accepte que mes données soient utilisées pour traiter ma demande
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn-submit">
                                <span class="btn-text">Envoyer le message</span>
                                <span class="btn-icon"><i class="fas fa-paper-plane"></i></span>
                            </button>
                        </form>

                        <!-- Message de succès (caché par défaut) -->
                        <div id="successMessage" class="success-message" style="display: none;">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Message envoyé avec succès !</h3>
                            <p>Notre équipe vous contactera dans les plus brefs délais. Pour toute urgence, appelez-nous au <strong>+243 987 654 321</strong>.</p>
                            <button class="btn-reset" onclick="resetForm()">
                                <i class="fas fa-redo"></i> Envoyer un autre message
                            </button>
                        </div>
                    </div>

                    <!-- Carte Google Maps (démonstrative) -->
                    <div class="map-wrapper mt-4">
                        <h3 class="map-title">
                            <i class="fas fa-location-dot"></i> Nous trouver
                        </h3>
                        <div class="map-container">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3978.435123456789!2d15.312345678901234!3d-4.312345678901234!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNMKwMTgnNDQuNCJTIDE1wrAxOCc0NC40IkU!5e0!3m2!1sfr!2scd!4v1234567890123!5m2!1sfr!2scd"
                                width="100%" 
                                height="350" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                        <div class="map-actions">
                            <a href="https://maps.google.com/?q=ISTAM+Kinshasa" target="_blank" class="btn-map-action">
                                <i class="fas fa-directions"></i> Itinéraire
                            </a>
                            <a href="https://maps.google.com/?q=ISTAM+Kinshasa" target="_blank" class="btn-map-action">
                                <i class="fas fa-external-link-alt"></i> Voir en grand
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Support -->
    <section class="support-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <span class="section-badge">Support</span>
                <h2 class="section-title">Assistance rapide</h2>
                <p class="section-subtitle">Trouvez les réponses à vos questions les plus courantes</p>
            </div>
            <div class="row">
                <!-- Carte FAQ rapide -->
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="support-card">
                        <div class="support-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h3>FAQ</h3>
                        <p>Consultez notre foire aux questions pour trouver rapidement des réponses.</p>
                        <a href="index.php#faq" class="support-link">
                            Voir la FAQ <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Carte Guide -->
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="support-card">
                        <div class="support-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Guide d'utilisation</h3>
                        <p>Apprenez à utiliser la plateforme étape par étape avec notre guide complet.</p>
                        <a href="#" class="support-link">
                            Télécharger le guide <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>

                <!-- Carte Support Prioritaire -->
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="support-card emergency">
                        <div class="support-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Urgence</h3>
                        <p>En cas de problème urgent avec un paiement, contactez-nous immédiatement.</p>
                        <a href="tel:+243987654321" class="support-link-emergency">
                            <i class="fas fa-phone"></i> Appeler le support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5">
        <div class="container text-center" data-aos="zoom-in">
            <h2>Besoin d'aide immédiate ?</h2>
            <p class="mb-4">Notre équipe support est disponible pour vous assister</p>
            <div class="cta-buttons">
                <a href="tel:+243987654321" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-phone-alt"></i> Appeler maintenant
                </a>
                <a href="mailto:support@istam.ac.cd" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-envelope"></i> Envoyer un email
                </a>
            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="assets/js/contact.js"></script>
</body>
</html>