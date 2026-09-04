-- Schema SQL pour l'application Livre d'Or
-- Exécuter ce script avec un utilisateur MySQL disposant des droits CREATE/ALTER

DROP DATABASE IF EXISTS `livreor`;
CREATE DATABASE `livreor`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `livreor`;

-- Table des utilisateurs enregistrés
CREATE TABLE `utilisateurs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_utilisateurs_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des commentaires du livre d'or
CREATE TABLE `commentaires` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commentaire` TEXT NOT NULL,
  `id_utilisateur` INT UNSIGNED NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_commentaires_utilisateur_date` (`id_utilisateur`, `date`),
  CONSTRAINT `fk_commentaires_utilisateur`
    FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (Optionnel) utilisateur de démonstration
INSERT INTO `utilisateurs` (`login`, `password`)
VALUES ('admin', '$2y$10$v1TA/k2dEvipIQVuf1rww.lM6LcST5mP.9yXMvgC30MIUGS1oYszW');
-- Mot de passe en clair : admin123
