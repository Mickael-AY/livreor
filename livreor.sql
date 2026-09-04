-- =====================================================================
-- Livre d'Or — schéma de la base de données
-- =====================================================================
--
-- Ce script crée les tables et le jeu de démonstration. Il ne crée pas la
-- base elle-même : sur un hébergement mutualisé, l'utilisateur applicatif
-- n'a pas le droit d'exécuter CREATE DATABASE, la base étant créée depuis
-- le panneau d'hébergement.
--
--   En local     : mysql -u root -e "CREATE DATABASE IF NOT EXISTS livreor
--                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--                  mysql -u root livreor < livreor.sql
--
--   En production : importer ce fichier dans la base déjà créée
--                   (phpMyAdmin, ou « Import Dump » sous Plesk).
--
-- Compatibilité : MariaDB 5.5 et supérieur, MySQL 5.5 et supérieur.
-- Les colonnes datées n'utilisent qu'un seul remplissage automatique par
-- table, seule forme acceptée par MariaDB 5.5 ; les autres dates sont
-- fournies explicitement par l'application.
-- =====================================================================

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `commentaires`;
DROP TABLE IF EXISTS `utilisateurs`;

-- ---------------------------------------------------------------------
-- Utilisateurs enregistrés
-- ---------------------------------------------------------------------
CREATE TABLE `utilisateurs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(50) NOT NULL,

  -- Longueur dimensionnée pour le condensat bcrypt (60 caractères),
  -- avec de la marge pour un algorithme futur. Jamais le mot de passe.
  `password` VARCHAR(255) NOT NULL,

  -- Seule colonne remplie automatiquement par le serveur : MariaDB 5.5
  -- n'en accepte qu'une par table.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- Renseignée par l'application lors d'une modification du profil.
  `updated_at` DATETIME NULL DEFAULT NULL,

  PRIMARY KEY (`id`),

  -- L'unicité est portée par la base, et pas seulement par le contrôle PHP.
  UNIQUE KEY `uniq_utilisateurs_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Messages du livre d'or
-- ---------------------------------------------------------------------
CREATE TABLE `commentaires` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commentaire` TEXT NOT NULL,
  `id_utilisateur` INT UNSIGNED NOT NULL,

  -- Fournie par l'application au moment de l'insertion.
  `date` DATETIME NOT NULL,

  PRIMARY KEY (`id`),

  -- Couvre l'affichage du livre d'or trié par date et le décompte
  -- des messages d'un utilisateur sur la page profil.
  KEY `idx_commentaires_utilisateur_date` (`id_utilisateur`, `date`),

  -- Un message n'a aucun sens sans son auteur : supprimer un compte
  -- supprime ses messages.
  CONSTRAINT `fk_commentaires_utilisateur`
    FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Compte de démonstration
-- ---------------------------------------------------------------------
-- Mot de passe en clair : admin123
-- À SUPPRIMER avant toute mise en ligne :
--   DELETE FROM utilisateurs WHERE login = 'admin';
INSERT INTO `utilisateurs` (`login`, `password`)
VALUES ('admin', '$2y$10$v1TA/k2dEvipIQVuf1rww.lM6LcST5mP.9yXMvgC30MIUGS1oYszW');
