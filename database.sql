-- Base de données pour le suivi de chantiers

CREATE DATABASE IF NOT EXISTS suivi_chantiers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE suivi_chantiers;

-- Table des utilisateurs (architectes)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des chantiers
CREATE TABLE IF NOT EXISTS chantiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    adresse TEXT,
    description TEXT,
    date_debut DATE,
    date_fin_prevue DATE,
    statut ENUM('en_cours', 'termine', 'en_pause') DEFAULT 'en_cours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des images
CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    commentaire TEXT,
    phase ENUM('fondations', 'structure', 'clos_couvert', 'second_oeuvre', 'finitions', 'autres') DEFAULT 'autres',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS `chantier_assignments` (
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion d'un utilisateur de test (mot de passe: architect123)
INSERT INTO users (username, email, password, nom, prenom) VALUES
('architect', 'architect@example.com', '$2y$10$7Zx3Y9YQZzN5H5qKZ8vQu.xE2F8RgKp7O1VQhKk5ZGz7fQ8pX5K8m', 'Dupont', 'Jean');

-- Insertion de chantiers de test
INSERT INTO chantiers (user_id, nom, adresse, description, date_debut, statut) VALUES
(1, 'Villa Moderne - Marseille', '25 Avenue du Prado, 13008 Marseille', 'Construction d\'une villa contemporaine de 250m²', '2024-01-15', 'en_cours'),
(1, 'Rénovation Appartement Haussmannien', '12 Rue de Rivoli, 75001 Paris', 'Rénovation complète d\'un appartement de 120m²', '2024-03-01', 'en_cours'),
(1, 'Extension Maison Individuelle', '8 Chemin des Vignes, 69006 Lyon', 'Extension et surélévation d\'une maison', '2023-11-20', 'termine');
