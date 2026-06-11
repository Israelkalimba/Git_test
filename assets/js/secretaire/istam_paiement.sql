-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 10 mai 2026 à 00:05
-- Version du serveur : 5.7.36
-- Version de PHP : 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `istam_paiement`
--

-- --------------------------------------------------------

--
-- Structure de la table `api_paiement`
--

DROP TABLE IF EXISTS `api_paiement`;
CREATE TABLE IF NOT EXISTS `api_paiement` (
  `id_api` int(11) NOT NULL AUTO_INCREMENT,
  `nom_api` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_api`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `api_paiement`
--

INSERT INTO `api_paiement` (`id_api`, `nom_api`, `api_key`, `endpoint`) VALUES
(1, 'PayLedger - Mobile Money', 'pl_htSEOb8G7VojrKRHKNHEcQySHqHKYxzldZkLsBU3', 'https://pay-ledger.b-manage.net/api/v1/gateway/initiate/mobile');

-- --------------------------------------------------------

--
-- Structure de la table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) DEFAULT NULL,
  `type_action` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_date` (`date_action`),
  KEY `idx_type` (`type_action`),
  KEY `idx_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `audit_log`
--

INSERT INTO `audit_log` (`id_log`, `id_utilisateur`, `type_action`, `action`, `description`, `adresse_ip`, `user_agent`, `date_action`) VALUES
(1, 1, 'system', 'initialisation', 'Création du journal d\'audit', '::1', NULL, '2026-05-08 16:20:39'),
(2, 1, 'modification', 'mise_a_jour_profil', 'Profil administrateur mis à jour - Nom: Administrateur ISTAME, Email: admin@istam.cd', '::1', NULL, '2026-05-08 18:32:47'),
(3, 1, 'modification', 'mise_a_jour_profil', 'Profil administrateur mis à jour - Nom: Administrateur ISTAM, Email: admin@istam.cd', '::1', NULL, '2026-05-08 18:32:52'),
(4, 1, 'deconnexion', 'logout_confirme', 'Déconnexion sécurisée de l\'utilisateur Administrateur ISTAM', '::1', NULL, '2026-05-08 18:56:59'),
(5, 2, 'connexion', 'login_etudiant', 'Connexion étudiant - Matricule: 2025147986, Nom: Raphael Tshomba', '::1', NULL, '2026-05-09 06:39:54'),
(6, 1, 'deconnexion', 'logout_confirme', 'Déconnexion sécurisée de l\'utilisateur Administrateur ISTAM', '::1', NULL, '2026-05-09 08:06:10'),
(7, 2, 'connexion', 'login_etudiant', 'Connexion étudiant - Matricule: 2025147986, Nom: Raphael Tshomba', '::1', NULL, '2026-05-09 14:59:58'),
(8, 4, 'validation', 'validation_manuelle', 'Validation manuelle par KISIMBIKA GLOIRE - Paiement #1 (Raphael Tshomba - 2025147986 - $50.00) - Justificatif: PL', '::1', NULL, '2026-05-09 16:50:24'),
(9, 5, 'connexion', 'login_etudiant', 'Connexion étudiant - Matricule: 2026045789, Nom: LARRISSA MBAYO MWEPU', '::1', NULL, '2026-05-09 17:20:29'),
(10, 5, 'securite', 'logout_echoue', 'Tentative de déconnexion avec mauvais mot de passe', '::1', NULL, '2026-05-09 18:56:49'),
(11, 5, 'connexion', 'login_etudiant', 'Connexion étudiant - Matricule: 2026045789, Nom: ISRAEL KALIMBA MOISE', '::1', NULL, '2026-05-09 18:59:37');

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;
CREATE TABLE IF NOT EXISTS `etudiants` (
  `id_etudiant` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `matricule` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_filiere` int(11) NOT NULL,
  `id_promotion` int(11) NOT NULL,
  PRIMARY KEY (`id_etudiant`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_filiere` (`id_filiere`),
  KEY `idx_etud_mat` (`matricule`),
  KEY `fk_etudiant_promotion` (`id_promotion`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO `etudiants` (`id_etudiant`, `id_utilisateur`, `matricule`, `telephone`, `id_filiere`, `id_promotion`) VALUES
(1, 2, '2025147986', '+243994239992', 1, 2),
(2, 3, '2025147987', '0823456780', 20, 3),
(3, 5, '2026045789', '+243 973366308', 4, 2);

-- --------------------------------------------------------

--
-- Structure de la table `facultes`
--

DROP TABLE IF EXISTS `facultes`;
CREATE TABLE IF NOT EXISTS `facultes` (
  `id_faculte` int(11) NOT NULL AUTO_INCREMENT,
  `nom_faculte` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_faculte`),
  UNIQUE KEY `nom_faculte` (`nom_faculte`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `facultes`
--

INSERT INTO `facultes` (`id_faculte`, `nom_faculte`) VALUES
(3, 'Droit'),
(2, 'Économie & Gestion'),
(4, 'Lettres & Sciences Humaines'),
(1, 'Sciences Informatiques');

-- --------------------------------------------------------

--
-- Structure de la table `filieres`
--

DROP TABLE IF EXISTS `filieres`;
CREATE TABLE IF NOT EXISTS `filieres` (
  `id_filiere` int(11) NOT NULL AUTO_INCREMENT,
  `nom_filiere` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_faculte` int(11) NOT NULL,
  PRIMARY KEY (`id_filiere`),
  KEY `id_faculte` (`id_faculte`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `filieres`
--

INSERT INTO `filieres` (`id_filiere`, `nom_filiere`, `id_faculte`) VALUES
(1, 'Systèmes Informatiques', 1),
(2, 'Réseaux & Télécoms', 1),
(3, 'Génie Logiciel', 1),
(4, 'IAGL (Informatique de Gestion)', 1),
(5, 'Cybersécurité', 1),
(6, 'Intelligence Artificielle', 1),
(7, 'Data Science', 1),
(8, 'Gestion des Entreprises', 2),
(9, 'Économie & Finance', 2),
(10, 'Gestion Financière', 2),
(11, 'Marketing', 2),
(12, 'Gestion des RH (GRH)', 2),
(13, 'Administration Publique', 2),
(14, 'Gestion Hôtelière', 2),
(15, 'Économie du Développement', 2),
(16, 'Tourisme Durable', 2),
(17, 'Événementiel', 2),
(18, 'Droit Privé & Judiciaire', 3),
(19, 'Droit Public', 3),
(20, 'Droit des Affaires', 3),
(21, 'Droit International', 3),
(22, 'Sciences Politiques', 3),
(23, 'Communication & Journalisme', 4),
(24, 'Sociologie', 4),
(25, 'Psychologie du Travail', 4),
(26, 'Sciences de l\'Éducation', 4);

-- --------------------------------------------------------

--
-- Structure de la table `frais`
--

DROP TABLE IF EXISTS `frais`;
CREATE TABLE IF NOT EXISTS `frais` (
  `id_frais` int(11) NOT NULL AUTO_INCREMENT,
  `type_frais` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `devise` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `taux_change` decimal(10,4) DEFAULT '2300.0000',
  `montant_fc` decimal(12,2) DEFAULT NULL,
  `annee_academique` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_limite` date DEFAULT NULL,
  `id_filiere` int(11) DEFAULT NULL,
  `id_promotion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_frais`),
  KEY `id_filiere` (`id_filiere`),
  KEY `fk_frais_promotion` (`id_promotion`)
) ENGINE=InnoDB AUTO_INCREMENT=317 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `frais`
--

INSERT INTO `frais` (`id_frais`, `type_frais`, `montant`, `devise`, `taux_change`, `montant_fc`, `annee_academique`, `date_limite`, `id_filiere`, `id_promotion`) VALUES
(3, 'Minerval - Tranche 1', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 1, 1),
(4, 'Minerval - Tranche 2', '120.00', 'USD', '300.0000', '36000.00', '2026-2027', NULL, 1, 1),
(5, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 1, 1),
(6, 'Examen - 2ème Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 1, 1),
(7, 'Frais d\'Inscription Administrative', '25.00', 'USD', '300.0000', '7500.00', '2026-2027', NULL, 1, 1),
(8, 'Frais de Laboratoire', '30.00', 'USD', '300.0000', '9000.00', '2026-2027', NULL, 1, 1),
(9, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 1, 2),
(10, 'Minerval - Tranche 2', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 1, 2),
(11, 'Minerval - Tranche 3', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 1, 2),
(12, 'Examen - 1ère Session', '1.00', 'USD', '300.0000', '300.00', '2026-2027', NULL, 1, 2),
(13, 'Examen - 2ème Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 1, 2),
(14, 'Frais de Laboratoire', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 1, 2),
(15, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 1, 3),
(16, 'Minerval - Tranche 2', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 1, 3),
(17, 'Minerval - Tranche 3', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 1, 3),
(18, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 1, 3),
(19, 'Examen - 2ème Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 1, 3),
(20, 'Frais de Laboratoire', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 1, 3),
(21, 'Frais de Stage', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 1, 3),
(22, 'Minerval - Tranche 1', '320.00', 'USD', '300.0000', '96000.00', '2026-2027', NULL, 1, 4),
(23, 'Minerval - Tranche 2', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 1, 4),
(24, 'Minerval - Tranche 3', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 1, 4),
(25, 'Examen - 1ère Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 1, 4),
(26, 'Examen - 2ème Session', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 1, 4),
(27, 'Frais de Laboratoire', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 1, 4),
(28, 'Frais de Stage', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 1, 4),
(29, 'Minerval - Tranche 1', '350.00', 'USD', '300.0000', '105000.00', '2026-2027', NULL, 1, 5),
(30, 'Minerval - Tranche 2', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 1, 5),
(31, 'Minerval - Tranche 3', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 1, 5),
(32, 'Examen - 1ère Session', '75.00', 'USD', '300.0000', '22500.00', '2026-2027', NULL, 1, 5),
(33, 'Examen - 2ème Session', '90.00', 'USD', '300.0000', '27000.00', '2026-2027', NULL, 1, 5),
(34, 'Défense de Mémoire', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 1, 5),
(35, 'Frais de Toge', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 1, 5),
(36, 'Frais de Laboratoire', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 1, 5),
(37, 'Minerval - Tranche 1', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 2, 1),
(38, 'Minerval - Tranche 2', '120.00', 'USD', '300.0000', '36000.00', '2026-2027', NULL, 2, 1),
(39, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 2, 1),
(40, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 2, 2),
(41, 'Minerval - Tranche 2', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 2, 2),
(42, 'Minerval - Tranche 3', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 2, 2),
(43, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 2, 2),
(44, 'Frais de Laboratoire', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 2, 2),
(45, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 2, 3),
(46, 'Minerval - Tranche 2', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 2, 3),
(47, 'Minerval - Tranche 3', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 2, 3),
(48, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 2, 3),
(49, 'Frais de Stage', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 2, 3),
(50, 'Minerval - Tranche 1', '320.00', 'USD', '300.0000', '96000.00', '2026-2027', NULL, 2, 4),
(51, 'Minerval - Tranche 2', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 2, 4),
(52, 'Minerval - Tranche 3', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 2, 4),
(53, 'Examen - 1ère Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 2, 4),
(54, 'Frais de Stage', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 2, 4),
(55, 'Minerval - Tranche 1', '350.00', 'USD', '300.0000', '105000.00', '2026-2027', NULL, 2, 5),
(56, 'Minerval - Tranche 2', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 2, 5),
(57, 'Défense de Mémoire', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 2, 5),
(58, 'Frais de Toge', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 2, 5),
(59, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 3, 2),
(60, 'Minerval - Tranche 2', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 3, 2),
(61, 'Minerval - Tranche 3', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 3, 2),
(62, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 3, 2),
(63, 'Frais de Laboratoire', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 3, 2),
(64, 'Minerval - Tranche 1', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 3, 3),
(65, 'Minerval - Tranche 2', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 3, 3),
(66, 'Minerval - Tranche 3', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 3, 3),
(67, 'Examen - 1ère Session', '65.00', 'USD', '300.0000', '19500.00', '2026-2027', NULL, 3, 3),
(68, 'Frais de Stage', '90.00', 'USD', '300.0000', '27000.00', '2026-2027', NULL, 3, 3),
(69, 'Minerval - Tranche 1', '350.00', 'USD', '300.0000', '105000.00', '2026-2027', NULL, 3, 4),
(70, 'Minerval - Tranche 2', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 3, 4),
(71, 'Minerval - Tranche 3', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 3, 4),
(72, 'Examen - 1ère Session', '75.00', 'USD', '300.0000', '22500.00', '2026-2027', NULL, 3, 4),
(73, 'Frais de Stage', '110.00', 'USD', '300.0000', '33000.00', '2026-2027', NULL, 3, 4),
(74, 'Frais de Laboratoire', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 3, 4),
(75, 'Minerval - Tranche 1', '400.00', 'USD', '300.0000', '120000.00', '2026-2027', NULL, 3, 5),
(76, 'Minerval - Tranche 2', '320.00', 'USD', '300.0000', '96000.00', '2026-2027', NULL, 3, 5),
(77, 'Minerval - Tranche 3', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 3, 5),
(78, 'Examen - 1ère Session', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 3, 5),
(79, 'Défense de Mémoire', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 3, 5),
(80, 'Frais de Toge', '120.00', 'USD', '300.0000', '36000.00', '2026-2027', NULL, 3, 5),
(81, 'Frais de Certification', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 3, 5),
(82, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 4, 2),
(83, 'Minerval - Tranche 2', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 4, 2),
(84, 'Examen - 1ère Session', '1.00', 'USD', '300.0000', '300.00', '2026-2027', NULL, 4, 2),
(85, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 4, 3),
(86, 'Minerval - Tranche 2', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 4, 3),
(87, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 4, 3),
(88, 'Minerval - Tranche 1', '320.00', 'USD', '300.0000', '96000.00', '2026-2027', NULL, 4, 4),
(89, 'Minerval - Tranche 2', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 4, 4),
(90, 'Examen - 1ère Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 4, 4),
(91, 'Minerval - Tranche 1', '350.00', 'USD', '300.0000', '105000.00', '2026-2027', NULL, 4, 5),
(92, 'Défense de Mémoire', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 4, 5),
(93, 'Frais de Toge', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 4, 5),
(94, 'Minerval - Tranche 1', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 5, 2),
(95, 'Minerval - Tranche 2', '240.00', 'USD', '300.0000', '72000.00', '2026-2027', NULL, 5, 2),
(96, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 5, 2),
(97, 'Frais de Laboratoire', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 5, 2),
(98, 'Minerval - Tranche 1', '330.00', 'USD', '300.0000', '99000.00', '2026-2027', NULL, 5, 3),
(99, 'Minerval - Tranche 2', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 5, 3),
(100, 'Examen - 1ère Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 5, 3),
(101, 'Frais de Stage', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 5, 3),
(102, 'Minerval - Tranche 1', '380.00', 'USD', '300.0000', '114000.00', '2026-2027', NULL, 5, 4),
(103, 'Minerval - Tranche 2', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 5, 4),
(104, 'Examen - 1ère Session', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 5, 4),
(105, 'Minerval - Tranche 1', '420.00', 'USD', '300.0000', '126000.00', '2026-2027', NULL, 5, 5),
(106, 'Défense de Mémoire', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 5, 5),
(107, 'Frais de Toge', '120.00', 'USD', '300.0000', '36000.00', '2026-2027', NULL, 5, 5),
(108, 'Frais de Certification', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 5, 5),
(109, 'Minerval - Tranche 1', '350.00', 'USD', '300.0000', '105000.00', '2026-2027', NULL, 6, 2),
(110, 'Minerval - Tranche 2', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 6, 2),
(111, 'Examen - 1ère Session', '65.00', 'USD', '300.0000', '19500.00', '2026-2027', NULL, 6, 2),
(112, 'Frais de Laboratoire', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 6, 2),
(113, 'Minerval - Tranche 1', '380.00', 'USD', '300.0000', '114000.00', '2026-2027', NULL, 6, 3),
(114, 'Minerval - Tranche 2', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 6, 3),
(115, 'Examen - 1ère Session', '75.00', 'USD', '300.0000', '22500.00', '2026-2027', NULL, 6, 3),
(116, 'Frais de Stage', '120.00', 'USD', '300.0000', '36000.00', '2026-2027', NULL, 6, 3),
(117, 'Minerval - Tranche 1', '420.00', 'USD', '300.0000', '126000.00', '2026-2027', NULL, 6, 4),
(118, 'Minerval - Tranche 2', '320.00', 'USD', '300.0000', '96000.00', '2026-2027', NULL, 6, 4),
(119, 'Examen - 1ère Session', '85.00', 'USD', '300.0000', '25500.00', '2026-2027', NULL, 6, 4),
(120, 'Minerval - Tranche 1', '480.00', 'USD', '300.0000', '144000.00', '2026-2027', NULL, 6, 5),
(121, 'Défense de Mémoire', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 6, 5),
(122, 'Frais de Toge', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 6, 5),
(123, 'Minerval - Tranche 1', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 7, 2),
(124, 'Minerval - Tranche 2', '240.00', 'USD', '300.0000', '72000.00', '2026-2027', NULL, 7, 2),
(125, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 7, 2),
(126, 'Minerval - Tranche 1', '330.00', 'USD', '300.0000', '99000.00', '2026-2027', NULL, 7, 3),
(127, 'Minerval - Tranche 2', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 7, 3),
(128, 'Examen - 1ère Session', '65.00', 'USD', '300.0000', '19500.00', '2026-2027', NULL, 7, 3),
(129, 'Minerval - Tranche 1', '380.00', 'USD', '300.0000', '114000.00', '2026-2027', NULL, 7, 4),
(130, 'Minerval - Tranche 2', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 7, 4),
(131, 'Examen - 1ère Session', '75.00', 'USD', '300.0000', '22500.00', '2026-2027', NULL, 7, 4),
(132, 'Minerval - Tranche 1', '450.00', 'USD', '300.0000', '135000.00', '2026-2027', NULL, 7, 5),
(133, 'Défense de Mémoire', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 7, 5),
(134, 'Frais de Toge', '130.00', 'USD', '300.0000', '39000.00', '2026-2027', NULL, 7, 5),
(135, 'Minerval - Tranche 1', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 8, 2),
(136, 'Minerval - Tranche 2', '160.00', 'USD', '300.0000', '48000.00', '2026-2027', NULL, 8, 2),
(137, 'Minerval - Tranche 3', '120.00', 'USD', '300.0000', '36000.00', '2026-2027', NULL, 8, 2),
(138, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 8, 2),
(139, 'Minerval - Tranche 1', '230.00', 'USD', '300.0000', '69000.00', '2026-2027', NULL, 8, 3),
(140, 'Minerval - Tranche 2', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 8, 3),
(141, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 8, 3),
(142, 'Frais de Stage', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 8, 3),
(143, 'Minerval - Tranche 1', '270.00', 'USD', '300.0000', '81000.00', '2026-2027', NULL, 8, 4),
(144, 'Minerval - Tranche 2', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 8, 4),
(145, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 8, 4),
(146, 'Frais de Stage', '90.00', 'USD', '300.0000', '27000.00', '2026-2027', NULL, 8, 4),
(147, 'Minerval - Tranche 1', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 8, 5),
(148, 'Défense de Mémoire', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 8, 5),
(149, 'Frais de Toge', '90.00', 'USD', '300.0000', '27000.00', '2026-2027', NULL, 8, 5),
(150, 'Minerval - Tranche 1', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 9, 2),
(151, 'Minerval - Tranche 2', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 9, 2),
(152, 'Examen - 1ère Session', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 9, 2),
(153, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 9, 3),
(154, 'Minerval - Tranche 2', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 9, 3),
(155, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 9, 3),
(156, 'Minerval - Tranche 1', '290.00', 'USD', '300.0000', '87000.00', '2026-2027', NULL, 9, 4),
(157, 'Minerval - Tranche 2', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 9, 4),
(158, 'Examen - 1ère Session', '65.00', 'USD', '300.0000', '19500.00', '2026-2027', NULL, 9, 4),
(159, 'Minerval - Tranche 1', '320.00', 'USD', '300.0000', '96000.00', '2026-2027', NULL, 9, 5),
(160, 'Défense de Mémoire', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 9, 5),
(161, 'Frais de Toge', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 9, 5),
(162, 'Minerval - Tranche 1', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 10, 2),
(163, 'Minerval - Tranche 2', '170.00', 'USD', '300.0000', '51000.00', '2026-2027', NULL, 10, 2),
(164, 'Examen - 1ère Session', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 10, 2),
(165, 'Minerval - Tranche 1', '240.00', 'USD', '300.0000', '72000.00', '2026-2027', NULL, 10, 3),
(166, 'Minerval - Tranche 2', '190.00', 'USD', '300.0000', '57000.00', '2026-2027', NULL, 10, 3),
(167, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 10, 3),
(168, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 10, 4),
(169, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 10, 4),
(170, 'Minerval - Tranche 1', '310.00', 'USD', '300.0000', '93000.00', '2026-2027', NULL, 10, 5),
(171, 'Défense de Mémoire', '190.00', 'USD', '300.0000', '57000.00', '2026-2027', NULL, 10, 5),
(172, 'Minerval - Tranche 1', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 11, 2),
(173, 'Minerval - Tranche 2', '160.00', 'USD', '300.0000', '48000.00', '2026-2027', NULL, 11, 2),
(174, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 11, 2),
(175, 'Minerval - Tranche 1', '230.00', 'USD', '300.0000', '69000.00', '2026-2027', NULL, 11, 3),
(176, 'Minerval - Tranche 2', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 11, 3),
(177, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 11, 3),
(178, 'Frais de Stage', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 11, 3),
(179, 'Minerval - Tranche 1', '270.00', 'USD', '300.0000', '81000.00', '2026-2027', NULL, 11, 4),
(180, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 11, 4),
(181, 'Minerval - Tranche 1', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 11, 5),
(182, 'Défense de Mémoire', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 11, 5),
(183, 'Minerval - Tranche 1', '190.00', 'USD', '300.0000', '57000.00', '2026-2027', NULL, 12, 2),
(184, 'Minerval - Tranche 2', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 12, 2),
(185, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 12, 2),
(186, 'Minerval - Tranche 1', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 12, 3),
(187, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 12, 3),
(188, 'Minerval - Tranche 1', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 12, 4),
(189, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 12, 4),
(190, 'Minerval - Tranche 1', '290.00', 'USD', '300.0000', '87000.00', '2026-2027', NULL, 12, 5),
(191, 'Défense de Mémoire', '170.00', 'USD', '300.0000', '51000.00', '2026-2027', NULL, 12, 5),
(192, 'Minerval - Tranche 1', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 13, 2),
(193, 'Minerval - Tranche 2', '140.00', 'USD', '300.0000', '42000.00', '2026-2027', NULL, 13, 2),
(194, 'Examen - 1ère Session', '35.00', 'USD', '300.0000', '10500.00', '2026-2027', NULL, 13, 2),
(195, 'Minerval - Tranche 1', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 13, 3),
(196, 'Examen - 1ère Session', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 13, 3),
(197, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 13, 4),
(198, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 13, 4),
(199, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 13, 5),
(200, 'Défense de Mémoire', '160.00', 'USD', '300.0000', '48000.00', '2026-2027', NULL, 13, 5),
(201, 'Minerval - Tranche 1', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 14, 2),
(202, 'Minerval - Tranche 2', '140.00', 'USD', '300.0000', '42000.00', '2026-2027', NULL, 14, 2),
(203, 'Examen - 1ère Session', '35.00', 'USD', '300.0000', '10500.00', '2026-2027', NULL, 14, 2),
(204, 'Frais de Stage', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 14, 3),
(205, 'Minerval - Tranche 1', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 14, 3),
(206, 'Examen - 1ère Session', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 14, 3),
(207, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 14, 4),
(208, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 14, 5),
(209, 'Défense de Mémoire', '160.00', 'USD', '300.0000', '48000.00', '2026-2027', NULL, 14, 5),
(210, 'Minerval - Tranche 1', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 15, 2),
(211, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 15, 2),
(212, 'Minerval - Tranche 1', '230.00', 'USD', '300.0000', '69000.00', '2026-2027', NULL, 15, 3),
(213, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 15, 3),
(214, 'Minerval - Tranche 1', '270.00', 'USD', '300.0000', '81000.00', '2026-2027', NULL, 15, 4),
(215, 'Défense de Mémoire', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 15, 5),
(216, 'Minerval - Tranche 1', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 16, 2),
(217, 'Examen - 1ère Session', '35.00', 'USD', '300.0000', '10500.00', '2026-2027', NULL, 16, 2),
(218, 'Frais de Stage', '90.00', 'USD', '300.0000', '27000.00', '2026-2027', NULL, 16, 3),
(219, 'Minerval - Tranche 1', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 16, 3),
(220, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 16, 4),
(221, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 16, 5),
(222, 'Minerval - Tranche 1', '190.00', 'USD', '300.0000', '57000.00', '2026-2027', NULL, 17, 2),
(223, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 17, 2),
(224, 'Minerval - Tranche 1', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 17, 3),
(225, 'Frais de Stage', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 17, 3),
(226, 'Minerval - Tranche 1', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 17, 4),
(227, 'Minerval - Tranche 1', '290.00', 'USD', '300.0000', '87000.00', '2026-2027', NULL, 17, 5),
(228, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 18, 2),
(229, 'Minerval - Tranche 2', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 18, 2),
(230, 'Minerval - Tranche 3', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 18, 2),
(231, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 18, 2),
(232, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 18, 3),
(233, 'Minerval - Tranche 2', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 18, 3),
(234, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 18, 3),
(235, 'Minerval - Tranche 1', '320.00', 'USD', '300.0000', '96000.00', '2026-2027', NULL, 18, 4),
(236, 'Examen - 1ère Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 18, 4),
(237, 'Frais de Stage', '100.00', 'USD', '300.0000', '30000.00', '2026-2027', NULL, 18, 4),
(238, 'Minerval - Tranche 1', '350.00', 'USD', '300.0000', '105000.00', '2026-2027', NULL, 18, 5),
(239, 'Défense de Mémoire', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 18, 5),
(240, 'Frais de Toge', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 18, 5),
(241, 'Minerval - Tranche 1', '240.00', 'USD', '300.0000', '72000.00', '2026-2027', NULL, 19, 2),
(242, 'Minerval - Tranche 2', '190.00', 'USD', '300.0000', '57000.00', '2026-2027', NULL, 19, 2),
(243, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 19, 2),
(244, 'Minerval - Tranche 1', '270.00', 'USD', '300.0000', '81000.00', '2026-2027', NULL, 19, 3),
(245, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 19, 3),
(246, 'Minerval - Tranche 1', '310.00', 'USD', '300.0000', '93000.00', '2026-2027', NULL, 19, 4),
(247, 'Examen - 1ère Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 19, 4),
(248, 'Minerval - Tranche 1', '340.00', 'USD', '300.0000', '102000.00', '2026-2027', NULL, 19, 5),
(249, 'Défense de Mémoire', '240.00', 'USD', '300.0000', '72000.00', '2026-2027', NULL, 19, 5),
(250, 'Minerval - Tranche 1', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 20, 2),
(251, 'Minerval - Tranche 2', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 20, 2),
(252, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 20, 2),
(253, 'Minerval - Tranche 1', '290.00', 'USD', '300.0000', '87000.00', '2026-2027', NULL, 20, 3),
(254, 'Examen - 1ère Session', '65.00', 'USD', '300.0000', '19500.00', '2026-2027', NULL, 20, 3),
(255, 'Minerval - Tranche 1', '330.00', 'USD', '300.0000', '99000.00', '2026-2027', NULL, 20, 4),
(256, 'Examen - 1ère Session', '75.00', 'USD', '300.0000', '22500.00', '2026-2027', NULL, 20, 4),
(257, 'Minerval - Tranche 1', '370.00', 'USD', '300.0000', '111000.00', '2026-2027', NULL, 20, 5),
(258, 'Défense de Mémoire', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 20, 5),
(259, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 21, 2),
(260, 'Minerval - Tranche 2', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 21, 2),
(261, 'Examen - 1ère Session', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 21, 2),
(262, 'Minerval - Tranche 1', '310.00', 'USD', '300.0000', '93000.00', '2026-2027', NULL, 21, 3),
(263, 'Examen - 1ère Session', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 21, 3),
(264, 'Minerval - Tranche 1', '360.00', 'USD', '300.0000', '108000.00', '2026-2027', NULL, 21, 4),
(265, 'Minerval - Tranche 1', '400.00', 'USD', '300.0000', '120000.00', '2026-2027', NULL, 21, 5),
(266, 'Défense de Mémoire', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 21, 5),
(267, 'Minerval - Tranche 1', '230.00', 'USD', '300.0000', '69000.00', '2026-2027', NULL, 22, 2),
(268, 'Minerval - Tranche 2', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 22, 2),
(269, 'Examen - 1ère Session', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 22, 2),
(270, 'Minerval - Tranche 1', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 22, 3),
(271, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 22, 3),
(272, 'Minerval - Tranche 1', '300.00', 'USD', '300.0000', '90000.00', '2026-2027', NULL, 22, 4),
(273, 'Minerval - Tranche 1', '330.00', 'USD', '300.0000', '99000.00', '2026-2027', NULL, 22, 5),
(274, 'Défense de Mémoire', '220.00', 'USD', '300.0000', '66000.00', '2026-2027', NULL, 22, 5),
(275, 'Minerval - Tranche 1', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 23, 2),
(276, 'Minerval - Tranche 2', '140.00', 'USD', '300.0000', '42000.00', '2026-2027', NULL, 23, 2),
(277, 'Examen - 1ère Session', '35.00', 'USD', '300.0000', '10500.00', '2026-2027', NULL, 23, 2),
(278, 'Minerval - Tranche 1', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 23, 3),
(279, 'Examen - 1ère Session', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 23, 3),
(280, 'Frais de Stage', '70.00', 'USD', '300.0000', '21000.00', '2026-2027', NULL, 23, 3),
(281, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 23, 4),
(282, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 23, 4),
(283, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 23, 5),
(284, 'Défense de Mémoire', '160.00', 'USD', '300.0000', '48000.00', '2026-2027', NULL, 23, 5),
(285, 'Frais de Toge', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 23, 5),
(286, 'Minerval - Tranche 1', '170.00', 'USD', '300.0000', '51000.00', '2026-2027', NULL, 24, 2),
(287, 'Minerval - Tranche 2', '130.00', 'USD', '300.0000', '39000.00', '2026-2027', NULL, 24, 2),
(288, 'Examen - 1ère Session', '30.00', 'USD', '300.0000', '9000.00', '2026-2027', NULL, 24, 2),
(289, 'Minerval - Tranche 1', '200.00', 'USD', '300.0000', '60000.00', '2026-2027', NULL, 24, 3),
(290, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 24, 3),
(291, 'Minerval - Tranche 1', '240.00', 'USD', '300.0000', '72000.00', '2026-2027', NULL, 24, 4),
(292, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 24, 4),
(293, 'Minerval - Tranche 1', '270.00', 'USD', '300.0000', '81000.00', '2026-2027', NULL, 24, 5),
(294, 'Défense de Mémoire', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', NULL, 24, 5),
(295, 'Minerval - Tranche 1', '180.00', 'USD', '300.0000', '54000.00', '2026-2027', NULL, 25, 2),
(296, 'Minerval - Tranche 2', '140.00', 'USD', '300.0000', '42000.00', '2026-2027', NULL, 25, 2),
(297, 'Examen - 1ère Session', '35.00', 'USD', '300.0000', '10500.00', '2026-2027', NULL, 25, 2),
(298, 'Minerval - Tranche 1', '210.00', 'USD', '300.0000', '63000.00', '2026-2027', NULL, 25, 3),
(299, 'Examen - 1ère Session', '45.00', 'USD', '300.0000', '13500.00', '2026-2027', NULL, 25, 3),
(300, 'Frais de Stage', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', NULL, 25, 3),
(301, 'Minerval - Tranche 1', '250.00', 'USD', '300.0000', '75000.00', '2026-2027', NULL, 25, 4),
(302, 'Examen - 1ère Session', '55.00', 'USD', '300.0000', '16500.00', '2026-2027', NULL, 25, 4),
(303, 'Minerval - Tranche 1', '280.00', 'USD', '300.0000', '84000.00', '2026-2027', NULL, 25, 5),
(304, 'Défense de Mémoire', '170.00', 'USD', '300.0000', '51000.00', '2026-2027', NULL, 25, 5),
(305, 'Minerval - Tranche 1', '160.00', 'USD', '300.0000', '48000.00', '2026-2027', NULL, 26, 2),
(306, 'Minerval - Tranche 2', '120.00', 'USD', '300.0000', '36000.00', '2026-2027', NULL, 26, 2),
(307, 'Examen - 1ère Session', '30.00', 'USD', '300.0000', '9000.00', '2026-2027', NULL, 26, 2),
(308, 'Minerval - Tranche 1', '190.00', 'USD', '300.0000', '57000.00', '2026-2027', NULL, 26, 3),
(309, 'Examen - 1ère Session', '40.00', 'USD', '300.0000', '12000.00', '2026-2027', NULL, 26, 3),
(310, 'Frais de Stage', '60.00', 'USD', '300.0000', '18000.00', '2026-2027', NULL, 26, 3),
(311, 'Minerval - Tranche 1', '230.00', 'USD', '300.0000', '69000.00', '2026-2027', NULL, 26, 4),
(312, 'Examen - 1ère Session', '50.00', 'USD', '300.0000', '15000.00', '2026-2027', NULL, 26, 4),
(313, 'Minerval - Tranche 1', '260.00', 'USD', '300.0000', '78000.00', '2026-2027', NULL, 26, 5),
(314, 'Défense de Mémoire', '140.00', 'USD', '300.0000', '42000.00', '2026-2027', NULL, 26, 5),
(315, 'Minerval - Tranche 3', '150.00', 'USD', '300.0000', '45000.00', '2026-2027', '2026-07-16', 4, 2),
(316, 'Frais de Bibliothèque', '80.00', 'USD', '300.0000', '24000.00', '2026-2027', '2026-06-10', 1, 2);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id_notification` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'envoyé',
  PRIMARY KEY (`id_notification`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id_notification`, `id_utilisateur`, `message`, `date_envoi`, `statut`) VALUES
(1, 1, 'Nouvel étudiant inscrit : Raphael Tshomba (Matricule: 2025147986)', '2026-05-08 08:59:27', 'lu'),
(2, 1, 'Nouvel étudiant inscrit : jocelyne (Matricule: 2025147987)', '2026-05-08 09:00:44', 'non_lu'),
(3, 1, 'Nouveau Secrétaire ajouté : KISIMBIKA GLOIRE (kisimbikagloire@gmail.com)', '2026-05-08 13:18:51', 'non_lu'),
(4, 2, 'Paiement initié : Examen - 1ère Session - $50.00 (Réf: ISTAM-147986-20260509173652-479). Vous recevrez une demande de confirmation sur votre téléphone.', '2026-05-09 16:36:58', 'lu'),
(5, 1, 'Nouvel étudiant inscrit : LARRISSA MBAYO MWEPU (Matricule: 2026045789)', '2026-05-09 16:45:20', 'non_lu'),
(6, 5, 'Paiement initié : Examen - 1ère Session - $50.00 (Réf: ISTAM-045789-20260509195947-655). Vous recevrez une demande de confirmation sur votre téléphone.', '2026-05-09 18:59:51', 'non_lu'),
(7, 5, 'Paiement initié : Examen - 1ère Session - $1.00 (Réf: ISTAM-045789-20260509200418-968). Vous recevrez une demande de confirmation sur votre téléphone.', '2026-05-09 19:04:21', 'non_lu');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `id_etudiant` int(11) NOT NULL,
  `id_frais` int(11) NOT NULL,
  `montant_paye` decimal(10,2) NOT NULL,
  `date_paiement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_attente','succes','echec') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `reference_transaction` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_paiement`),
  UNIQUE KEY `reference_transaction` (`reference_transaction`),
  KEY `id_etudiant` (`id_etudiant`),
  KEY `id_frais` (`id_frais`),
  KEY `idx_pay_stat` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id_paiement`, `id_etudiant`, `id_frais`, `montant_paye`, `date_paiement`, `statut`, `reference_transaction`) VALUES
(3, 3, 84, '1.00', '2026-05-09 19:04:18', 'en_attente', 'ISTAM-045789-20260509200418-968');

-- --------------------------------------------------------

--
-- Structure de la table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE IF NOT EXISTS `promotions` (
  `id_promotion` int(11) NOT NULL AUTO_INCREMENT,
  `nom_promotion` varchar(50) NOT NULL,
  PRIMARY KEY (`id_promotion`),
  UNIQUE KEY `nom_promotion` (`nom_promotion`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `promotions`
--

INSERT INTO `promotions` (`id_promotion`, `nom_promotion`) VALUES
(2, 'BAC 1'),
(3, 'BAC 2'),
(4, 'BAC 3'),
(5, 'BAC 4'),
(1, 'Préparatoire');

-- --------------------------------------------------------

--
-- Structure de la table `rapports`
--

DROP TABLE IF EXISTS `rapports`;
CREATE TABLE IF NOT EXISTS `rapports` (
  `id_rapport` int(11) NOT NULL AUTO_INCREMENT,
  `type_rapport` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_generation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_admin` int(11) DEFAULT NULL,
  `chemin_fichier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_rapport`),
  KEY `id_admin` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transaction_mobile_money`
--

DROP TABLE IF EXISTS `transaction_mobile_money`;
CREATE TABLE IF NOT EXISTS `transaction_mobile_money` (
  `id_transaction` int(11) NOT NULL AUTO_INCREMENT,
  `id_paiement` int(11) NOT NULL,
  `numero_telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operateur` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_api` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_transaction`),
  KEY `id_paiement` (`id_paiement`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `transaction_mobile_money`
--

INSERT INTO `transaction_mobile_money` (`id_transaction`, `id_paiement`, `numero_telephone`, `operateur`, `statut_api`) VALUES
(3, 3, '+243 973366308', 'Airtel Money', 'processing');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','secretaire','etudiant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_compte` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `email`, `mot_de_passe`, `role`, `statut_compte`, `created_at`) VALUES
(1, 'Administrateur ISTAM', 'admin@istam.cd', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'admin', 'actif', '2026-05-07 17:35:49'),
(2, 'Raphael Tshomba', 'raphaeltshomba3@gmail.com', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'etudiant', 'actif', '2026-05-08 08:59:27'),
(3, 'jocelyne', 'jocelyne@gmail.com', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'etudiant', 'actif', '2026-05-08 09:00:44'),
(4, 'KISIMBIKA GLOIRE', 'kisimbikagloire@gmail.com', '82e54ead8ded08eca9819c7dd06bcc9eb8fc85367090ae77eeee4351eb76dabb', 'secretaire', 'actif', '2026-05-08 13:18:51'),
(5, 'ISRAEL KALIMBA MOISE', 'miranzaya3@gmail.com', '03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4', 'etudiant', 'actif', '2026-05-09 16:45:20');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `etudiants_ibfk_2` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id_filiere`),
  ADD CONSTRAINT `fk_etudiant_promotion` FOREIGN KEY (`id_promotion`) REFERENCES `promotions` (`id_promotion`);

--
-- Contraintes pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD CONSTRAINT `filieres_ibfk_1` FOREIGN KEY (`id_faculte`) REFERENCES `facultes` (`id_faculte`) ON DELETE CASCADE;

--
-- Contraintes pour la table `frais`
--
ALTER TABLE `frais`
  ADD CONSTRAINT `fk_frais_promotion` FOREIGN KEY (`id_promotion`) REFERENCES `promotions` (`id_promotion`),
  ADD CONSTRAINT `frais_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id_filiere`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`),
  ADD CONSTRAINT `paiements_ibfk_2` FOREIGN KEY (`id_frais`) REFERENCES `frais` (`id_frais`);

--
-- Contraintes pour la table `rapports`
--
ALTER TABLE `rapports`
  ADD CONSTRAINT `rapports_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `transaction_mobile_money`
--
ALTER TABLE `transaction_mobile_money`
  ADD CONSTRAINT `transaction_mobile_money_ibfk_1` FOREIGN KEY (`id_paiement`) REFERENCES `paiements` (`id_paiement`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
