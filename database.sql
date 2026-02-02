-- --------------------------------------------------------
-- Base de données pour le suivi de chantiers
-- Version: 1.0
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `suivi_chantiers` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `suivi_chantiers`;

-- --------------------------------------------------------
-- Structure de la table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `chantier_assignments`;
DROP TABLE IF EXISTS `images`;
DROP TABLE IF EXISTS `chantiers`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','architect') DEFAULT 'architect',
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `chantiers`
-- --------------------------------------------------------
CREATE TABLE `chantiers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `nom` varchar(200) NOT NULL,
  `adresse` text,
  `description` text,
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `statut` enum('en_cours','termine','en_pause') DEFAULT 'en_cours',
  `type` enum('chantier','visite_commerciale','etat_des_lieux','autre') DEFAULT 'chantier',
  `lot_id` varchar(50) DEFAULT NULL,
  `template_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `chantiers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `images`
-- --------------------------------------------------------
CREATE TABLE `images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chantier_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `commentaire` text,
  `phase` varchar(50) DEFAULT 'autres',
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chantier_id` (`chantier_id`),
  KEY `fk_images_user` (`user_id`),
  CONSTRAINT `fk_images_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `images_ibfk_1` FOREIGN KEY (`chantier_id`) REFERENCES `chantiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `chantier_assignments`
-- --------------------------------------------------------
CREATE TABLE `chantier_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chantier_id` int NOT NULL,
  `user_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`chantier_id`,`user_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_user` (`user_id`),
  KEY `idx_chantier` (`chantier_id`),
  CONSTRAINT `chantier_assignments_ibfk_1` FOREIGN KEY (`chantier_id`) REFERENCES `chantiers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chantier_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chantier_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Données de test pour la table `users`
-- Mot de passe par défaut pour tous: "password123"
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `email`, `password`, `role`, `nom`, `prenom`) VALUES
('admin', 'admin@example.com', '$2y$12$1RzBpx7LcGy6YS1iqTImSuSUyGuudjCiFgltBQs7SQP8B1MFBKSqa', 'admin', 'Admin', 'Système'),
('architect1', 'architect1@example.com', '$2y$12$1RzBpx7LcGy6YS1iqTImSuSUyGuudjCiFgltBQs7SQP8B1MFBKSqa', 'architect', 'Dupont', 'Jean'),
('architect2', 'architect2@example.com', '$2y$12$1RzBpx7LcGy6YS1iqTImSuSUyGuudjCiFgltBQs7SQP8B1MFBKSqa', 'architect', 'Martin', 'Sophie');

-- --------------------------------------------------------
-- Données de test pour la table `chantiers`
-- --------------------------------------------------------
INSERT INTO `chantiers` (`user_id`, `nom`, `adresse`, `description`, `date_debut`, `date_fin_prevue`, `statut`, `type`) VALUES
(1, 'Villa Moderne - Marseille', '25 Avenue du Prado, 13008 Marseille', 'Construction d\'une villa contemporaine de 250m²', '2024-01-15', '2024-12-31', 'en_cours', 'chantier'),
(1, 'Rénovation Appartement Haussmannien', '12 Rue de Rivoli, 75001 Paris', 'Rénovation complète d\'un appartement de 120m²', '2024-03-01', '2024-09-30', 'en_cours', 'chantier'),
(1, 'Extension Maison Individuelle', '8 Chemin des Vignes, 69006 Lyon', 'Extension et surélévation d\'une maison', '2023-11-20', '2024-06-30', 'termine', 'chantier');

-- --------------------------------------------------------
-- Restauration des paramètres MySQL
-- --------------------------------------------------------
/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;