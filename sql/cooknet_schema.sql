-- CookNet schema for MySQL / MariaDB (phpMyAdmin: SQL tab or Import)
-- Charset: utf8mb4 | Engine: InnoDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `recipe_tag_map`;
DROP TABLE IF EXISTS `user_favorites`;
DROP TABLE IF EXISTS `recipe_images`;
DROP TABLE IF EXISTS `recipe_steps`;
DROP TABLE IF EXISTS `recipe_ingredients`;
DROP TABLE IF EXISTS `recipes`;
DROP TABLE IF EXISTS `recipe_tags`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE IF NOT EXISTS `cooknet`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `cooknet`;

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(120) NOT NULL,
  `avatar_url` VARCHAR(2048) NULL DEFAULT NULL,
  `bio` VARCHAR(500) NULL DEFAULT NULL,
  `role` ENUM('user', 'contributor', 'admin') NOT NULL DEFAULT 'user',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- categories (matches includes/category_links.php slugs)
-- ---------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(64) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(64) NOT NULL COMMENT 'Material Symbols icon name',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- recipes
-- ---------------------------------------------------------------------------
CREATE TABLE `recipes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT 'author',
  `category_id` SMALLINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  `prep_minutes` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `cook_minutes` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `difficulty` ENUM('easy', 'medium', 'hard') NULL DEFAULT NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `rating_avg` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recipes_slug` (`slug`),
  KEY `idx_recipes_user` (`user_id`),
  KEY `idx_recipes_category` (`category_id`),
  KEY `idx_recipes_status` (`status`),
  KEY `idx_recipes_featured` (`featured`),
  CONSTRAINT `fk_recipes_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recipes_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- recipe_ingredients
-- ---------------------------------------------------------------------------
CREATE TABLE `recipe_ingredients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipe_id` INT UNSIGNED NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `quantity` VARCHAR(64) NOT NULL DEFAULT '',
  `name` VARCHAR(512) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recipe_ingredients_recipe` (`recipe_id`),
  CONSTRAINT `fk_recipe_ingredients_recipe`
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- recipe_steps
-- ---------------------------------------------------------------------------
CREATE TABLE `recipe_steps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipe_id` INT UNSIGNED NOT NULL,
  `step_number` SMALLINT UNSIGNED NOT NULL,
  `instruction` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recipe_steps_order` (`recipe_id`, `step_number`),
  KEY `idx_recipe_steps_recipe` (`recipe_id`),
  CONSTRAINT `fk_recipe_steps_recipe`
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- recipe_images (hero + gallery; path or full URL)
-- ---------------------------------------------------------------------------
CREATE TABLE `recipe_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipe_id` INT UNSIGNED NOT NULL,
  `role` ENUM('hero', 'gallery') NOT NULL DEFAULT 'gallery',
  `path_or_url` VARCHAR(2048) NOT NULL,
  `alt_text` VARCHAR(255) NULL DEFAULT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_recipe_images_recipe` (`recipe_id`),
  KEY `idx_recipe_images_role` (`recipe_id`, `role`),
  CONSTRAINT `fk_recipe_images_recipe`
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- user_favorites (saved recipes)
-- ---------------------------------------------------------------------------
CREATE TABLE `user_favorites` (
  `user_id` INT UNSIGNED NOT NULL,
  `recipe_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `recipe_id`),
  KEY `idx_user_favorites_recipe` (`recipe_id`),
  CONSTRAINT `fk_user_favorites_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_favorites_recipe`
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- recipe_tags + map (e.g. chips: Healthy, Quick)
-- ---------------------------------------------------------------------------
CREATE TABLE `recipe_tags` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(64) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recipe_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recipe_tag_map` (
  `recipe_id` INT UNSIGNED NOT NULL,
  `tag_id` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`recipe_id`, `tag_id`),
  KEY `idx_recipe_tag_map_tag` (`tag_id`),
  CONSTRAINT `fk_recipe_tag_map_recipe`
    FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recipe_tag_map_tag`
    FOREIGN KEY (`tag_id`) REFERENCES `recipe_tags` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Seed: categories (aligned with includes/category_links.php)
-- ---------------------------------------------------------------------------
INSERT INTO `categories` (`id`, `slug`, `label`, `icon`, `sort_order`) VALUES
  (1, 'main_dishes', 'Main Dishes', 'restaurant', 1),
  (2, 'breakfast', 'Breakfast', 'egg_alt', 2),
  (3, 'vegetarian', 'Vegetarian', 'eco', 3),
  (4, 'desserts', 'Desserts', 'cake', 4),
  (5, 'quick_meals', 'Quick Meals', 'timer', 5);

-- ---------------------------------------------------------------------------
-- Seed: optional common tags for UI chips
-- ---------------------------------------------------------------------------
INSERT INTO `recipe_tags` (`id`, `slug`, `label`) VALUES
  (1, 'healthy', 'Healthy'),
  (2, 'quick', 'Quick'),
  (3, 'vegetarian', 'Vegetarian'),
  (4, 'seasonal', 'Seasonal'),
  (5, 'chefs_choice', 'Chef''s Choice');
