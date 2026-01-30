-- Enterprise Event Management and Ticketing System Database Schema
-- Created for comprehensive event management with enterprise features

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: event_ticketing
CREATE DATABASE IF NOT EXISTS `event_ticketing` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `event_ticketing`;

-- Users and Authentication
CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_uuid_unique` (`uuid`),
  KEY `users_status_index` (`status`),
  KEY `users_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles and Permissions
CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `level` int NOT NULL DEFAULT 0,
  `is_system` boolean NOT NULL DEFAULT false,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `module` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`),
  KEY `permissions_module_index` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_role_id_foreign` (`role_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `assigned_by` bigint UNSIGNED DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_unique` (`user_id`,`role_id`),
  KEY `user_roles_user_id_foreign` (`user_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  KEY `user_roles_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `user_roles_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Venues
CREATE TABLE `venues` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `capacity` int NOT NULL DEFAULT 0,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `images` json DEFAULT NULL,
  `facilities` json DEFAULT NULL,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `venues_uuid_unique` (`uuid`),
  KEY `venues_status_index` (`status`),
  KEY `venues_created_by_foreign` (`created_by`),
  CONSTRAINT `venues_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events
CREATE TABLE `events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext,
  `short_description` text,
  `venue_id` bigint UNSIGNED NOT NULL,
  `event_type` enum('concert','conference','sports','theater','festival','exhibition','workshop','other') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `images` json DEFAULT NULL,
  `organizer_name` varchar(255) DEFAULT NULL,
  `organizer_email` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','live','closed','cancelled','archived') NOT NULL DEFAULT 'draft',
  `visibility` enum('public','private','unlisted') NOT NULL DEFAULT 'public',
  `max_tickets_per_order` int NOT NULL DEFAULT 10,
  `min_tickets_per_order` int NOT NULL DEFAULT 1,
  `created_by` bigint UNSIGNED NOT NULL,
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_uuid_unique` (`uuid`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  KEY `events_venue_id_foreign` (`venue_id`),
  KEY `events_status_index` (`status`),
  KEY `events_starts_at_index` (`starts_at`),
  KEY `events_created_by_foreign` (`created_by`),
  CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `events_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket Types
CREATE TABLE `ticket_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `ticket_type` enum('general','vip','early_bird','group','complimentary','student','senior','staff') NOT NULL DEFAULT 'general',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `quantity_available` int NOT NULL DEFAULT 0,
  `quantity_sold` int NOT NULL DEFAULT 0,
  `min_per_order` int NOT NULL DEFAULT 1,
  `max_per_order` int NOT NULL DEFAULT 10,
  `sale_starts_at` timestamp NULL DEFAULT NULL,
  `sale_ends_at` timestamp NULL DEFAULT NULL,
  `is_active` boolean NOT NULL DEFAULT true,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_types_event_id_foreign` (`event_id`),
  KEY `ticket_types_is_active_index` (`is_active`),
  CONSTRAINT `ticket_types_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders
CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','payment_pending','confirmed','paid','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `promo_code` varchar(50) DEFAULT NULL,
  `payment_method` enum('credit_card','paypal','stripe','bank_transfer') DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  UNIQUE KEY `orders_uuid_unique` (`uuid`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_status_index` (`status`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items
CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint UNSIGNED NOT NULL,
  `ticket_type_id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_ticket_type_id_foreign` (`ticket_type_id`),
  KEY `order_items_event_id_foreign` (`event_id`),
  CONSTRAINT `order_items_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ticket_type_id_foreign` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets
CREATE TABLE `tickets` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `order_item_id` bigint UNSIGNED NOT NULL,
  `ticket_type_id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED NOT NULL,
  `attendee_name` varchar(255) NOT NULL,
  `attendee_email` varchar(255) DEFAULT NULL,
  `price_paid` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `qr_code` text,
  `barcode` text,
  `status` enum('pending','confirmed','checked_in','used','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_uuid_unique` (`uuid`),
  UNIQUE KEY `tickets_ticket_number_unique` (`ticket_number`),
  KEY `tickets_order_id_foreign` (`order_id`),
  KEY `tickets_ticket_type_id_foreign` (`ticket_type_id`),
  KEY `tickets_event_id_foreign` (`event_id`),
  KEY `tickets_status_index` (`status`),
  CONSTRAINT `tickets_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  CONSTRAINT `tickets_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_ticket_type_id_foreign` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check-in Gates
CREATE TABLE `check_in_gates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `venue_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `gate_type` enum('entrance','exit','vip','staff') NOT NULL DEFAULT 'entrance',
  `is_active` boolean NOT NULL DEFAULT true,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `check_in_gates_venue_id_foreign` (`venue_id`),
  KEY `check_in_gates_created_by_foreign` (`created_by`),
  CONSTRAINT `check_in_gates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `check_in_gates_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics
CREATE TABLE `analytics_events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `views` int NOT NULL DEFAULT 0,
  `orders` int NOT NULL DEFAULT 0,
  `revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tickets_sold` int NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `analytics_events_unique` (`event_id`,`date`),
  KEY `analytics_events_event_id_foreign` (`event_id`),
  CONSTRAINT `analytics_events_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs
CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` bigint UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
