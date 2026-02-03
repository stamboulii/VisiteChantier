-- ========================================================
-- Base de données pour le suivi de chantiers
-- Version: 2.1
-- Date: 2026-02-02
-- ========================================================
--
-- INSTRUCTIONS D'UTILISATION:
--
-- 1. NOUVELLE INSTALLATION (première fois):
--    - Exécutez ce fichier complet dans phpMyAdmin ou MySQL CLI
--    - Cela créera la base de données avec toutes les tables
--    - Des données de test seront ajoutées automatiquement
--
-- 2. MISE À JOUR (base existante):
--    - Exécutez ce fichier complet
--    - Les tables existantes seront SUPPRIMÉES et recréées (⚠️ ATTENTION)
--    - OU exécutez UNIQUEMENT la section MIGRATIONS (plus sûr)
--
-- 3. MIGRATION SÉCURISÉE (recommandé pour bases existantes):
--    - Exécutez uniquement la section "MIGRATIONS" de ce fichier
--    - Vos données existantes seront préservées
--    - Les nouvelles colonnes/index seront ajoutés automatiquement
--
-- CHANGELOG:
-- v2.1 (2026-02-02):
--   - Ajout du partage public des chantiers
--   - Champs 'is_public' et 'share_token' pour la table chantiers
--   - Page publique accessible via token unique
--   - Toggle admin pour changer la visibilité
--
-- v2.0 (2026-02-02):
--   - Ajout du champ 'date_prise' pour les images
--   - Ajout de l'index sur 'date_prise' pour la timeline
--   - Fonctionnalités d'édition/suppression d'images
--   - Vue timeline chronologique
--
-- v1.0 (initiale):
--   - Structure de base avec users, chantiers, images, assignments
--
-- ========================================================

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
  `is_public` tinyint(1) DEFAULT '0',
  `share_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_share_token` (`share_token`),
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
  `date_prise` date DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chantier_id` (`chantier_id`),
  KEY `fk_images_user` (`user_id`),
  KEY `idx_date_prise` (`date_prise`),
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

-- ========================================================
-- SECTION MIGRATIONS - Pour les bases de données existantes
-- ========================================================
-- Cette section ajoute les nouvelles fonctionnalités sans supprimer les données existantes
-- Elle peut être exécutée en toute sécurité sur une base existante ou nouvelle

-- Migration 1: Ajout du champ date_prise pour les images (v2.0 - 2026-02-02)
-- Cette migration ajoute le champ date_prise pour permettre la timeline chronologique

-- Vérifier et ajouter la colonne date_prise si elle n'existe pas
SET @dbname = 'suivi_chantiers';
SET @tablename = 'images';
SET @columnname = 'date_prise';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 'Column date_prise already exists in images table.' AS Info;",
  "ALTER TABLE `images` ADD COLUMN `date_prise` date DEFAULT NULL AFTER `phase`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Vérifier et ajouter l'index sur date_prise s'il n'existe pas
SET @indexname = 'idx_date_prise';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND INDEX_NAME = @indexname
  ) > 0,
  "SELECT 'Index idx_date_prise already exists.' AS Info;",
  "ALTER TABLE `images` ADD KEY `idx_date_prise` (`date_prise`);"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Migrer les données existantes: définir date_prise = DATE(uploaded_at) pour les images sans date_prise
UPDATE `images`
SET `date_prise` = DATE(`uploaded_at`)
WHERE `date_prise` IS NULL AND `uploaded_at` IS NOT NULL;

-- Afficher le résultat de la migration
SELECT
    'Migration terminée avec succès!' AS Statut,
    COUNT(*) AS Total_Images,
    SUM(CASE WHEN date_prise IS NOT NULL THEN 1 ELSE 0 END) AS Images_Avec_Date
FROM `images`;

-- Migration 2: Ajout des champs de partage public (v2.1 - 2026-02-02)
-- Cette migration ajoute les champs is_public et share_token pour le partage public des chantiers

-- Vérifier et ajouter la colonne is_public si elle n'existe pas
SET @tablename = 'chantiers';
SET @columnname = 'is_public';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 'Column is_public already exists in chantiers table.' AS Info;",
  "ALTER TABLE `chantiers` ADD COLUMN `is_public` tinyint(1) DEFAULT '0' AFTER `template_file`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Vérifier et ajouter la colonne share_token si elle n'existe pas
SET @columnname = 'share_token';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 'Column share_token already exists in chantiers table.' AS Info;",
  "ALTER TABLE `chantiers` ADD COLUMN `share_token` varchar(64) DEFAULT NULL AFTER `is_public`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Vérifier et ajouter l'index sur share_token s'il n'existe pas
SET @indexname = 'idx_share_token';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND INDEX_NAME = @indexname
  ) > 0,
  "SELECT 'Index idx_share_token already exists.' AS Info;",
  "ALTER TABLE `chantiers` ADD KEY `idx_share_token` (`share_token`);"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Afficher le résultat de la migration
SELECT
    'Migration partage public terminée avec succès!' AS Statut,
    COUNT(*) AS Total_Chantiers,
    SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) AS Chantiers_Publics
FROM `chantiers`;

-- ========================================================
-- FIN DES MIGRATIONS
-- ========================================================

-- --------------------------------------------------------
-- Restauration des paramètres MySQL
-- --------------------------------------------------------
/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;