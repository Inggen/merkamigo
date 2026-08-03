-- Adminer 4.8.4 MySQL 9.6.0 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `analytics_events`;
CREATE TABLE `analytics_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `visitor_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_events_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `analytics_events_business_id_type_created_at_index` (`business_id`,`type`,`created_at`),
  KEY `analytics_events_created_at_index` (`created_at`),
  CONSTRAINT `analytics_events_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `analytics_events` (`id`, `business_id`, `type`, `subject_type`, `subject_id`, `visitor_hash`, `created_at`, `updated_at`) VALUES
(1,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 03:21:06',	'2026-08-01 03:21:06'),
(2,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 03:21:06',	'2026-08-01 03:21:06'),
(3,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 04:08:45',	'2026-08-01 04:08:45'),
(4,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 04:08:46',	'2026-08-01 04:08:46'),
(5,	1,	'whatsapp_click',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 04:26:05',	'2026-08-01 04:26:05'),
(6,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 04:26:55',	'2026-08-01 04:26:55'),
(7,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 04:38:58',	'2026-08-01 04:38:58'),
(8,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 04:38:59',	'2026-08-01 04:38:59'),
(9,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 05:36:10',	'2026-08-01 05:36:10'),
(10,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 05:36:10',	'2026-08-01 05:36:10'),
(11,	1,	'compartir_click',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 05:36:17',	'2026-08-01 05:36:17'),
(12,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 05:36:21',	'2026-08-01 05:36:21'),
(13,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 05:36:21',	'2026-08-01 05:36:21'),
(14,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 06:06:13',	'2026-08-01 06:06:13'),
(15,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 06:06:13',	'2026-08-01 06:06:13'),
(16,	1,	'compartir_click',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 06:22:05',	'2026-08-01 06:22:05'),
(17,	1,	'whatsapp_click',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 07:20:44',	'2026-08-01 07:20:44'),
(18,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 07:24:05',	'2026-08-01 07:24:05'),
(19,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 07:24:05',	'2026-08-01 07:24:05'),
(20,	1,	'compartir_click',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 07:26:13',	'2026-08-01 07:26:13'),
(21,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'836385448e4b7be515c32277fc7e3e4601370c833628b8a3abba23ea77279022',	'2026-08-01 07:36:09',	'2026-08-01 07:36:09'),
(22,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 07:44:15',	'2026-08-01 07:44:15'),
(23,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 07:44:18',	'2026-08-01 07:44:18'),
(24,	1,	'compartir_click',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 07:45:04',	'2026-08-01 07:45:04'),
(25,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 08:04:05',	'2026-08-01 08:04:05'),
(26,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 08:04:07',	'2026-08-01 08:04:07'),
(27,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 08:34:08',	'2026-08-01 08:34:08'),
(28,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 08:34:09',	'2026-08-01 08:34:09'),
(29,	1,	'compartir_click',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-01 08:49:47',	'2026-08-01 08:49:47'),
(30,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 01:13:40',	'2026-08-02 01:13:40'),
(31,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 01:13:45',	'2026-08-02 01:13:45'),
(32,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:16:55',	'2026-08-02 03:16:55'),
(33,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:16:56',	'2026-08-02 03:16:56'),
(34,	1,	'compartir_click',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:17:32',	'2026-08-02 03:17:32'),
(35,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:17:39',	'2026-08-02 03:17:39'),
(36,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:17:39',	'2026-08-02 03:17:39'),
(37,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:48:50',	'2026-08-02 03:48:50'),
(38,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:48:51',	'2026-08-02 03:48:51'),
(39,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:49:18',	'2026-08-02 03:49:18'),
(40,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:49:18',	'2026-08-02 03:49:18'),
(41,	1,	'compartir_click',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:55:06',	'2026-08-02 03:55:06'),
(42,	1,	'whatsapp_click',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 03:56:59',	'2026-08-02 03:56:59'),
(43,	1,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 04:54:28',	'2026-08-02 04:54:28'),
(44,	1,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 04:54:29',	'2026-08-02 04:54:29'),
(45,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 05:04:52',	'2026-08-02 05:04:52'),
(46,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 05:04:52',	'2026-08-02 05:04:52'),
(47,	1,	'compartir_click',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 05:04:57',	'2026-08-02 05:04:57'),
(48,	1,	'whatsapp_click',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 05:05:00',	'2026-08-02 05:05:00'),
(49,	1,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 07:42:16',	'2026-08-02 07:42:16'),
(50,	1,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-02 07:42:16',	'2026-08-02 07:42:16'),
(53,	3,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:25:11',	'2026-08-03 04:25:11'),
(54,	3,	'vitrina_view',	NULL,	NULL,	'836385448e4b7be515c32277fc7e3e4601370c833628b8a3abba23ea77279022',	'2026-08-03 04:28:12',	'2026-08-03 04:28:12'),
(55,	5,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:54:46',	'2026-08-03 04:54:46'),
(56,	5,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:54:47',	'2026-08-03 04:54:47'),
(57,	5,	'compartir_click',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:55:49',	'2026-08-03 04:55:49'),
(58,	3,	'vitrina_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:56:05',	'2026-08-03 04:56:05'),
(59,	3,	'qr_view',	NULL,	NULL,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:56:05',	'2026-08-03 04:56:05'),
(60,	3,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:56:30',	'2026-08-03 04:56:30'),
(61,	3,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:56:31',	'2026-08-03 04:56:31'),
(62,	3,	'compartir_click',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:56:36',	'2026-08-03 04:56:36'),
(63,	3,	'whatsapp_click',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 04:56:59',	'2026-08-03 04:56:59'),
(64,	5,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 05:04:50',	'2026-08-03 05:04:50'),
(65,	5,	'qr_view',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 05:04:50',	'2026-08-03 05:04:50'),
(66,	5,	'whatsapp_click',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'db16530de4027720379e38212dd807f79a11a235fbd53a58fc3d3589a5d0ca89',	'2026-08-03 05:06:39',	'2026-08-03 05:06:39'),
(67,	5,	'producto_view',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'836385448e4b7be515c32277fc7e3e4601370c833628b8a3abba23ea77279022',	'2026-08-03 05:06:43',	'2026-08-03 05:06:43');

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `subject_type`, `subject_id`, `metadata`, `ip_address`, `created_at`) VALUES
(1,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-01 03:06:25'),
(2,	2,	'business.created',	'App\\Domain\\Businesses\\Models\\Business',	1,	'{\"business_id\": 1}',	'127.0.0.1',	'2026-08-01 03:07:04'),
(3,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:07:26'),
(4,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:09:59'),
(5,	2,	'product.created',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:10:37'),
(6,	2,	'business.published',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:07'),
(7,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:14'),
(8,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:15'),
(9,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:27'),
(10,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:37'),
(11,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:41'),
(12,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:45'),
(13,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:47'),
(14,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:48'),
(15,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:51'),
(16,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:11:55'),
(17,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:02'),
(18,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:14'),
(19,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:15'),
(20,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:16'),
(21,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:18'),
(22,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:30'),
(23,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:40'),
(24,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:46'),
(25,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:53'),
(26,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:12:58'),
(27,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:01'),
(28,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:06'),
(29,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:10'),
(30,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:14'),
(31,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:19'),
(32,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:27'),
(33,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:33'),
(34,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:37'),
(35,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:41'),
(36,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:44'),
(37,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:13:46'),
(38,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:14:02'),
(39,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:14:05'),
(40,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:14:12'),
(41,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:14:14'),
(42,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:14:20'),
(43,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:14:23'),
(44,	2,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:14:34'),
(45,	2,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:18:38'),
(46,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 03:19:22'),
(47,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	1,	'[]',	'127.0.0.1',	'2026-08-01 04:11:36'),
(48,	2,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'[]',	'127.0.0.1',	'2026-08-01 08:52:03'),
(49,	1,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-01 08:58:27'),
(50,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-01 09:12:21'),
(51,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-01 18:22:49'),
(52,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-01 18:22:49'),
(53,	2,	'need.published',	'App\\Domain\\Needs\\Models\\Need',	1,	'[]',	'127.0.0.1',	'2026-08-01 20:23:17'),
(54,	2,	'support_ticket.resolved',	'App\\Domain\\Moderation\\Models\\SupportTicket',	1,	'{\"status\": \"en_progreso\"}',	'127.0.0.1',	'2026-08-01 22:43:12'),
(55,	NULL,	'business.featured_purchased',	'App\\Domain\\Businesses\\Models\\Business',	1,	'{\"days\": 7, \"payment_id\": 3}',	'127.0.0.1',	'2026-08-01 22:54:07'),
(56,	NULL,	'subscription.plan_changed',	'App\\Domain\\Billing\\Models\\Subscription',	1,	'{\"to_plan\": \"emprendedor\", \"from_plan\": \"gratis\", \"business_id\": 1}',	'127.0.0.1',	'2026-08-01 22:55:52'),
(57,	2,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	1,	'[]',	'127.0.0.1',	'2026-08-02 03:04:16'),
(58,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-02 06:08:29'),
(59,	2,	'business.created',	'App\\Domain\\Businesses\\Models\\Business',	5,	'{\"business_id\": 5}',	'127.0.0.1',	'2026-08-02 08:06:30'),
(60,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:07:15'),
(61,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:07:53'),
(62,	2,	'product.created',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'[]',	'127.0.0.1',	'2026-08-02 08:09:37'),
(63,	2,	'business.published',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:10:01'),
(64,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:11:13'),
(65,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:11:29'),
(66,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:11:35'),
(67,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:11:43'),
(68,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:11:44'),
(69,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:12:00'),
(70,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:12:55'),
(71,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:13:05'),
(72,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:13:08'),
(73,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-02 08:13:16'),
(74,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-02 21:44:51'),
(75,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-02 21:50:03'),
(76,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-02 23:28:11'),
(77,	1,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-02 23:29:06'),
(78,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:40:54'),
(79,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:41:04'),
(80,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:41:14'),
(81,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:41:19'),
(82,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:41:32'),
(83,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:41:34'),
(84,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:41:58'),
(85,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:42:23'),
(86,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:42:35'),
(87,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:42:35'),
(88,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:43:00'),
(89,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:43:03'),
(90,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:43:26'),
(91,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:43:31'),
(92,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:43:39'),
(93,	1,	'product.created',	'App\\Domain\\Storefronts\\Models\\Product',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:44:51'),
(94,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:44:55'),
(95,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:45:24'),
(96,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:45:44'),
(97,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:45:55'),
(98,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:46:37'),
(99,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-02 23:46:48'),
(100,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-02 23:52:56'),
(101,	2,	'need.published',	'App\\Domain\\Needs\\Models\\Need',	2,	'[]',	'127.0.0.1',	'2026-08-02 23:55:41'),
(102,	1,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-03 00:23:18'),
(103,	1,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-03 00:24:56'),
(104,	1,	'product.created',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'[]',	'127.0.0.1',	'2026-08-03 04:18:38'),
(105,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'[]',	'127.0.0.1',	'2026-08-03 04:18:57'),
(106,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'[]',	'127.0.0.1',	'2026-08-03 04:18:59'),
(107,	1,	'business.published',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-03 04:19:08'),
(108,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-03 04:19:09'),
(109,	NULL,	'subscription.plan_changed',	'App\\Domain\\Billing\\Models\\Subscription',	2,	'{\"to_plan\": \"emprendedor\", \"from_plan\": \"gratis\", \"business_id\": 3}',	'127.0.0.1',	'2026-08-03 04:39:36'),
(110,	1,	'subscription.plan_changed',	'App\\Domain\\Billing\\Models\\Subscription',	3,	'{\"to_plan\": \"gratis\", \"from_plan\": \"emprendedor\", \"business_id\": 3}',	'127.0.0.1',	'2026-08-03 04:41:09'),
(111,	1,	'business.verification_requested',	'App\\Domain\\Trust\\Models\\BusinessVerification',	1,	'{\"business_id\": 3}',	'127.0.0.1',	'2026-08-03 04:47:04'),
(112,	1,	'business.collaborator_added',	'App\\Domain\\Businesses\\Models\\Business',	3,	'{\"role\": \"collaborator\", \"user_id\": 2}',	'127.0.0.1',	'2026-08-03 04:48:40'),
(113,	1,	'business.collaborator_removed',	'App\\Domain\\Businesses\\Models\\Business',	3,	'{\"user_id\": 2}',	'127.0.0.1',	'2026-08-03 04:48:47'),
(114,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'[]',	'127.0.0.1',	'2026-08-03 04:59:53'),
(115,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'[]',	'127.0.0.1',	'2026-08-03 05:00:10'),
(116,	1,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	4,	'[]',	'127.0.0.1',	'2026-08-03 05:00:18'),
(117,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-03 05:00:44'),
(118,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-03 05:01:03'),
(119,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-03 05:01:05'),
(120,	1,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	3,	'[]',	'127.0.0.1',	'2026-08-03 05:01:06'),
(121,	2,	'auth.login',	NULL,	NULL,	'[]',	'127.0.0.1',	'2026-08-03 05:03:36'),
(122,	2,	'need.suspended',	'App\\Domain\\Needs\\Models\\Need',	3,	'{\"reason\": \"Incumple las reglas de comunidad\"}',	'127.0.0.1',	'2026-08-03 05:04:13'),
(123,	2,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'[]',	'127.0.0.1',	'2026-08-03 05:05:45'),
(124,	2,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'[]',	'127.0.0.1',	'2026-08-03 05:05:57'),
(125,	2,	'product.updated',	'App\\Domain\\Storefronts\\Models\\Product',	2,	'[]',	'127.0.0.1',	'2026-08-03 05:06:09'),
(126,	2,	'business.updated',	'App\\Domain\\Businesses\\Models\\Business',	5,	'[]',	'127.0.0.1',	'2026-08-03 05:07:16');

DROP TABLE IF EXISTS `billing_products`;
CREATE TABLE `billing_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price_cents` int unsigned NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_products_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `billing_products` (`id`, `slug`, `name`, `description`, `price_cents`, `kind`, `payload`, `is_active`, `created_at`, `updated_at`) VALUES
(1,	'destacado-7',	'Destacado 7 días',	'Tu vitrina aparece primero en la Plaza de tu municipio durante 7 días.',	990000,	'destacado',	'{\"days\": 7}',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(2,	'destacado-14',	'Destacado 14 días',	'Tu vitrina aparece primero en la Plaza de tu municipio durante 14 días.',	1690000,	'destacado',	'{\"days\": 14}',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(3,	'destacado-30',	'Destacado 30 días',	'Tu vitrina aparece primero en la Plaza de tu municipio durante 30 días.',	2990000,	'destacado',	'{\"days\": 30}',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(4,	'vitrina-asistida',	'Vitrina asistida',	'Nuestro equipo te ayuda a completar y pulir tu vitrina (fotos, descripciones, categorías).',	4990000,	'vitrina_asistida',	NULL,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(5,	'kit-arranca-bonito',	'Kit Arranca Bonito',	'Sesión de fotos básica + vitrina asistida + destacado de 14 días para arrancar con todo.',	9990000,	'kit_arranca_bonito',	'{\"days\": 14}',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35');

DROP TABLE IF EXISTS `business_attributes`;
CREATE TABLE `business_attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_attributes_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `business_attributes` (`id`, `name`, `slug`, `is_active`, `created_at`, `updated_at`) VALUES
(1,	'Producto artesanal',	'producto-artesanal',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(2,	'Hecho en la región',	'hecho-en-la-region',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(3,	'Ingredientes frescos',	'ingredientes-frescos',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(4,	'Atención cercana',	'atencion-cercana',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(5,	'Domicilios disponibles',	'domicilios-disponibles',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(6,	'Acepta pagos digitales',	'acepta-pagos-digitales',	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35');

DROP TABLE IF EXISTS `business_memberships`;
CREATE TABLE `business_memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('invitado','activo','revocado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_memberships_business_id_user_id_unique` (`business_id`,`user_id`),
  KEY `business_memberships_user_id_foreign` (`user_id`),
  CONSTRAINT `business_memberships_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_memberships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `business_memberships` (`id`, `business_id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1,	1,	2,	'activo',	'2026-08-01 03:07:04',	'2026-08-01 03:07:04'),
(3,	3,	1,	'activo',	'2026-07-29 18:44:04',	'2026-07-29 18:44:04'),
(5,	5,	2,	'activo',	'2026-08-02 08:06:30',	'2026-08-02 08:06:30');

DROP TABLE IF EXISTS `business_municipalities`;
CREATE TABLE `business_municipalities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `municipality_id` bigint unsigned NOT NULL,
  `zone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_municipalities_business_id_municipality_id_unique` (`business_id`,`municipality_id`),
  KEY `business_municipalities_municipality_id_foreign` (`municipality_id`),
  CONSTRAINT `business_municipalities_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_municipalities_municipality_id_foreign` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `business_municipalities` (`id`, `business_id`, `municipality_id`, `zone`, `created_at`, `updated_at`) VALUES
(1,	1,	1,	NULL,	'2026-08-01 03:07:04',	'2026-08-01 03:07:04'),
(2,	1,	2,	NULL,	'2026-08-01 03:07:04',	'2026-08-01 03:07:04'),
(3,	1,	3,	NULL,	'2026-08-01 03:07:04',	'2026-08-01 03:07:04');

DROP TABLE IF EXISTS `business_verifications`;
CREATE TABLE `business_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned DEFAULT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basica',
  `status` enum('sin_iniciar','en_revision','requiere_ajustes','verificada','vencida','revocada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sin_iniciar',
  `legal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_document_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_document_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_note` text COLLATE utf8mb4_unicode_ci,
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `expiry_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_verifications_requested_by_foreign` (`requested_by`),
  KEY `business_verifications_reviewed_by_foreign` (`reviewed_by`),
  KEY `business_verifications_business_id_status_index` (`business_id`,`status`),
  CONSTRAINT `business_verifications_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_verifications_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_verifications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `business_verifications` (`id`, `business_id`, `requested_by`, `level`, `status`, `legal_name`, `contact_name`, `contact_document_type`, `contact_document_number`, `verification_document_path`, `request_note`, `review_note`, `reviewed_by`, `reviewed_at`, `expires_at`, `expiry_reminder_sent_at`, `created_at`, `updated_at`) VALUES
(1,	3,	1,	'basica',	'en_revision',	'Inggen',	'John Alexander Ramirez',	'CC',	'3146419',	'business-verifications/3/QMSiaun1UjnOxX6NdIWQLrfYF67Y30jfU4x5ggAZ.pdf',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	'2026-08-03 04:47:04',	'2026-08-03 04:47:04');

DROP TABLE IF EXISTS `businesses`;
CREATE TABLE `businesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `municipality_id` bigint unsigned DEFAULT NULL,
  `zone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hours` json DEFAULT NULL,
  `social_links` json DEFAULT NULL,
  `payment_info` text COLLATE utf8mb4_unicode_ci,
  `attributes` json DEFAULT NULL,
  `whatsapp_faq_answers` json DEFAULT NULL,
  `status` enum('borrador','pendiente_revision','publicado','suspendido','archivado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `featured_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `businesses_slug_unique` (`slug`),
  KEY `businesses_organization_id_foreign` (`organization_id`),
  KEY `businesses_municipality_id_foreign` (`municipality_id`),
  KEY `businesses_category_id_foreign` (`category_id`),
  CONSTRAINT `businesses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `businesses_municipality_id_foreign` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `businesses_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `businesses` (`id`, `organization_id`, `municipality_id`, `zone`, `address`, `latitude`, `longitude`, `category_id`, `name`, `slug`, `whatsapp_number`, `logo_path`, `logo_alt_text`, `hours`, `social_links`, `payment_info`, `attributes`, `whatsapp_faq_answers`, `status`, `suspension_reason`, `suspended_at`, `featured_until`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1,	1,	12,	'',	'',	5.0316353,	-73.9840082,	5,	'Inggen',	'inggen',	'+573213407772',	'businesses/1/0e33bTgFZVJuAz7OxNyqhrapbIwNZwYeJ0KFhyzD.png',	'',	'{\"note\": \"Siempre abierto\", \"schedule\": {\"friday\": {\"open\": \"07:00\", \"close\": \"17:00\", \"closed\": false}, \"monday\": {\"open\": \"07:00\", \"close\": \"17:00\", \"closed\": false}, \"sunday\": {\"open\": null, \"close\": \"17:00\", \"closed\": true}, \"tuesday\": {\"open\": \"07:00\", \"close\": \"17:00\", \"closed\": false}, \"saturday\": {\"open\": \"08:00\", \"close\": \"12:00\", \"closed\": false}, \"thursday\": {\"open\": \"07:00\", \"close\": \"17:00\", \"closed\": false}, \"wednesday\": {\"open\": \"07:00\", \"close\": \"17:00\", \"closed\": false}}}',	'{\"tiktok\": \"\", \"facebook\": \"https://www.facebook.com/inggensas\", \"instagram\": \"https://www.instagram.com/inggensas/\"}',	'',	'[]',	NULL,	'publicado',	NULL,	NULL,	'2026-08-08 22:54:07',	'2026-08-01 03:07:04',	'2026-08-01 22:54:07',	NULL),
(3,	3,	2,	'Centro',	'Calle 4 #4-31, Cajicá Centro',	NULL,	NULL,	3,	'Cortinas y persianas Daviu Decco',	'cortinas-y-persianas-daviu-decco',	'3214518143',	'businesses/3/KfLEGBVRPrMCKYLbOeU5Oqq2XgE3ywtcFtVExr9i.png',	'',	'{\"note\": \"\", \"schedule\": {\"friday\": {\"open\": null, \"close\": null, \"closed\": false}, \"monday\": {\"open\": null, \"close\": null, \"closed\": false}, \"sunday\": {\"open\": null, \"close\": null, \"closed\": false}, \"tuesday\": {\"open\": null, \"close\": null, \"closed\": false}, \"saturday\": {\"open\": null, \"close\": null, \"closed\": false}, \"thursday\": {\"open\": null, \"close\": null, \"closed\": false}, \"wednesday\": {\"open\": null, \"close\": null, \"closed\": false}}}',	'{\"tiktok\": \"\", \"facebook\": \"https://www.facebook.com/profile.php?id=100063776088582\", \"instagram\": \"https://www.instagram.com/daviudecco/?hl=es-la\"}',	'Recibimos todos los medios de pago',	'[]',	NULL,	'publicado',	NULL,	NULL,	NULL,	'2026-07-29 18:44:04',	'2026-08-03 05:01:06',	NULL),
(5,	5,	12,	'Centro histórico',	' CRA. 6 #7 - 87',	NULL,	NULL,	8,	'Hotel Bacatá Plaza',	'hotel-bacata-plaza-2',	'+57 312 504 7213',	'businesses/5/voSIIIYZLTXCLrx8Vi46M2GPj3aUx6A28f5GVSz8.jpg',	'',	'{\"note\": \"\", \"schedule\": {\"friday\": {\"open\": null, \"close\": null, \"closed\": false}, \"monday\": {\"open\": null, \"close\": null, \"closed\": false}, \"sunday\": {\"open\": null, \"close\": null, \"closed\": false}, \"tuesday\": {\"open\": null, \"close\": null, \"closed\": false}, \"saturday\": {\"open\": null, \"close\": null, \"closed\": false}, \"thursday\": {\"open\": null, \"close\": null, \"closed\": false}, \"wednesday\": {\"open\": null, \"close\": null, \"closed\": false}}}',	'{\"tiktok\": \"\", \"facebook\": \"https://www.facebook.com/Hotelbacataplaza/\", \"instagram\": \"https://www.instagram.com/hotelbacataplaza/\"}',	'',	'[]',	NULL,	'publicado',	NULL,	NULL,	NULL,	'2026-08-02 08:06:30',	'2026-08-03 05:07:16',	NULL);

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tag',
  `position` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `position`, `is_active`, `created_at`, `updated_at`) VALUES
(1,	'Alimentos y bebidas',	'alimentos-y-bebidas',	'cake',	0,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(2,	'Moda y accesorios',	'moda-y-accesorios',	'shopping-bag',	1,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(3,	'Hogar y decoración',	'hogar-y-decoracion',	'home',	2,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(4,	'Belleza y cuidado personal',	'belleza-y-cuidado-personal',	'sparkles',	3,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(5,	'Servicios profesionales',	'servicios-profesionales',	'briefcase',	4,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(6,	'Servicios para el hogar',	'servicios-para-el-hogar',	'wrench-screwdriver',	5,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(7,	'Salud y bienestar',	'salud-y-bienestar',	'heart',	6,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(8,	'Hoteles y renta de inmuebles',	'hoteles-y-renta-de-inmuebles',	'building-office-2',	7,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(9,	'Otros',	'otros',	'tag',	8,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35');

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` int unsigned NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `max_redemptions` int unsigned DEFAULT NULL,
  `redeemed_count` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `favoritable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `favoritable_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favorites_user_id_favoritable_type_favoritable_id_unique` (`user_id`,`favoritable_type`,`favoritable_id`),
  KEY `favorites_favoritable_type_favoritable_id_index` (`favoritable_type`,`favoritable_id`),
  CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `favorites` (`id`, `user_id`, `favoritable_type`, `favoritable_id`, `created_at`, `updated_at`) VALUES
(2,	2,	'App\\Domain\\Storefronts\\Models\\Product',	1,	'2026-08-02 05:22:52',	'2026-08-02 05:22:52'),
(3,	2,	'App\\Domain\\Businesses\\Models\\Business',	5,	'2026-08-02 08:28:37',	'2026-08-02 08:28:37'),
(4,	2,	'App\\Domain\\Businesses\\Models\\Business',	1,	'2026-08-02 08:28:46',	'2026-08-02 08:28:46'),
(5,	1,	'App\\Domain\\Businesses\\Models\\Business',	5,	'2026-08-03 04:55:47',	'2026-08-03 04:55:47');

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1,	'0001_01_01_000000_create_users_table',	1),
(2,	'0001_01_01_000001_create_cache_table',	1),
(3,	'0001_01_01_000002_create_jobs_table',	1),
(4,	'2024_01_01_000000_create_passkeys_table',	1),
(5,	'2025_08_14_170933_add_two_factor_columns_to_users_table',	1),
(6,	'2026_07_27_145859_create_permission_tables',	1),
(7,	'2026_07_27_145859_create_personal_access_tokens_table',	1),
(8,	'2026_07_27_150844_create_municipalities_table',	1),
(9,	'2026_07_27_150845_create_categories_table',	1),
(10,	'2026_07_27_150846_create_organizations_table',	1),
(11,	'2026_07_27_150847_create_businesses_table',	1),
(12,	'2026_07_27_150848_create_business_memberships_table',	1),
(13,	'2026_07_27_150849_create_storefronts_table',	1),
(14,	'2026_07_27_150850_create_audit_logs_table',	1),
(15,	'2026_07_27_181657_add_experience_to_users_table',	1),
(16,	'2026_07_27_211611_add_profile_fields_to_businesses_table',	1),
(17,	'2026_07_27_211612_add_profile_fields_to_storefronts_table',	1),
(18,	'2026_07_27_211613_create_products_table',	1),
(19,	'2026_07_27_211614_create_product_media_table',	1),
(20,	'2026_07_27_230001_add_terms_consent_to_users_table',	1),
(21,	'2026_07_27_230100_create_favorites_table',	1),
(22,	'2026_07_28_100000_create_analytics_events_table',	1),
(23,	'2026_07_28_110000_create_whatsapp_contents_table',	1),
(24,	'2026_07_28_120000_add_moderation_fields_to_businesses_and_products_table',	1),
(25,	'2026_07_28_120100_create_reports_table',	1),
(26,	'2026_07_28_130000_add_icon_to_categories_table',	1),
(27,	'2026_07_28_140000_add_avatar_path_to_users_table',	1),
(28,	'2026_07_28_150000_create_product_variants_table',	1),
(29,	'2026_07_28_150100_add_promo_dates_to_products_table',	1),
(30,	'2026_07_29_100000_add_cover_path_to_municipalities_table',	1),
(31,	'2026_07_29_110000_create_business_attributes_table',	1),
(32,	'2026_07_29_131514_add_coordinates_to_municipalities_table',	1),
(33,	'2026_07_29_140000_add_position_to_categories_table',	1),
(34,	'2026_07_30_022848_add_coordinates_to_businesses_table',	1),
(35,	'2026_07_30_025932_create_needs_table',	1),
(36,	'2026_07_30_025933_create_need_media_table',	1),
(37,	'2026_07_30_025934_create_offers_table',	1),
(38,	'2026_07_30_090000_create_business_verifications_table',	1),
(39,	'2026_07_30_090100_create_order_confirmations_table',	1),
(40,	'2026_07_30_090200_create_recommendations_table',	1),
(41,	'2026_07_30_155517_create_notifications_table',	1),
(42,	'2026_07_30_160145_add_remember_recently_viewed_to_users_table',	1),
(43,	'2026_07_30_160146_create_recently_viewed_businesses_table',	1),
(44,	'2026_07_30_170816_add_alt_text_to_product_media_table',	1),
(45,	'2026_07_30_170817_add_logo_alt_text_to_businesses_table',	1),
(46,	'2026_07_30_170818_add_cover_alt_text_to_storefronts_table',	1),
(47,	'2026_07_30_170819_add_cover_alt_text_to_municipalities_table',	1),
(48,	'2026_07_30_172954_add_created_at_index_to_analytics_events_table',	1),
(49,	'2026_07_30_173122_create_support_tickets_table',	1),
(50,	'2026_07_31_120000_add_hero_video_path_to_municipalities_table',	1),
(51,	'2026_07_31_122216_create_business_municipalities_table',	1),
(52,	'2026_07_31_130014_add_expiry_reminder_sent_at_to_business_verifications_table',	1),
(53,	'2026_07_31_132843_add_unique_order_confirmation_to_recommendations_table',	1),
(54,	'2026_07_31_134135_create_plans_table',	1),
(55,	'2026_07_31_134136_create_subscriptions_table',	1),
(56,	'2026_07_31_134137_create_coupons_table',	1),
(57,	'2026_07_31_140820_create_billing_products_table',	1),
(58,	'2026_07_31_140827_create_payments_table',	1),
(59,	'2026_07_31_140828_create_wompi_webhook_events_table',	1),
(60,	'2026_07_31_170600_add_scheduled_for_to_whatsapp_contents_table',	1),
(61,	'2026_07_31_170601_add_whatsapp_faq_answers_to_businesses_table',	1),
(62,	'2026_08_01_090000_create_user_devices_table',	1),
(63,	'2026_08_01_090001_add_notification_channel_preferences_to_users_table',	1),
(64,	'2026_08_01_100000_create_webhook_subscriptions_table',	2),
(65,	'2026_08_01_110000_create_wompi_settings_table',	3),
(66,	'2026_08_01_120000_add_features_to_plans_table',	4),
(67,	'2026_08_01_121000_create_openai_settings_table',	5),
(68,	'2026_08_01_130000_add_max_storefronts_to_plans_limits',	5);

DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `team_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`team_id`,`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_permissions_permission_id_foreign` (`permission_id`),
  KEY `model_has_permissions_team_foreign_key_index` (`team_id`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `team_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`team_id`,`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_roles_role_id_foreign` (`role_id`),
  KEY `model_has_roles_team_foreign_key_index` (`team_id`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`) VALUES
(1,	'App\\Models\\User',	2,	1),
(2,	'App\\Models\\User',	1,	2),
(3,	'App\\Models\\User',	1,	3),
(4,	'App\\Models\\User',	1,	4),
(5,	'App\\Models\\User',	2,	0),
(6,	'App\\Models\\User',	2,	5);

DROP TABLE IF EXISTS `municipalities`;
CREATE TABLE `municipalities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cundinamarca',
  `cover_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_video_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `municipalities_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `municipalities` (`id`, `name`, `slug`, `department`, `cover_path`, `hero_video_path`, `cover_alt_text`, `latitude`, `longitude`, `is_active`, `created_at`, `updated_at`) VALUES
(1,	'Bogotá',	'bogota',	'Bogotá, D.C.',	'municipalities/01KYZY3HDP872JAPGR1ZMCKEHZ.jpg',	NULL,	NULL,	4.7110000,	-74.0721000,	1,	'2026-08-01 02:47:35',	'2026-08-02 05:31:12'),
(2,	'Cajicá',	'cajica',	'Cundinamarca',	'municipalities/01KZ1RBZBTDEQ4T26PNBCTXF9H.jpg',	NULL,	NULL,	4.9185700,	-74.0279900,	1,	'2026-08-01 02:47:35',	'2026-08-02 22:29:26'),
(3,	'Chía',	'chia',	'Cundinamarca',	'municipalities/01KYZCCGZ38DJKNWNJKWF119FS.jpg',	NULL,	NULL,	4.8623200,	-74.0327900,	1,	'2026-08-01 02:47:35',	'2026-08-02 00:21:32'),
(4,	'Cogua',	'cogua',	'Cundinamarca',	NULL,	NULL,	NULL,	5.0618900,	-73.9792500,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(5,	'Cota',	'cota',	'Cundinamarca',	NULL,	NULL,	NULL,	4.8093800,	-74.1015400,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(6,	'Gachancipá',	'gachancipa',	'Cundinamarca',	NULL,	NULL,	NULL,	4.9911100,	-73.8715400,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(7,	'Nemocón',	'nemocon',	'Cundinamarca',	NULL,	NULL,	NULL,	5.0676700,	-73.8776900,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(8,	'Sopó',	'sopo',	'Cundinamarca',	NULL,	NULL,	NULL,	4.9075000,	-73.9384000,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(9,	'Tabio',	'tabio',	'Cundinamarca',	NULL,	NULL,	NULL,	4.9166700,	-74.1000000,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(10,	'Tenjo',	'tenjo',	'Cundinamarca',	NULL,	NULL,	NULL,	4.8727000,	-74.1443500,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(11,	'Tocancipá',	'tocancipa',	'Cundinamarca',	NULL,	NULL,	NULL,	4.9653100,	-73.9130100,	1,	'2026-08-01 02:47:35',	'2026-08-01 02:47:35'),
(12,	'Zipaquirá',	'zipaquira',	'Cundinamarca',	'municipalities/01KYXRK9FWM1DCWKJC1R4K3Q94.jpg',	NULL,	NULL,	5.0220800,	-74.0048100,	1,	'2026-08-01 02:47:35',	'2026-08-01 09:16:28');

DROP TABLE IF EXISTS `need_media`;
CREATE TABLE `need_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `need_id` bigint unsigned NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `need_media_need_id_foreign` (`need_id`),
  CONSTRAINT `need_media_need_id_foreign` FOREIGN KEY (`need_id`) REFERENCES `needs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `need_media` (`id`, `need_id`, `path`, `position`, `created_at`, `updated_at`) VALUES
(1,	2,	'needs/2/eEtDlEiBSZvldcqp2alH9hJDRwtlEiL0ce9pfP9H.webp',	1,	'2026-08-02 23:55:36',	'2026-08-02 23:55:36');

DROP TABLE IF EXISTS `needs`;
CREATE TABLE `needs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `municipality_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `zone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `outcome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selected_offer_id` bigint unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `needs_user_id_foreign` (`user_id`),
  KEY `needs_category_id_foreign` (`category_id`),
  KEY `needs_municipality_id_status_index` (`municipality_id`,`status`),
  CONSTRAINT `needs_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `needs_municipality_id_foreign` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`),
  CONSTRAINT `needs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `needs` (`id`, `user_id`, `municipality_id`, `category_id`, `zone`, `title`, `description`, `budget`, `status`, `outcome`, `selected_offer_id`, `published_at`, `expires_at`, `closed_at`, `suspension_reason`, `suspended_at`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1,	2,	12,	1,	'Villa María',	'Torta de Red Velvet',	'Necesito una torta para 30 personas que me la puedan enviar por domicilio para el lunes a primera hora',	60.00,	'publicada',	NULL,	NULL,	'2026-08-01 20:23:17',	'2026-08-15 20:23:17',	NULL,	NULL,	NULL,	NULL,	'2026-08-01 20:21:38',	'2026-08-01 20:23:17'),
(2,	2,	2,	3,	NULL,	'Cortina panel japones',	'Quiero cambiar la cortina de mi Sala, la que tengo es de 2mts de ancho por 180 de alto, la quiero en panel japones y ojala una opción como velo',	800000.00,	'publicada',	NULL,	NULL,	'2026-08-02 23:55:41',	'2026-08-16 23:55:41',	NULL,	NULL,	NULL,	NULL,	'2026-08-02 05:57:00',	'2026-08-03 04:43:58'),
(3,	1,	NULL,	NULL,	'',	'',	'',	NULL,	'borrador',	NULL,	NULL,	NULL,	NULL,	NULL,	'Incumple las reglas de comunidad',	'2026-08-03 05:04:13',	NULL,	'2026-08-03 04:49:51',	'2026-08-03 05:04:13');

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `need_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `availability` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enviada',
  `viewed_at` timestamp NULL DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `offers_need_id_business_id_unique` (`need_id`,`business_id`),
  KEY `offers_business_id_foreign` (`business_id`),
  KEY `offers_product_id_foreign` (`product_id`),
  CONSTRAINT `offers_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offers_need_id_foreign` FOREIGN KEY (`need_id`) REFERENCES `needs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `openai_settings`;
CREATE TABLE `openai_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `entrepreneur_copilot_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` text COLLATE utf8mb4_unicode_ci,
  `base_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timeout_seconds` smallint unsigned NOT NULL DEFAULT '30',
  `max_output_tokens` smallint unsigned DEFAULT NULL,
  `temperature` decimal(3,2) DEFAULT NULL,
  `system_prompt` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `openai_settings` (`id`, `enabled`, `entrepreneur_copilot_enabled`, `model`, `api_key`, `base_url`, `timeout_seconds`, `max_output_tokens`, `temperature`, `system_prompt`, `created_at`, `updated_at`) VALUES
(1,	0,	0,	NULL,	NULL,	'https://api.openai.com/v1',	30,	NULL,	NULL,	NULL,	'2026-08-02 00:26:46',	'2026-08-02 00:26:46');

DROP TABLE IF EXISTS `order_confirmations`;
CREATE TABLE `order_confirmations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `customer_user_id` bigint unsigned DEFAULT NULL,
  `business_user_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `status` enum('pendiente_confirmacion','confirmado_por_ambos','completado','cancelado','en_disputa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente_confirmacion',
  `customer_confirmed_at` timestamp NULL DEFAULT NULL,
  `business_confirmed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `dispute_note` text COLLATE utf8mb4_unicode_ci,
  `is_reputation_eligible` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_confirmations_customer_user_id_foreign` (`customer_user_id`),
  KEY `order_confirmations_business_user_id_foreign` (`business_user_id`),
  KEY `order_confirmations_created_by_foreign` (`created_by`),
  KEY `order_confirmations_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `order_confirmations_business_id_status_index` (`business_id`,`status`),
  CONSTRAINT `order_confirmations_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_confirmations_business_user_id_foreign` FOREIGN KEY (`business_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_confirmations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_confirmations_customer_user_id_foreign` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `organizations`;
CREATE TABLE `organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organizations_slug_unique` (`slug`),
  KEY `organizations_owner_user_id_foreign` (`owner_user_id`),
  CONSTRAINT `organizations_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `organizations` (`id`, `name`, `slug`, `owner_user_id`, `created_at`, `updated_at`) VALUES
(1,	'Inggen',	'inggen',	2,	'2026-08-01 03:07:04',	'2026-08-01 03:07:04'),
(2,	'La unión',	'la-union',	1,	'2026-07-28 02:26:45',	'2026-07-28 02:26:45'),
(3,	'Cortinas y persianas Daviu Decco',	'cortinas-y-persianas-daviu-decco',	1,	'2026-07-29 18:44:03',	'2026-07-29 18:44:03'),
(4,	'Hotel Bacatá Plaza',	'hotel-bacata-plaza',	1,	'2026-07-30 00:35:36',	'2026-07-30 00:35:36'),
(5,	'Hotel Bacatá Plaza',	'hotel-bacata-plaza-2',	2,	'2026-08-02 08:06:30',	'2026-08-02 08:06:30');

DROP TABLE IF EXISTS `passkeys`;
CREATE TABLE `passkeys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential` json NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`),
  KEY `passkeys_user_id_index` (`user_id`),
  CONSTRAINT `passkeys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `billing_product_id` bigint unsigned DEFAULT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wompi_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_cents` int unsigned NOT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COP',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `coupon_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_response` json DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_reference_unique` (`reference`),
  UNIQUE KEY `payments_wompi_transaction_id_unique` (`wompi_transaction_id`),
  KEY `payments_plan_id_foreign` (`plan_id`),
  KEY `payments_billing_product_id_foreign` (`billing_product_id`),
  KEY `payments_business_id_status_index` (`business_id`,`status`),
  CONSTRAINT `payments_billing_product_id_foreign` FOREIGN KEY (`billing_product_id`) REFERENCES `billing_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `business_id`, `plan_id`, `billing_product_id`, `reference`, `wompi_transaction_id`, `amount_cents`, `currency`, `status`, `coupon_code`, `raw_response`, `paid_at`, `created_at`, `updated_at`) VALUES
(1,	1,	2,	NULL,	'MKA-1-GKC2BHEPAP7S',	NULL,	1990000,	'COP',	'pendiente',	NULL,	NULL,	NULL,	'2026-08-01 08:08:03',	'2026-08-01 08:08:03'),
(2,	1,	NULL,	1,	'MKA-1-QLTB8FEOEHLR',	NULL,	990000,	'COP',	'pendiente',	NULL,	NULL,	NULL,	'2026-08-01 08:08:29',	'2026-08-01 08:08:29'),
(3,	1,	NULL,	1,	'MKA-1-KNKAZLDZKZZV',	'1115439-1785606839-33887',	990000,	'COP',	'aprobado',	NULL,	'{\"id\": \"1115439-1785606839-33887\", \"taxes\": [], \"status\": \"APPROVED\", \"currency\": \"COP\", \"merchant\": {\"id\": 115439, \"name\": \"John Alexander Ramirez Rodriguez\", \"email\": \"inggensas@gmail.com\", \"legal_id\": \"3146419\", \"logo_url\": null, \"legal_name\": \"John Alexander Ramirez Rodriguez\", \"public_key\": \"pub_test_cKus3zTdsZkkdFTWNQAuIZhBIoRa6tjZ\", \"contact_name\": \"John Alexander Ramirez Rodriguez\", \"phone_number\": \"+573213407772\", \"legal_id_type\": \"CC\"}, \"reference\": \"MKA-1-KNKAZLDZKZZV\", \"created_at\": \"2026-08-01T17:54:01.064Z\", \"finalized_at\": \"2026-08-01T17:54:02.813Z\", \"redirect_url\": \"https://merkamigo.test/billing/checkout/retorno\", \"tip_in_cents\": null, \"customer_data\": {\"full_name\": \"John Alexander Ramirez\", \"phone_number\": \"+573213407772\"}, \"customer_email\": \"inggensas@gmail.com\", \"payment_method\": {\"type\": \"CARD\", \"extra\": {\"name\": \"VISA-4242\", \"brand\": \"VISA\", \"card_type\": \"CREDIT\", \"last_four\": \"4242\", \"is_three_ds\": true, \"three_ds_auth\": {\"current_step\": \"AUTHENTICATION\", \"current_step_status\": \"COMPLETED\"}, \"three_ds_auth_type\": null, \"external_identifier\": \"wMZW2J6sDx\", \"processor_response_code\": \"00\"}, \"installments\": 1, \"is_click_to_pay\": false}, \"status_message\": null, \"amount_in_cents\": 990000, \"payment_link_id\": null, \"payment_method_type\": \"CARD\"}',	'2026-08-01 22:54:07',	'2026-08-01 22:51:34',	'2026-08-01 22:54:07'),
(4,	1,	2,	NULL,	'MKA-1-UXNIBUF1JXK5',	'1115439-1785606946-81496',	1990000,	'COP',	'aprobado',	NULL,	'{\"id\": \"1115439-1785606946-81496\", \"taxes\": [], \"status\": \"APPROVED\", \"currency\": \"COP\", \"merchant\": {\"id\": 115439, \"name\": \"John Alexander Ramirez Rodriguez\", \"email\": \"inggensas@gmail.com\", \"legal_id\": \"3146419\", \"logo_url\": null, \"legal_name\": \"John Alexander Ramirez Rodriguez\", \"public_key\": \"pub_test_cKus3zTdsZkkdFTWNQAuIZhBIoRa6tjZ\", \"contact_name\": \"John Alexander Ramirez Rodriguez\", \"phone_number\": \"+573213407772\", \"legal_id_type\": \"CC\"}, \"reference\": \"MKA-1-UXNIBUF1JXK5\", \"created_at\": \"2026-08-01T17:55:47.376Z\", \"finalized_at\": \"2026-08-01T17:55:48.611Z\", \"redirect_url\": \"https://merkamigo.test/billing/checkout/retorno\", \"tip_in_cents\": null, \"customer_data\": {\"full_name\": \"John Alexander Ramirez\", \"phone_number\": \"+573213407772\"}, \"customer_email\": \"inggensas@gmail.com\", \"payment_method\": {\"type\": \"CARD\", \"extra\": {\"name\": \"VISA-4242\", \"brand\": \"VISA\", \"card_type\": \"CREDIT\", \"last_four\": \"4242\", \"is_three_ds\": true, \"three_ds_auth\": {\"current_step\": \"AUTHENTICATION\", \"current_step_status\": \"COMPLETED\"}, \"three_ds_auth_type\": null, \"external_identifier\": \"aoZqkjoFHk\", \"processor_response_code\": \"00\"}, \"installments\": 1, \"is_click_to_pay\": false}, \"status_message\": null, \"amount_in_cents\": 1990000, \"payment_link_id\": null, \"payment_method_type\": \"CARD\"}',	'2026-08-01 22:55:52',	'2026-08-01 22:55:11',	'2026-08-01 22:55:52'),
(5,	3,	2,	NULL,	'MKA-3-EJJ4KQOAIBTO',	'1115439-1785713959-99966',	1990000,	'COP',	'aprobado',	NULL,	'{\"id\": \"1115439-1785713959-99966\", \"taxes\": [], \"status\": \"APPROVED\", \"currency\": \"COP\", \"merchant\": {\"id\": 115439, \"name\": \"John Alexander Ramirez Rodriguez\", \"email\": \"inggensas@gmail.com\", \"legal_id\": \"3146419\", \"logo_url\": null, \"legal_name\": \"John Alexander Ramirez Rodriguez\", \"public_key\": \"pub_test_cKus3zTdsZkkdFTWNQAuIZhBIoRa6tjZ\", \"contact_name\": \"John Alexander Ramirez Rodriguez\", \"phone_number\": \"+573213407772\", \"legal_id_type\": \"CC\"}, \"reference\": \"MKA-3-EJJ4KQOAIBTO\", \"created_at\": \"2026-08-02T23:39:19.838Z\", \"finalized_at\": \"2026-08-02T23:39:25.882Z\", \"redirect_url\": \"https://merkamigo.test/billing/checkout/retorno\", \"tip_in_cents\": null, \"customer_data\": {\"full_name\": \"John Alexander Ramirez\", \"phone_number\": \"+573213407772\"}, \"customer_email\": \"inggensas@gmail.com\", \"payment_method\": {\"type\": \"CARD\", \"extra\": {\"name\": \"VISA-4242\", \"brand\": \"VISA\", \"card_type\": \"CREDIT\", \"last_four\": \"4242\", \"is_three_ds\": true, \"three_ds_auth\": {\"current_step\": \"AUTHENTICATION\", \"current_step_status\": \"COMPLETED\"}, \"three_ds_auth_type\": null, \"external_identifier\": \"QBheuEaX59\", \"processor_response_code\": \"00\"}, \"installments\": 1, \"is_click_to_pay\": false}, \"status_message\": null, \"amount_in_cents\": 1990000, \"payment_link_id\": null, \"payment_method_type\": \"CARD\"}',	'2026-08-03 04:39:36',	'2026-08-03 04:38:02',	'2026-08-03 04:39:36'),
(6,	3,	2,	NULL,	'MKA-3-WP6NDAD9XTCF',	NULL,	1990000,	'COP',	'pendiente',	NULL,	NULL,	NULL,	'2026-08-03 04:41:24',	'2026-08-03 04:41:24');

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price_cents` int unsigned DEFAULT NULL,
  `billing_period` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mensual',
  `limits` json DEFAULT NULL,
  `features` json DEFAULT NULL,
  `trial_days` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `plans` (`id`, `slug`, `name`, `description`, `price_cents`, `billing_period`, `limits`, `features`, `trial_days`, `is_active`, `position`, `created_at`, `updated_at`) VALUES
(1,	'gratis',	'Gratis',	'Vitrina básica para empezar a vender en Merkamigo.',	NULL,	'mensual',	'{\"max_members\": null, \"max_products\": 10, \"max_storefronts\": 1, \"max_featured_days\": 0}',	'[\"Vitrina pública en la Plaza\", \"Hasta 10 productos o servicios\", \"Recibe y responde solicitudes de \\\"Pídelo en Merkamigo\\\"\"]',	0,	1,	0,	'2026-08-01 02:47:34',	'2026-08-02 00:21:33'),
(2,	'emprendedor',	'Emprendedor',	'Más productos, colaboradores y destacados para hacer crecer tu negocio.',	1990000,	'mensual',	'{\"max_members\": 5, \"max_products\": null, \"max_storefronts\": 3, \"max_featured_days\": 7}',	'[\"Productos y servicios ilimitados\", \"Hasta 5 colaboradores en el equipo\", \"Destacados en la Plaza hasta 7 días\", \"Copiloto de WhatsApp para promociones\"]',	14,	1,	1,	'2026-08-01 02:47:35',	'2026-08-02 00:21:33');

DROP TABLE IF EXISTS `product_media`;
CREATE TABLE `product_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_media_product_id_foreign` (`product_id`),
  CONSTRAINT `product_media_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_media` (`id`, `product_id`, `path`, `alt_text`, `position`, `created_at`, `updated_at`) VALUES
(1,	1,	'products/1/cuL6heE6n8nf5hpT4RsIyRd12mR5BVVlS0H075rk.png',	NULL,	1,	'2026-08-01 03:18:38',	'2026-08-01 03:18:38'),
(3,	4,	'products/4/GQkTRl9V0KvLyGnJSBzS04zDbCQPZoNnL3HCVTbe.webp',	'Cortina vintage',	1,	'2026-08-03 04:18:38',	'2026-08-03 05:00:09'),
(4,	2,	'products/2/RtJmmZ6owTTmg6fYSBcuhRfULcsDYn2p2Ksu1DWH.jpg',	'Habitación Doble',	1,	'2026-08-03 05:05:45',	'2026-08-03 05:05:55');

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('producto','servicio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'producto',
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) DEFAULT NULL,
  `price_type` enum('exacto','desde','consultar','sin_precio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'exacto',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_price` decimal(10,2) DEFAULT NULL,
  `promo_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_starts_at` timestamp NULL DEFAULT NULL,
  `promo_ends_at` timestamp NULL DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('borrador','publicado','agotado','archivado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_business_id_slug_unique` (`business_id`,`slug`),
  CONSTRAINT `products_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `business_id`, `name`, `slug`, `type`, `description`, `price`, `price_type`, `unit`, `promo_price`, `promo_label`, `promo_starts_at`, `promo_ends_at`, `is_available`, `status`, `suspension_reason`, `suspended_at`, `position`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1,	1,	'Desarrollo de sitios web',	'desarrollo-de-sitios-web',	'servicio',	'Diseñamos y desarrollamos sitios web modernos, rápidos, seguros y adaptables a cualquier dispositivo. Creamos soluciones a la medida que reflejan la identidad de cada marca, facilitan la navegación y convierten visitantes en clientes.',	300000.00,	'desde',	'Sitio',	NULL,	NULL,	NULL,	NULL,	1,	'publicado',	NULL,	NULL,	1,	'2026-08-01 03:10:37',	'2026-08-02 03:04:16',	NULL),
(2,	5,	'Habitación Doble',	'habitacion-doble',	'servicio',	'',	227333.00,	'desde',	'Noche',	NULL,	NULL,	NULL,	NULL,	1,	'publicado',	NULL,	NULL,	1,	'2026-08-02 08:09:37',	'2026-08-03 05:06:09',	NULL),
(4,	3,	'Hanas',	'hanas',	'producto',	'Sistema caracterizado por curvas uniformes en forma de S que crean un estilo diferente y sofisticado. Franjas verticales opacas y traslúcidas que controlan la privacidad.',	150000.00,	'desde',	'M2',	NULL,	NULL,	NULL,	NULL,	1,	'publicado',	NULL,	NULL,	1,	'2026-08-03 04:18:37',	'2026-08-03 04:18:59',	NULL);

DROP TABLE IF EXISTS `recently_viewed_businesses`;
CREATE TABLE `recently_viewed_businesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `viewed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recently_viewed_businesses_user_id_business_id_unique` (`user_id`,`business_id`),
  KEY `recently_viewed_businesses_business_id_foreign` (`business_id`),
  CONSTRAINT `recently_viewed_businesses_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recently_viewed_businesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `recommendations`;
CREATE TABLE `recommendations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `order_confirmation_id` bigint unsigned DEFAULT NULL,
  `author_user_id` bigint unsigned DEFAULT NULL,
  `status` enum('pendiente','publicada','oculta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` json DEFAULT NULL,
  `business_response` text COLLATE utf8mb4_unicode_ci,
  `published_at` timestamp NULL DEFAULT NULL,
  `moderated_by` bigint unsigned DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recommendations_order_confirmation_id_unique` (`order_confirmation_id`),
  KEY `recommendations_author_user_id_foreign` (`author_user_id`),
  KEY `recommendations_moderated_by_foreign` (`moderated_by`),
  KEY `recommendations_business_id_status_index` (`business_id`,`status`),
  CONSTRAINT `recommendations_author_user_id_foreign` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recommendations_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recommendations_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recommendations_order_confirmation_id_foreign` FOREIGN KEY (`order_confirmation_id`) REFERENCES `order_confirmations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reportable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reportable_id` bigint unsigned NOT NULL,
  `reporter_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pendiente','resuelto','descartado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `resolution_note` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_reportable_type_reportable_id_index` (`reportable_type`,`reportable_id`),
  KEY `reports_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `reports_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_team_id_name_guard_name_unique` (`team_id`,`name`,`guard_name`),
  KEY `roles_team_foreign_key_index` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `team_id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1,	1,	'owner',	'web',	'2026-08-01 03:07:04',	'2026-08-01 03:07:04'),
(2,	2,	'owner',	'web',	'2026-07-28 02:26:45',	'2026-07-28 02:26:45'),
(3,	3,	'owner',	'web',	'2026-07-29 18:44:04',	'2026-07-29 18:44:04'),
(4,	4,	'owner',	'web',	'2026-07-30 00:35:36',	'2026-07-30 00:35:36'),
(5,	NULL,	'superadmin',	'web',	'2026-08-01 09:04:39',	'2026-08-01 09:04:39'),
(6,	5,	'owner',	'web',	'2026-08-02 08:06:30',	'2026-08-02 08:06:30'),
(7,	3,	'collaborator',	'web',	'2026-08-03 04:48:40',	'2026-08-03 04:48:40');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('02n4Gqtu0eHJtglSaEKo9uz1GSKSZ5clQ7JcK7U4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIxUWxOZHU1cGpnMGR3djluUjZKSWdxOWtlVUc1Z09rYVpvY3MzYXM3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713141),
('0amLEV4ImHS83vfvifJkR66jajbAqsdxtODqfnU1',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPTnlHZ3RhM0ZaSlhwd3VxUERuWjhrbURTbHFoTWsxQ2hVbEhoVUFiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711143),
('0aQvZU2cozfdxlH7ffvlP1s7dtzK4sllzlBYD1yZ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJydnE5RW9JWGtlNFFUdnRPblJqeE11UllCS0pLUjRjUWY3NmtsVkUzIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714480),
('0cDYt8YMSxky6YQJUaCgaAAzDVzQMQO27SF3vXC1',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKSmhHVXZmblAxcjQxSXZUYVJiRmFFYzVLT1o3cWhJVjU5Sk5QREFuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712147),
('0cTh8J1zXoIZaTOl6AFf5eWWrbiLckTKXvLIGiyw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3d1QxTVpQQlFtZFA1Q0Q3SUlCNkl2eENwR0JHWUQ2VnE4c2k1dEs0IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvaW1wdWxzYXIifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2ltcHVsc2FyIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmltcHVsc2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714307),
('0EAZaVd6v2aJeRR0OOSqHdFW96wmGwuXCr8FaEfS',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJZb0hQcUxMRFpmUFJJUkkwQ01ybnYxOUI2UVExZGIzbWZKUkpaQzY1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvY29ydGluYXMteS1wZXJzaWFuYXMtZGF2aXUtZGVjY28iLCJyb3V0ZSI6InZpdHJpbmFzLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715152),
('0F7uhvVfOyADKkT9xBQkIOzVQLEJoN5C4Q4k1EbG',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4WWh6WU04T1hDVGE2MkpCaXpUYnZXTFFGaWhwdWljYXlMMzJWdHgwIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29tcGFydGlyIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9jb21wYXJ0aXIiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MuY29tcGFydGlyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713112),
('0GiIKGgiYmA0L41G5w8gHc2EmQCmpXnto6HutKza',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOVk1QSUpjQkZPbmZXMWt5YkVrM2tzcE10REM2czVHNkNzd0RJR0VkIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712005),
('0lTZ2jpKjFmtJUjJ48FqWsZAzbWBpMTe51paB7Gq',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ1N2p0TmhkNU9MRWVuQ2Jia0J5bHd2NW1kVEFyejQ4Z0Y4ZnpjalRxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711470),
('0OIzXjiHONJqlwrRmB7WhlTg0Rc0MEjHrANd1PVk',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6WnRwRldUbnJ1WndHVlVRMHE5OGo4dzVYaktDMjBzbmZoOU54c0U3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713699),
('0uTx1uRbxi7RdtYOsIWnboykyI8ud3bD7uBjvGPx',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6blNnUkZnRk1qVk55WUNJUzRWdjNHMG4xbFo3TTFCRXNXaUUwUVhmIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712634),
('0veET8KlhP5E1SZSL3Dqr8z6O1J2WeOs16vk4PCB',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJRUzRhSUpWcDBNNFVRVWp4bFFzWWJlMmFtZVg2MjBsZmp3OHJ2ZHZkIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712746),
('0VnMbxVw1pxXEDMfa0lR2DCoota9iO2TMmGuymqp',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJaNGZtT3ZMOUVhSjFSQjZwc2h3WjZGbDBkYURrSXNWMWRORnNLQVlNIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pblwvbmVlZHMifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL25lZWRzIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5yZXNvdXJjZXMubmVlZHMuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715441),
('0zbkhhBZZww3LpTIBxsgZig6HjbFPEHc5wpxmZim',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjdEFIVzl0bHRBVlhXYjFhR2RaRFNlNmJuZ2ZVUFpUaUk5SW9MQ042IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZSIsInJvdXRlIjoic29wb3J0ZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712580),
('15dzsxZDuJc2OR1doww31sV0e9Y5XSJ3yCSxrFpt',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2emZobjJVeGJqcEJZVlZWTVEzUzVUQ2FsbG1WNFJTY081M0JDODY3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713000),
('1aW7ApVeU6NSHAcTIEiOBYNcSnyQ6Zd9egQZlHLR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4TWpZbUFWN29uOEsyUkEybEVtd2hWTUJhVGlZeG9EQ3JwMFg1S3BrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9ob2dhci15LWRlY29yYWNpb24iLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716362),
('1cy9nUIc5hcmCmgFBC1Mfe87aMBNflUFmrJvvu30',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJEVVptbHZZNjIxU2o4bDRVRHRDMnhiWnVvWjZpbTJnUHZIY3R4dGg4IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710910),
('1fGsF1xsu76c8jSoTkE1eAF70LvTjFNxUWrM2I6I',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJpRjdWblFqcmxuSTg1SjdCTWFqTmVkYlQzTk5vRlBTWDVDNGlFQ2xYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715506),
('1J0rJVzyCY358EeWioStAEs8r1AHEs2WcEX4FOxE',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJoZEYwS1luelJqWE1QRGdndUZSdDhCZzYwVXlnTjNvT01ORmJ4QzJ2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712588),
('1qdo80LWj15ykMi1Kwtz1Z9GfBRjyWbgVnUJ1RJG',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxbkwyd2NZUGFkUGlwc05pMEx3ZDZVMGxDVjZac2NIbTlKTU9xZW9JIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712746),
('1SEFIqPwR1zGhLtqhCUjYdplbXYo2nNMUpl7smls',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJndk1mRGJoNnJ6cElsZTFhaUFIdjJkVDZ3Mk5YNk9MWGFQWWk3WkV6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713286),
('1SQhhT687CiROtxZSGZNJTSSVZ1jLqENYZ3MDknc',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTeGFXRmUwMlkxbTFqeHBmQWdmNG9HU2RMVjQ0SHhQN2MydUhHUjJxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712614),
('1uurCKfxbli4qnk5KVAvYpUnW5t2HnvZhzbJ42py',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTM0hLYlBodGdPbDVuWFlOMGEyTGEzU0w5UEttRTc4UDRLc3FVanpCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711518),
('1wnhzAgYSYmJT6D8qE6j1QyYP6z58c1JyBV4XAvX',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJkN1lyT293YUtHMURrWjV5UDVkY1dvQnhPUmp3T05LMHNhbnJEdlJMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712254),
('1xRVKBA1qjg2VWho5r1hgz2Svkyvbq0nQIA8buxC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ3MGZ6UW9HR3R5YXNQTnJ2S1FRS0p0SkVESXQ5NEExZGx3YzZqSFZVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710910),
('27AJeJhwqvXyErNnOkBwmHYm2AVl0tUi843r9B2Y',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJuT1YzU1BFYXZNempkZFYwV3lkSDAzQlVPbTNhVE5qQ1ZZSmk0aGJYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711095),
('2e5NcDmGGYlGLqMKHjyY75ekLdgCBIupOCdAwfNe',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJwU2RpYXRFS0V5YWVJSUdraTBJa0V5Mkp3Z2ZjaHpjRlMyOWYxOExNIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713988),
('2kma3QvtCd2TpRDpyAFcKehm95whGGly0yhtM26x',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqcDN5SmVuR2gwamtiUlZJWmhKWU0yTHhpTGtvREtNTHM1QkVFWnhSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715173),
('2oE9mssnnRb7hqvlBvH6Rd4yONKIgP4dkDwSCsPz',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJIelZPeFVJS25xZ3g2U05iU0xyMVBEd2FTSHZuZTZCQkU1bGxrUFJiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710856),
('2qKMXP0HzlR5ZZ63ZZTglek10c8eq10vgVkr7cE4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJVMEZlZXpybGsycXJrd3VYWHZOeExDUWJia1FXOHF3TkFoQnBNRFNVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714198),
('2T81HjXDjyBJO9d0uP5eqC63sxgXX7XMStFxbFSG',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjWTFVY21kbkVUNmQydjliQllDWW84SkdXRnhDRzRTM1NkUUd2Nkw1IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714480),
('2wkfJmev9ahsLpo5bdybZlhtc8QcoFQrIAM61Pwz',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKTzJBTTVmeTE5Nnp1eEdVOXFqNFVoT3NFcXhnRGhNc3JBalVZYXZIIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714295),
('32JOwAJLY6NKeRezp9BzKY72d9JZViMFZUCyxBpn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJYbUt3ZlRzRG9UbkJpcm5xdWtaZ3U5M2FmMUNRc0VXVWJQaGRpOXdaIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712313),
('3gOLlCsDliKZX5gZGVEkscWyMSwPgGD0GPzKMTq8',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPdUZYV3RNY0t0TXhPRkk2YktJcm80OGtoMlZTbExoY0VidVJxcmUwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710720),
('3mpdp6dS4RecrRifHdoncsVVFd8khkJX2F1IktYz',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJXb1o4TFBGQWpNY1dEMnNuQnFobWMwckJuaW9JZTdoQzE3dWJ2T3FrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711484),
('3MzeEQxd9PgU3SfVltl8dxexxdZMlUIj0R3DpQml',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3RWJpQ0pGc3ptNGhCZDlCV3JXSm1ueEwxZTJWem1EaERrZWU5Ym40IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713866),
('3Z064azbX5PNQgGSwoeVMeOnHtVjbtyuD50ZrFp0',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJpQlhkZmR1am16eXdZN3B0aE9mVFZEcU1hcXBtbFBwcnVHanZWSXdTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716337),
('3zSwlwmkvtWM311KROUBV9Pe60BZGFCB1VgJcGla',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPTFhhVXBCZXdpRzZ4aXZQdmxFdmhIUUs4ODlLS2J5aUVHbFN0TmgxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711451),
('44UOONuyadFPqP69y8OWae4ASQGmqArEfxY2g9G0',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJEczZmYVhjRTMzNm52Y0sxdVNMbU0xdm1xdHNPV3k5Y1AyeDQ0WEptIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3ppcGFxdWlyYSIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715689),
('4BwcQSoo8UfAAxWFpFqvbnqpL0sJlPAzaSrSBwDm',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTbWNsdXRYWVBFRVZ6UFM5V0VQempXMTFxeHVLNGJYT3lzeUNRdHllIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9wbGF6YSIsInJvdXRlIjoiYnVzY2FyIn19',	1785716571),
('4HBCAFowlEcv9OrhV820e7ceEaY6Jm71UqxlDxvw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJsb3NmYm1hVnR4OHdTbmdPeURiOVp1aWNIbVJDcVV5YWswbk1TN0UxIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712972),
('4Rb40wt2rjJ06TDq8lRZWrv9veFxQaT6TtmFv8rf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtc0dtSDhIcE9rczdsMnJzNmJqYlhKSGRVSncwTkdtdlc1UFZVQ0xvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715362),
('4UEia5AAIfIamPCfdmxJkVwzmGkSJ40Ty6NDKRYJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIwY1p0SktyZU1vSXZtTUdqZEh3RTJSdmk2M0FkYjE0c2duWFZwVVdCIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29waWxvdG8ifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2NvcGlsb3RvIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmNvcGlsb3RvIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712578),
('4UM7iFEAACOQrzPEgbAbjyYu8nt1PIw7NQyS2rbn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjNW9YMFRWM3VseEhRd1hKV2pGdEF2azRQbllkRjFMbW1VU0s2SHI5IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711049),
('4Vw3642QVPwWCFKGpzRGAIjaJaeljigPtbJg0rgl',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJHSjM4TXNPakRKVVBnYzR5d1RXdnVmMTZ2aGR4ekwwTDB3bWV2SEluIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711433),
('4xqS4jq6WbFQz75ECBF8ZN7EdKbc3nh3JmvlR4B6',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJuVEdjMDFqc1FLS1FpNlJmdDNZMktNYUQ4Z2l5ZzhUNW5YUzloeDg5IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712016),
('5FQvcdDMQySI4T3vHFhZYBN9XuMqBGq765duToHy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJYV3Y4clhyS0lhekxla3VLS0xBcDhpUGNFZnJscGQwV2xCUnlTVXFtIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714082),
('5lEyODnAL8QUwDBgM3yjAtABe81gdoHCcyNO1CPV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJZRThKd2FLeXNoMXJ4Y3JWemc0TDc3TWhCcTJ5OUVtblZ4dk5LcTk2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713878),
('5PlzTKJsxzzmsQov5QcnUvQaIopa02JjLhq1VSmz',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGTkdsRFA3V3lpZ1VEMjA4RXR0MHU5WnhUQzlzREhuY1BnV29WaWNoIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712905),
('65e1R6tV2qMuzqfZ7VkuraEBStVW0eXbupDGv0oC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjS3Nqc3MyVnJvZXZEYllNellTc0c3QUZIc21mbmpZVEpIaDExWTljIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713849),
('6eCLcE3WWGvbGvqKGiR4AY7cIun7nNUku3SAPjOV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCdzRaNkMyYkpiczBjc2FycmJOTHdNVFR0ZnZ5aEFoV0ZyYVFIbWxJIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711803),
('6funKfW86rKb79XIL2Wz6Jut62jZliG5duGnmWPd',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJoODJYTFpydW9TMzZiUWVtU0Flb0ZsVGZ1STBlbGlDeWRCU0k5UWllIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711803),
('6m41SU3tsm5sRpSiPSOhY6ubMXtaK11zGVSrCamI',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5ZFNxYXF5NzEwSGZ3N0dTRG50a2hTWXpWaWkwNVd4aTIyY2lNWFUyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712604),
('6PyABmU47H8WG1fG2v9y04ZLZBhYedxJVaViADsj',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTMThic2Rkb2t6d0Rxazl5Rm1qNERKTlJBWExHODJtR056QXB3aWxqIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712147),
('6qjnrCitttvjAETWir1C7vC9Fi4f6ug9xXwjJk96',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6YXc0R2V2WjhKYVpSUTFVdXhHZUVJZjRwNDlyRTdQQW5WRnJvZHZkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711155),
('6zorfOTICvCynpCEiaCfzJpvPUnCbYVDdAMH2XlH',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJVGpFbmtxbGJlU2hTNnlGdlA4MFFZWjRkQ25oenh1QjY2SWJBaHJnIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713802),
('70f11Oo6E6dOnp0HnU9WPQYp2VTzBpbHZbcfYBRP',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3akNOVmlXWHBSTTFJbFJkbTBhSDlRMFFNS0JCZXpkSE55aHF6ZlptIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712013),
('7bYtTCuGFGAk6bk9weUbYgCQ01DCpW9Dep26abb8',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJoMmtHakRpRUZmVzJVb3pqOGxtdGlQT01kemx4M0ZUUWE1Y05KV3cyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711154),
('7cJgCf8p44S05XepoDImDqE2AH2V801G1avwRNPn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzM0JMVDdibVpMcWdieU1aZmhyb2o3NDlUd0o1T1E2V0VrUGw5Y1pmIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714488),
('7exJJZJYgNEy6tILgcNLtDtZFHzEP0D7jwanbcDg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5SUJ6T1lqUDlFYlhaTkdWNVdidFlIRVR2dkQwTm1ZNFJnSzZkcGZlIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714197),
('7OpnADn73uewVDKsIXLjzvmJXglmTFqly7BHYNjb',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLaUxBQmg4T3FxRWRHbGJtbmg3d1lHSGRkQnJPRGp0U0plcVVJZHFDIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712590),
('804PWhizCQqtXur2etsT2NwEqv0uQKYraBplLuOJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2WTFKVW9iV0Y2VGc2M2VESXFlRFBYNEhHRG1CejR2Q1BwSlk3UVFPIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712934),
('82MBR8e7bPkcOCMpOGJIOLKKhhHkSZKhobZp8sSh',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJyR2d6VDNRNU8wSUExYTFEUGpKZkdFV0J0T2V5Mm1BRXB2N09iQ05PIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711434),
('84h7Up64eEs1VNzhB8R56hOKhazKpatDo5X9CNJD',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqVXlTbFRNV0VGY05hd1RhRWlDWmhacTl6bkZ2WFdxbUMwZU92TjdvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710995),
('8FhwrpdeZEGOP0A6jCC17L8wwUXk67WOiN1H4UCw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJsbmJ5UXpwTVZnWVYwWXdDSFhMMEpLQW9FYUlJS3g4QXZBTUpOOTJyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711339),
('8H3aMLl4mT0jZOm898KMc3Efj8rKwuRBYVKLYEwA',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJuWU5JWmJTYkl6ckdWYUQzUm5hUVZxamNkcktacW8ydEI1UWtiSE53IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvZmF2b3JpdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9mYXZvcml0b3MiLCJyb3V0ZSI6ImNsaWVudGVzLmZhdm9yaXRvcyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713851),
('8JjjvPxLhtOSlEWCqq2cM6M17Okk6pPzY4RTnfxt',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJma0YxdHFRMXVWdnQ4OXpkbmdqbDFUVWxId0hsTFZqUk95WlJwTnVSIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9zZXR0aW5nc1wvYXBwZWFyYW5jZSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9zZXR0aW5nc1wvYXBwZWFyYW5jZSIsInJvdXRlIjoiYXBwZWFyYW5jZS5lZGl0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710907),
('8LNiaME4A7Eq8ZGYfkO2xp12jRH03FdWLaM6t0RR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5YU5oeHhMSmZsZFJMQ1JkN2tCZUhqVnBZcDVIZmp5bXhlbTV4dmdpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714063),
('9hswDkFLt6v3Tw2PwBN903nCBISEnmPd8H0Rp4Sg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPeml3b2FQSXh1Nm9kbzhLY1pidzVjMWNvQXQ2UFhJNFJMUXFNNXEyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29waWxvdG8ifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2NvcGlsb3RvIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmNvcGlsb3RvIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712588),
('9IwGwgN2aFtLC9yJVUNLh7eaHEN2tV2ICZ0x1ZEA',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0eTJwVXh2VkVORzdoMktCWGJhSzB5SjNiTWxlNlRtelRNUzM0VDl0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9zZXJ2aWNpb3MtcHJvZmVzaW9uYWxlcyIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716368),
('9lqjhPzHD7GkqgVzPo71Scnfa1xk9Ax2DCDl0u9Y',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJvdlZZSGxnSmpxZkdWOVgyZlBTcW5rall5TFREWE12N1VYdFVNVTI5IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711307),
('9qJNPjygu8zbYsy3BKuwSaMGs2w7UyHARikArzkX',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJDWEtzZTRLVFlhTzNUT0FkbzRKRGRRZ2FBNTdhREt4QjRNSmFsSVdFIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713143),
('9zHUAv4w5dgLVoYPylz4MljTNHpxpnVQWaZH3wm4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJYU1g5aDlvSEpNR0RtdXZwdTB5ZDZRQjVSMk5nblJJUTVhSGh0b2Z5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712934),
('AAsJyIYftpkYqYVTvyc86tscOTIAugIN9uadioZO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJnMld4bzRVNFpMU05IaHJBalVIcm96TktGVjlNSHpNZFJiRUh1U01nIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711049),
('acvAkVhWtWji8TrA71rkwhCqRLdy7iLLCfeeea0a',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIyT2d6aHJTNUliU0JrMTUybnZNVlA0bEt5Tk85WHIxSUhqak1kNXNwIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714290),
('AdhwmtnlUj7keJU1zoL2r1WFICFtlAvAsE2WBHQK',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJVdlhpckpuZHVCS0lrcHlHODVFSUMySkNxUzZTcHRlazJzQ3pQM01MIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711070),
('AnBF7gEpRvG15oxSIyEIawOnL5wDaqjNTrQUhumR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJFSGVZbnRUWjk5VkVua2pHbUozVXFsd3MwV0FmcUNmRFJuc05qVldHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712927),
('aPtmULjb20ZKevZG1h5bSRfOdLeFFE3IJr3l5Ns4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQMlFCNjVwU1RvcFkwZmpEcktqVE9SY2ViUWhqNVQ0MnZJcVJRYlM0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712576),
('ataadlQWWbZPRm6ng6qBsxfWuBixWJglvP0p5SKN',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtTzBONEtyTk02alg3akdHcnFhbzhNcUl2WkZ6eHF1N3ZxVXVpa29JIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715235),
('au96wLHL7PLqC1UOGg9VQaxiRTq6EDp17CzqkQjN',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJiOEVtaklLQXhKTkRBU2ZnclV3WDdnNHRpTWJMNmtMRndNVHhkc3dPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711030),
('AvghHDuXyP6Erv9NHC9T5viDxYFBlbgmiEbuM10I',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJSbjdpaWpFQjg1QnhDSXh6b3dQQURDVWpJWEM4ZVVEdHByaGlrY3lYIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710911),
('AYHGaTDbTrqbgXbBJAJpPYVNSAUjdY5SaPoDAibw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJrQzdFT3JNaG5ScUhPSDJnbWt0bTB4MXFrM2Y3Tk5lZ1lUSGN1NWVSIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvNVwvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvNVwvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715627),
('B8JfFse7GK0giAhUBm99cD8VytuzonGyDIlAGfbE',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxT2p4R0p6bUlZMkc1R0dFT3M5RjZsdURzcm5abmdZR2d4ZEtKVHpmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713129),
('BB66UqdknBKjwKvVP3VjQ5fnLxQezWYVT4WRCU0c',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJyV0tOMDBVOUZVbVhyMGVNU0NqNW5GR1IwTTE1TGRROG9QZGVHOUxFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712425),
('BbuQUnv7ROofT659iBZN8c7dtGh3lvhDT4JzxTK7',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqQXlNMFVGaXJUZjRQemxFak5oUllpczBUaEd3U2xzV1Q1dFlWeDltIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713864),
('BdbcP66H7vMdhciwmkQeVAutyIZ5hdMaQjiUQeUO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJobXNxVkJETHJOZUZxT3NYRFZVcndyQVF4bE5HbExzb09JaEFNODBlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvY29ydGluYXMteS1wZXJzaWFuYXMtZGF2aXUtZGVjY29cL3FyIiwicm91dGUiOiJ2aXRyaW5hcy5xciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713121),
('bdhnhH9BRdwWhKNipUhpfbDNERxevXs1oShcCHIM',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJBZEtlRjBGMGt4OXpBZWRiNkpMSmJ6OXdMYmE4Z1BRc2VIWUgzSnNNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712954),
('biC2lnXnLlFX7pQKlZDEQGbjrCJkz10iRs8O0PgI',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJSSVQ0RUh3SHhlbVRmTTl3cmJlaHQwTXQ2VzNNMVFTUkhKMzNxV0kzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712634),
('BkDP3nxzJOpi2yTcGr5wRF4OpHxiyP9dLMCqSMUT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJc3RMSVVjN3VvTldlRTB2akVVbGdGdnV2NVFFcTZWN3RxSnhzTFJlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714302),
('BkIEddtbQG10dnrjbrJurptk5JQzp2BY5ljb1dhT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2dXFvQlNTT0VBOWlkUzQyNm9FdmJHVUJ2akRYUlZROHpmV0FLZ3NKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710912),
('BKQAVPS5doj2JaN4No9Ki5J5hMRNrtvpwg0Hmkd6',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2UDl3Y0xSR2hKS1RLbEJNWm9lWEF0MU16aHViUmRLVmhxYVVuN3NVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvaG90ZWwtYmFjYXRhLXBsYXphLTJcL3Byb2R1Y3Rvc1wvaGFiaXRhY2lvbi1kb2JsZSIsInJvdXRlIjoidml0cmluYXMucHJvZHVjdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715490),
('BlJqhr9rWrr8MJGFrrur6zaes5oaVkFh1i7P8kgP',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4RjlRem5YcENQWlBIYTJJMTFSOWJBVHhWaUExenByNVMzd2hGTFhqIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712579),
('BMbj50rYTnZF4kCUQwVQquVVuBo7lRmFLC0SKeZo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQWjgyUnlWY0hmRXg2ZGRzQTBhdWNtRkJNSXdiNndxVDNuSlg2SnZSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710719),
('BmZR6LoZzDaALt3N1MZHh1mCtvTvSz2r0t4KIMob',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJraURQZzd4UXBRRVc4UEVzUldNTzhDQlVkb0pCYzV2b3dEbUNra1BEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716377),
('BOk3PHjlhsxwSOI2NJcmIlpDRUrE4AvOwXmETafo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCMXJwWEw1T011U3MxQWhiZm85dUJrTmt2NzgwSU1uc2NEWTllbmVjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL2xvZ2luIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715441),
('bqbH6UMZn6fkIgrC4d5vkqqYvsDA4Ykp1RDmKsb6',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqWHBDM2pWZFRuTHdKY0dxQmRCNUlrbUhzTUhpV3NqV1Q1aGhlbU9kIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL2NhamljYSIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713819),
('bqdk7mKUY7a6fuUsPNXzZdrFfTaN1muX8NdYB27X',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJyQUpZczA4elBEeW9HMGJnTWpmc1lvR0dYdzh2dzFTbVU4UmRWRldlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711484),
('BQgVjw0gS0KaNdRuon2jQ0EoDFZy7n2JAzzVqDgJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzU2FBckwxcGsyamRoWktLTVhQMERUTDl1eDBxSTZnb0pFMkIzQjlKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715176),
('bSLEH4oFd9fFDQBu2Ht0wGjYSqugQgJbcHYMdkAW',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPVWp0bDRHVmQ0MENlSVNrTmRRM2lWYWJCRkZxcVFxZUJ4VlM1ZDhqIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714859),
('bYd7XGXycIvHJqyGNaT30nO3fz3yDwoCw6ONAQ4L',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJsa2FLNWlLbVNNV0FhOFpJcjRSZ1JLa1phanBMV05iOER1VW1Qb0JqIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711803),
('bYo9UI0UkzV07n0IIOXM1uRT60pXPpwlrNUGLD70',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJuWjU4NGNSRGVzY2dUdmJCQTRjQkRVM1c4d2hsRkhxV0E0b3hvTkdMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713396),
('C1mJhFjLqwzANSATrDHj3fNP6atl7XB12sd0vpQp',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOSmg3QVViTGlobTB5bzg3dzZNY056QlhsS3hDOEpqY3phM3ZlYk1YIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713141),
('c72JEXPG5w44z3gqeC0jHvoJ3NQzEQAuPVnRP64c',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLMHFLNzlSWXc2ZzZOQUNhOWI2S3NrTEJ4SFE0Y2dYQldORHUzaWRYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711306),
('C9v6YmedFVnjBJUtA0Ehb1p88Bcfsx8dbpPN57un',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ0VHVHak1RUGR3d3hnQjAwTVpZMk5BVDR0SU8zaWVxTnVxU3locnR4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713877),
('cELP807YrUmk62Hdlp2qt9uPeddq1mmqWUI34MfC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI2aTdqRm5aTURMZDRiVU9FdUFXWUxnb21vVUZsVjR6TlZ1cHc0d2s2IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711299),
('cHmnUeb4YBgfFM57XdQDi3bP40tvdySyopQgFXPr',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4VmhxVVU2QVBFSTN5WGltMFd1OWtETGxKMEROdkhac0w5QkdaSk5zIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9taXMtc29saWNpdHVkZXMifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbWlzLXNvbGljaXR1ZGVzIiwicm91dGUiOiJtaXMtc29saWNpdHVkZXMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785714568),
('cOHfEOQZee1rMkNrqC4qwaV8wXXb2xOmOJZgu9w0',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqdmp6RXhvajRHSVBqdG9URloxbjFKSmpuME5RUlNzN2pwMUs2cmM3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712577),
('cqMbKb9UB3oH8ushs3ry1YsWMo2Ryp7Dg2ifugqf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLNjJnSXNuVVNBVEZBbVBBRHhEYXBrS3pZQ1FqWmFuMXZwOTUxT2xWIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711803),
('cshJG422XQF4djCjUSLstbDJf3jzITHtqhdZyHFJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJwaUdkYWQyUzFYWTNUSHpTcGh3YVZuWjRROTEyWHRrdzNLME5BaE5kIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715354),
('czY6MwmTZiN6AclmH9OI3CvMjXNM4FoKlijaYWQT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJiUkt0cEhPTFB1OEJMME9vTW44Tno0dGtIelVSYXFReFBSelQ0dmpiIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9taXMtc29saWNpdHVkZXMifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbWlzLXNvbGljaXR1ZGVzIiwicm91dGUiOiJtaXMtc29saWNpdHVkZXMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785714036),
('D28SW38myoJYkkGPG74pUiTU6Kjl3vyUWLHELnLR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCV2RiMWNDMHBNNTFPVE5NM2hvNjdNVGxvanBkdHZRMlozQzl2ZHZxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711155),
('D607XJE1cNSQWh7QnQyHqe1JudXYFcbusEcLHDWl',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJIVEFGRlJqWG9La1R1MTVzcWdLNDZ6TTlyS3VuZXB5T3R3RHBtVmF6IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712577),
('davJabHIXY04MNDaxZUAQsO5HaacHnCQOign3XUq',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLdFJZdUlMdkkybEk3Z2xMQ3pQTTd1bU41ZU05OU1nWXZnMHk1Vk1xIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712590),
('dd6LM918VQjbCWkzv3MQ0SzrEAW6dQpmqQTuGPGR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJZZktxdGFzaUpDdDVDcGdtdTVrdTFpV1FCQkJQVjBsOTg5T2xnMTFHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715510),
('DE4o3sOXuNdacPZ2CQ0mBnLazlKPCZkeC7T5CeI4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJkOEZQaWtuRDNuNW9ucTBubU96aWVvZ1FHMmhGRGNQRHJpd1l5Rmw0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710934),
('dgnkiyI1TU9sOoNpIWFOWofmxQiMyhlfzZcryS7Q',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJscjVQNmZSdWkzQVdYMk1aN2JXVDBIU1gxUzVRaFdHU0dQNmZTRUNaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712955),
('DhS8JZ8LcmlybE4HI37rIIk61wmiaDH7ostowFwK',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIxSEZVZjZDTGtjUmxJUW1hNVFGcWpQR2ZnSU05Njl6Vk0wSkpvZmpUIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711093),
('dPXeovIoLyM3rnfdanjgyGcccHsCIChjNKqsssft',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJWbGZzRTNNOXJGRjI2UnFmcDljZzRnMktYTGZHR1dRTjZXRGw4RFpiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713355),
('DWpZDd89f2hQ13Xnq9hrZEmmdJB96ruoLAq6fy2Z',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLQ3FiSm15amtNdWpjcENJRzVUelFCQnlyblBuc0VweEFHNlBuVmtpIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712629),
('dWqPOKMXOjs4ErWpDAkL0Zb0Ks33Z6PUT8O0IO45',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJFUnJJOGZCd29HTDRDSng4ZlAxdmU3Q3gwVUxxTTBxcDFzSGdoVnpUIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711030),
('dxFWPiiITmEQ69irn40byPbZwRvvinvGGJ4E6Y74',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxZ1haM0VoaXBaak16RWNXbFduc1VNMXhqcUxFdXR6Tm5TTkc0SEVSIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713858),
('dYKfG05UJ9qIm5cIHmwcW5uO3EHjzd9w3Hb9B80J',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQUzE2Vk95U2FkTDE3bVJNVWVoNzl3Y1ozdTg0UGNLbDA2Q0U5YXM5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9zZXJ2aWNpb3MtcGFyYS1lbC1ob2dhciIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716358),
('dyzwfzObP1fEQeeaiL8xeVQO0LViEXKgWQrh2vdq',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI1SlVkOUxxY1Q1RE1PU3ZleVRKd3hlRlREMFBlcTIzN0g0Y2ZCa2gyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715173),
('E1KwWyYl5hFfPd5GAThEeYuENhy8Luxj4xY96uXO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKZ0toM2VVQ0U3VnNRWEhxbEN6bDdUQVF2eFVPanlVQmpUT1FUMkxuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785710859),
('e2SyzyT8L7GycUvSuEv42HX536zzmtJVSn13oYwg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJMWUw3dHJxaXJpMFpBRkN1elc3ckd1UENaZklqZ2V5M0ZqZEFvSGJlIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9waWRlbG9cL251ZXZhIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL3BpZGVsb1wvbnVldmEiLCJyb3V0ZSI6InBpZGVsby5udWV2YSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714040),
('e3k6TmxwPdd6e5d1Ivg4Ut3Yb4Ke1bzZ2uPbggVT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJhQUhkQXI2UFVYTWtPOVNpcUI1TlRKeUt0NEZrR3Z5aXpSelZhbG9PIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712148),
('e6dcAjQsoFCgSSRJy9IXGZKbSIGaPlhfDiiFayDD',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJpdWI5VmZVZ3g2OFhETWtaaUZMODRPZTUxRUtqcUxKNmVZYWN3alZZIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711797),
('e98ou3081xZb0KfCjbncC8mxZ0Jt2BBOxdy5jz54',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ3eEMyMklqaUlVWERhN3RCbGhEeUFQWVBVaTJUaW5vOFE1aWhTdmlFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9iZWxsZXphLXktY3VpZGFkby1wZXJzb25hbCIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716356),
('eANbXLP77TRHUPlVXG05ilkcHk0z340GQbQ6R0jk',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjdVoydnRhVFkwd3VOYzNJeVhUWDVLbnVYNGpzQ0pUTVkzS0RUWUFBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711803),
('eHu7OcIxJoVT8XOkccSna0xk4AectMBWm4jb9rfy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOeTRWbWVGa09vbEFYMzJmbGxZZHNVQ1dHYVNaR0lXcHFndTR1NWtIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc2l0ZW1hcC54bWwiLCJyb3V0ZSI6InNpdGVtYXAifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713051),
('EPaJ7fyO0KpF25i2ACbskSODGINxocQu2X1SbLdh',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJbHo4RWw3cTU5ajJ3NUZLN0laZEFDNjJONGZJcjlSTWZ3WmZPVDhGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710913),
('EVkeUp3XAvEISBdlYXXxDVCCn9PtqGaipOzfLesv',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJWVzFnQVRGbDR0YlpCM0dXS0tmRzZHQ1M0NEo3Nll3RHh3Y2w0Wkc3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785718466),
('ezlPH5iGVfFfwh1U40P5Tfjp7548heKemZZZa6u5',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ1UXdqSTZDeGx0WHAzSHRlODhRVnRaZzF6czdNc1kyYWphSW9LaUVCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712627),
('ezm2wsLFv5W1vMlwQLxx5ozA5TRowDdrkOCLyXnL',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJEMUhua0g3dGludUtYbGNkR1FpekttQ05zS2JhSlJZRElsVlFYeUh6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714474),
('f0LO5JJ5We4KFWGMKENLKGG05DNHbknDebEXzU1m',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOYjhpSnFqcFNZV01jR0pYbDdkbUpPajZYbGdQTmNiWVZOOFlzMzlNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711202),
('FAqOFS43hHzjNv8Q07v9KnrROAE8wBkNhIBfrmK3',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5TE0zcjVJR01zajhLZ25LTnFGUDdUZ0l5a3R2M2o2SUN6bWdmaU52IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712897),
('FAXoTfgYjkFp2cQloBov4KY7iFJrkH3Z5S4s8Bq1',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJsMGlxWUNPWnkwbGdYYnJNYVF4blFjRUNxMW9JQVE4MTU5dmw1ZmlXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713229),
('fBsJ7v6loSmuxq2ermkwEqWSU8AOSA5r6Ezb0RLn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ5M2xEOGtRWGduQ1h0T3FwcmpqSmo3b05WWWt4VGt5eHZ5d0pFQXZzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711797),
('fEbN6fKvcftz3ru9kS3OdWccOV3puLZiWthvTyzS',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2cmhRZk00MG9MWnhIVHJVRjFSank4Yk84aFZCY2N1TmpjUlJBSk1JIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712013),
('fEdJ20jUBEdpT6v70zrnMSvSmNtdk2SqoMFHWGCE',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5TnI4U0JmeFc3RkRkaGxOWVlTdDhiRUVHU3RuMVZYT1RzdjhsOXdXIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712897),
('FehKFh815EtImQ0HRhmiXGRNuWZcXbEiwl18uccS',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJpWlZvZ1NqbmZSTVQxalVKcURmMWhQS2NPMGt4Q0dhMUNZNGIwY25VIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716342),
('FiSeKob4HLBF8aPinshQZwKBrJGgUWMQBwmeHhxg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJXM1NIVmE4ZlBMTllZNE9tZ0U5TkdmSGR5TUdpdW1tcVNTbWU4RlBPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715513),
('FL2NmgDLH4Sk1iVEPx1iMN6QK4JT26L8KPyniyar',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKcXVPblkyRXF1anA2VzQ2OTZCQ09CVDhGclVtN2xZa09lWjlpYWdtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvY29ydGluYXMteS1wZXJzaWFuYXMtZGF2aXUtZGVjY28iLCJyb3V0ZSI6InZpdHJpbmFzLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715129),
('flLuB5yGTFxAOW54djEYNyjATPmDOgTbCNuH8Nzu',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJXeFBuRG1tNEhPUXZqdGFsRE82anR4U081ZFVKUWhPNldraFN3TkI1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710840),
('FriJn7vb7btSG6UzbgHgRuVEo4y6oMgwadH7M7iX',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJwRmN3MXltMmxXY1hUU0xiQmlNV1dvaXBJNUJPQ0ZiTXljT0gwQkN3IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712631),
('fSCF725Ss1NDq3FzTA1oShJbJ4OT2YirnMlrBDw1',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ5YVFSTDdZR3dXdGU1ZjRBWWw0cmtlR0NPRlpra200eEdJT0ZRa1VKIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711474),
('fy7xntKa0dvoXHAaeE8glUvBJU71GAuItS972RFf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ0QVc2OWt1Yk1iNEdzeEZkeDFXelRGc0lDV28xOUlrU2h4aGtJRkZVIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712576),
('gBDr30gM1f4RXtOUTbCA9sUewx8rfsRkK1yPfqkb',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJeFUwN2tuVnE5TUU0UkRUNUdHTGFzS25hNnR1VlVtVW9pY0RNcW1OIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712016),
('GdXPemr1vTpHzwdDgCrLv02hUPn6kP1A4WpMLSz7',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGT3JRSXA1S1NiVjRHM0pBcU53OUV2bWM4clkyM1ZVVkprbWJQOFRsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713067),
('Gk5c9H1eXJdTGPzeNZiEn6jI10EAENLQBwrQi3wF',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtbVQ0SGtKSXZRR0dSTVdaeHJKUm8wSUtaWkVBU2hUdFI1eXhybzdaIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29waWxvdG8ifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2NvcGlsb3RvIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmNvcGlsb3RvIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714302),
('gpztRMF6jIGyKMj3DzcFOEb5UHpam9pVjXpVNdEn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLSDZRRDBMbG1Bb29sakpxaFo0ZVZSSXBYTlE0TWp4T0pOdzc4YjRoIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712254),
('GUP6ym1j41raaDcFe0CzhtkqpmksVsbcE9MEceqr',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5WDJQOTlGZG1XU1ZYRExUQ3c1UXg1YVNQOG9EalpLUEpDdFFLTjZxIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712579),
('GWhBeZ7zy0jqmdZIGHuZj9heLMXPkbGIjAuibYZU',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJVNEh4OHhlNzVwVnRSNEttek5uRDF6T1ZvQUp3bjhQZE96elZMbHAzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713093),
('gyNoGrHF5rYjpXfQvAcWnFSXLadNceOl02yHo2Dr',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5eWRGbjRDQ3BOQ2dZbjl2Vmt3Rzh5ekdJUXlzZlFUaWF1RnhEbmhTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9zZXJ2aWNpb3MtcGFyYS1lbC1ob2dhciIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716370),
('h9M4bnMy78IwadfTwdps1QTTATiSDoIznjbgUm66',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJwSmJUNVo2aGJlZ1lJTDBNaEdPNHBTY1Q4cWNsVFhlc2JlVnR3MU9vIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712254),
('HdyuCT8s2v3plvZIZoKpqrv4uyd4NHdrxy7dmvBw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjdDc2Z0pTN0xXaE83TnJmRVFUS2FGR3BhZW9yYjVXVXJ4T2ZSSDhWIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9zYWx1ZC15LWJpZW5lc3RhciIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716371),
('HEbHTU0JfEO9u2ipiwCJCFfbbSpyBDhYz5PWanAL',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJRTW1kc2dBRDJzQ25od2RnRzZ1NFRBbVl3SXQwaUVsdWdSb2h4eUdxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712313),
('HEp3yk9KZK7xbbcvuFb09d6VGZDJrulvZYVm5xbt',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ3QUZmRjBvTDFJYnR1ZERhV25SS1JrMmpIV1F0OWRGUHJadno1WndWIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713847),
('hFW0suhcHIGff8NMZvDM06umrNnz01nbyXTSGjDe',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIxdkJFYnZKOGR1c3daYUxqZnpMU2piVFZEak1zNmZ3VXFDZW9HWGtPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712313),
('hlSJIsW1JtPiaHLIfEN8VRGqFkYm0LhkD0JqeE9k',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJuU3dwSTVWWXFvVHZjalVLem94VHUxVGNuQ3BKY1F0bVluQVZLdkRKIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvaW1wdWxzYXIifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2ltcHVsc2FyIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmltcHVsc2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712586),
('hqyLlm8sOuSDbWyA0AEipuJQCOobhkKtyvcLnHDD',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJUSDJxdm03UHMwTDgyMnlvdlhOZ3pGMWUyRjRTcWswTHhTNDU5UGFMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712579),
('hSnQYgH2mRukSpDfiaDCPbYTOYASPl0y13fftKPn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI2WlpFMGZXbnB6ZG8zalYyZnRBRnVLQXpIc1ZEbW5zeVlXRXF2SHlVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711299),
('hsPdWUmQtxZydilLiuWPXbrKyuGBye9ubWtPpAaz',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLTTVwRW5hZEtZZ2Y3VkFYNnZzTzZuUUQyYkF0VkVmQ01MYmUyUXU2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYmlsbGluZ1wvY2hlY2tvdXRcL3JldG9ybm8/ZW52PXRlc3QmaWQ9MTExNTQzOS0xNzg1NzEzOTU5LTk5OTY2Iiwicm91dGUiOiJiaWxsaW5nLmNoZWNrb3V0LnJldHVybiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713985),
('ibiZ3OzkpwWo8PAsBeqUZdPu89wD9oR645idPDoy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJWDFmZEZBTjNtcTJFa29jM05XNXZWVHY2TVZvQlhRYm5Vd1pEa0dLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712195),
('IDKtEYhchzFKLMUTjPl02RUqKhnb0Wh9e9TY1Xgo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJUUm1iZ251TFpoN2xSc2c1VmRPdXFFZ0NMSHJucDQ1QTE2bEpiTnNsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714082),
('if6IAkKkFoPqVqfUkdZ0FJNxlZW595lNTxrcGHrJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJFakRQR0ppTHpGNVA4dnZTc2VSTU40TXoycU5iUGI5RkVQbFpwM0dlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713430),
('IfamFbts5xB8yXG32xUFsZq5wNnNfizZKHLristb',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJHZlBZSXFqaHNTZjRtZVZHYm9FVEdlaWlwYlNCeDIyY2s4bGl6bE5oIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714230),
('ih5aHlSCD1kOCt789EaAuJxbtuXVEbFNkCVpzAAU',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJZaGszb0R4dHcxR21BNEZBaElNUFZraE5lZEdOQXFkRlFCdkpJams2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711449),
('IhfTEzjKHEZSwMRuALXsbPHkWusX2jQN6H42946V',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqQU0xdkE5Sm5nak5XN003WVd2bExlTVZGTU95Z0xITVRYWER0NDZLIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714343),
('IHiyvD7QkLL439WUnrrFJ95Ap3vqWQusltFWgAXy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2Z2YwUnJoZHNRcEhLRE03bjZXeEpxaGhvM2pSR2RqS2dDVXhsY0ljIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712586),
('iMyJqSOs3O2VBd42BuMWTHlOc4sfSzurE128Ib1o',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJrNExUN2ZicGFoZnJNdU9EUk5NTngyVWRZemcyWmgwWDZmZkhnbkFYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711444),
('IOZbfNVgXAns7dB7Cr4Olrz6XlTP1Dpx5zBveiqq',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtdk5aTk5nUjNQRWtVU3h0aXcxajJMZkpNUklJNzFOSHZwa3pZdUpJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714133),
('iqzkaBoGjnT8Fx3MyJdI7322oIq2CKq3b4iVh0Ut',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJlc3pIMTNySjc3aFBhVVZsRlBOWkdGcjRwOU9ubmgzb3ZQeEw0ck0wIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711307),
('isYcBPhj04smN7mGGPEvTAEDe9tnjkoAKqV4pKEu',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIwS2ZVdWZZUG5meWtRclRYTXJEOGNXYkNLMU1ybFdlWGtEZXhDdVpzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713565),
('IWKCl3ZebhvsJPBHgXKTUtKEp4NkJrWt0lghVxHB',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxRHdUM29panZNbUhLN3htV3B3SWVzVmRTbzMxdU9qNjMxTTJMN2NJIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714080),
('izTeqQutU1HANKHyEOOwzKPmWEo9hm2zgKHM89MG',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOa2F3ZElISVpmNUFjbW9mU2hHQmFVM3htZnBWN1pTM1RiUXA0bWo1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714873),
('J7zh24PlXV5zRiJi2v5IqRuozxaiO2p95g8k5kUj',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJFSnFYQ0VHRUJ3eVFJeXF1aWJOQ2pCRVl2Q1dTalp5NGxWMk5RZlZsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZVwvc29saWNpdHVkIiwicm91dGUiOiJzb3BvcnRlLnNvbGljaXR1ZC5jcmVhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716324),
('j8G2uPezSj1v4CKB37BrqEuBmbjyoFs166BZFxBm',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJuZlVHaFFMdExSOTBna0lkZ2JTbEFmR3lrYUtCVDVQZkdWZnFyTWxsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711155),
('j9fihRLSjQybTpwe7wtyQuSC8OeS0pxoHKHEeGuk',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGd3RmeDZmOTRpck9BYnVGMXBMSHpVMm1EV09BTkp5RWtFSHhxNFJYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZSIsInJvdXRlIjoic29wb3J0ZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714028),
('JAMyT8hmowQ5iOBc27klk1qGVjuN9iWUSK1xgXfj',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJhQld6ZUJmQm5aZzlBbE0waDB5aHBLUWNVUVZ4azRvcTBkYklJV0ZCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712897),
('jBtD6ZIpjr2naj3qeOkIWwXA8QGrdqBG8Y0VfKLC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJUa096Qktpa3NybTJmUGNYUExLY1VuZzdUS3EyMzBaSGNVZmVlV3BHIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvMlwvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvMlwvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711339),
('JE8cunXhAJ6alulBX6olGwfOnefGNRrLR0IGdN9j',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJBNTlaMGRVSHpHaFlqYTFaN0MxQmtwaTMwYVpwYTdUQk94eE1RQzhJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711030),
('jhp1nin79LbLKSGv6axtU82yMTOBWvvXHusR13QL',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqRndMUUFQZlI0TUNPeURPMnprWnlSUEhHQVFjQWJBOFBwOU9weVFCIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pblwvYnVzaW5lc3NlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pblwvYnVzaW5lc3NlcyIsInJvdXRlIjoiZmlsYW1lbnQuYWRtaW4ucmVzb3VyY2VzLmJ1c2luZXNzZXMuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715438),
('JJJKtA1hKutsQbAs5bfLwqaR5O9T2wb3uAt7lMcE',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJBMGpSQU94dmRyaDZNeE01eXczdklrOVBZYXdraGxSSGtNeWtwazVlIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713863),
('jLLZkgdg5gP6NKkvXhkHUmNvKW4NEXWwWrntauTm',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJHWEFJSW4zMXNzUTdQNTZEVGlGSGRtRjVPaXVJaTNxYkVRdDlCanN6IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712101),
('JoRo1ybjSDGAjczU4nc6ufXkPU12qJFIACrGyr13',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJiS0IxMm5JR001U1lTTnNWbFJYWXZZWFdHTUpSUmFjbFVyUUZNdVBYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713990),
('jtZsSEOd7bDzZAqxtzzei6IBcTutw7DviZfONIpX',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIxMkl0dUJUS2dJckxjRFBHZHVZYW96dFNjVDFudkcyTmZHQWpaQnBiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL2xvZ2luIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715398),
('ju0z0hcvcekHofEMbqqe5Rvs3gq67xNm7XsbbXlB',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIxYjlKTXVCWW0xakZmVk0zdUFHUzBlTkJQTmptSUdVNTJQUVNFNnlYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715404),
('JuKHcbiB3loX4ssekYZ7xpEQMyTU39D1suKcyfJK',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3SW5oajFoc3lEUDJuc0duMjFncVk2WVlON09aWG1mU2NtQWU5U2YzIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29sYWJvcmFkb3JlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29sYWJvcmFkb3JlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5jb2xhYm9yYWRvcmVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714508),
('JUWeLRWR2TsWvGU0LJ4e8PbbkmqVqUtdbtA9dhLC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJmMkhRSU5MdVJsUnpEQkt1dzhvWlB1QUJkd3VMV2NRZHpmc2VScU9vIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713375),
('JyckQJBKkEle3ZMKC4Gx9qvSKQI537fnn1ZK7ttO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtbzdPQ0p3NlRJb2xQQ3pWaXFaRzJpTHdiSk1mRng5RFVCcFJNYzExIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714295),
('JYusYwTYazi17whkU9KbvbirLWEjGF2gB0iBc1w6',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4aUxkQURvaGxGTXpGQ1lKSEYwZmVZejBhNXVOeTliR1hwckliNmRyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714056),
('KCbrUbysGpkLGcJ7Da048jTAQqhd5oSpHxIdWM2N',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJhVWdiSEJ5aGtkMG02U3VzT0VEYzRNczE4NnJ5c3VodzV5dHljcFpYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711434),
('kEJGLj5XsFDA6KDwrALw2QU6kjQT96HOsQjC7Yri',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzUGxSTnB1cWhVWGJaVXZqQ0Q3NFN0ZXpZNmY3N2lqUjhSMEtCMUdPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712195),
('KiBWNeQrdNtbZVLgUK8RNh7HlgAzerVr3lkBMEWM',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2b0VWSFBId25iUTRWOWVHcWF4TjA4Y2VrQmNWYXJTM1RhZTk2amZxIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711484),
('KihHuAUprWoQiSqSROl6OCD3mbiB7KUb36wWtSIp',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIyVDJzNFJQdXdqcXppdHM0VzU4ZnBQTmdRd0Z6enFsTjlxTHE3bnlsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvaG90ZWwtYmFjYXRhLXBsYXphLTIiLCJyb3V0ZSI6InZpdHJpbmFzLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785714887),
('kjfTbLCnPJKLxIQ1gAK8rGJ0hEzQASIHrtovgMmV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJzTXRRd1kwaU1qZXR1NzRmMEtHNHltM2E0TFVpbmlSYWdrQ2VSU3A2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711451),
('kJNMh1bHUbQAflI52zUMJlQ6lINy4d3EJhC10fGo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQME1wS2ZNMGJmY1VkTHBFV3dkcU1kU3owdFlHZWNpWnY5MjJGSERyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711308),
('kkto02g1UlcKfwM0Po1VAKah7jZo8P6ru2n6NCuX',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ1RTM1b2pZZXVBOFlrY0JjWVpxZXo5dDNpSmNGSzlyV2NGbGoyM3Q3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712917),
('kO2ZcPYEBVFWUFVdYa1Nk0uR728PH0rz9Hs2ZBvo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0SFYwbHFKanBpZTRycFB4eUloYlJhWkk4NjBLanFhTmRJZWxJWlBZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715401),
('KpWmjFEwFME4iTtKfbxLSl5HeMQsVhYtSB05vrgl',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJrdmVneHBrRTFXemE2UDFjQjNFaXVRR21WY0NvalhaV3RRdzhiR0hjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785718466),
('kQA7JZwG6jLocT9fHXZjTVYsV2fjTJyqy522LwDo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJaUnBMdHI5a0ttbTBtYlcxNjl6cFZrUUMxdXZrVEhRM3ZYV2l5eFZnIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713093),
('KQehJpD1igs7GQu27vzLIxHsm01eQH2pkiKxqkqk',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPNjF3S1NRVUV4MGZFejJtSW10c1JjWm94Wlo1Z1plWDZtTzU5MHpyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715215),
('KQSYrEbnzgohP923Zq8ys7UlUUiPseltfBb9y4Pf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIweW4xY3l6RzVsc1EzUEF0bkR1STNYTEFsRklMZlFaTDFSVlh1T3lwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710720),
('KQyrHH8Dk6M8uVIihCCc5eXVKWXqg9Jy25MbhMz0',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqWVNEZXU1NmFzMVQxUGt6QlVBQ3JLYjh6akdESjFVdmNPbzRTcHN5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713128),
('kT0Z9pIF74DYQ7wrW7hcnM6wxsJsK09u8rMwNuNT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJDako2a0ZZUWhMZk1pNkxiVVN2OGRFTXd6VkM5TzVPZWlJM1IyMjdEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714471),
('KurHqUvn92OaCEtMfltYBZPkTKVoA1VlM1be6wdu',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqMFI4R1laZUpaaVIzam5YMjNXUWkxVGxueUhhdHY4cmNKNFBraE5vIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713858),
('KxVtsOIxuJfLoHqngalO4PsgW3EjblEAuvXt6K5a',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIycjA2ZmwycDNIdkJUNGxPVWdoajAxWHpQbXlZZWVXdWJGT0xkU1NUIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9waWRlbG9cL251ZXZhIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL3BpZGVsb1wvbnVldmEiLCJyb3V0ZSI6InBpZGVsby5udWV2YSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714569),
('L16pHkBaiAVVtF7izcVuqYdCW56INptbjHeuZOi9',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKaHN5OFdvN3ozWlFVamRBZnE0dlQxVDRvanBUeUM1a0sxd0puRTJVIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712147),
('L1GGibimz9VsrjT1yJumoMm8EGSTaogRIjUNj4q9',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJmNlR3S2wyNDFsM1IzSmZ0c21xS1B3UmJhOWJiM1k4M2JranhHWThJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713389),
('l2l3fqWfK0H5HFWIFHUgxoQTTSqHj0BFrh7JPlA3',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJBZG5mSDl6SmFTT0k1enF0elk3MkRXSlNrd2h5bU93SjF2cnVNUFRkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713855),
('L3PtwWh1oqLrq5w4yOfkNbrZT7HnQreNF2TcIhya',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2RFJXT2hPRjhkbDlGNllsV2hTV1kwTUlRRHdXRWRoSEY2Q0h5WUkyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712954),
('l6WsKS4d4IBWyMMOSagS1MW6MDGA4dCN2l8Xij0a',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjQUw4VlpqVHRVZ2poamFidFhNVnhUOURzdnFQOTZvOU0yWFJoS2laIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714471),
('l8IuRD7oDlMzxIr5hND3S4FAJVEGgMBwaAN4CWWO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJvcEQzcDBiTWVvYVVpSU9YSFVuM2RlNm9lZElyOHBYeVZoalVtczB3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711049),
('LAU6SBsKwT81ambKW29LhyN3obDKBcA4IXpulcgd',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJNR2Q4WlhCNzRGbHhva3ZnVjVsMnFGYmpqZU9oeGdrdkp6dk9QellPIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713855),
('ldXXWN0kW0tApcdinsjfJaxqdwdvB55sKvPDhEeJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ0V2tWZU1YUmFtNDVBOWtFVzVHeFI4VTQ1ZTZqdVl1QmlENmk1UzU3IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712602),
('LLUcszDiU3qo4Bc4eYwAuXnNmrdWZturAopxfXzh',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJXc2hxQnF6RE9aTTVOeVhVR05MSTBCY3lCdUJRYzBLNEp0OXVpV0p4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9ob3RlbGVzLXktcmVudGEtZGUtaW5tdWVibGVzIiwicm91dGUiOiJidXNjYXIifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785716357),
('lLysjChCe5EDH3gH9QUsSXp3z7AheRliFT6qieEf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJaa0RaRk1vZjk0TzljWW55S1pEMGhtdktQOGtEQ1BWWEd2c1hCM1dQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710859),
('LmLlT8bWqBj9l5RYjewog0RZOBYSTweXCdvWp1ce',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJDMlVqY3NoSUV5YW01eURWUTVDYlVnNzlsT3dOTGpmdWRDUjRJQXE3IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712313),
('Lq5TdwWZPnuEzL6vvgbCGvIFD9kWS6QDni1h3RJf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCdTRwdWg4aGk5elR0QlJsd3k2TFI3ZDRtbGVRcVJhWHFicjl1YjBJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711434),
('lQ7QTeQPUTa6I9PCvcBtD2zAnE70lIajm9o5MEJY',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4QVFPSUZZb1dpMjhCOW1tcVZ3M21QZlhISW5hYkJWWGliaVBNbVZRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713112),
('LQJbakZGI9zQpMKRvOIQREgd53rtc61ZnhN6wRZv',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJmWllDNFVjNHVJUjdZWUdMU0JNMmZyVDEyQmt4ejlBbFpGSXhXNU42IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvY29ydGluYXMteS1wZXJzaWFuYXMtZGF2aXUtZGVjY29cL3Byb2R1Y3Rvc1wvaGFuYXMiLCJyb3V0ZSI6InZpdHJpbmFzLnByb2R1Y3QifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785714991),
('lRT3PpacMbxCdc3GAyz3iDtH3a4fCjbmFdegkRaP',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzWXFQN0FwelpqSUpmcHNKNHl4cExqSE9QVXpQUjhCRWRZU1QxdmFEIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9zZXR0aW5nc1wvcHJvZmlsZSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9zZXR0aW5nc1wvcHJvZmlsZSIsInJvdXRlIjoicHJvZmlsZS5lZGl0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714049),
('lScocck1X8XKmmEAOG3V1AtrlMOyOCDl6uoeIVuU',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJjREVWazI3dDJTMTFGc3M3QTVHNmJ5QkNNeERoR1ZHN2hPSVI5N0t4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714307),
('LuWo0RjEACEAiTZ7eH0EnY3U13YeBtstAbyWbmvO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJUZlBrUE5jMEtKbnRFeUx5NVNqRjdXQ1RPTG1wUlZUVUtsc0E4RXN6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvdGVybWlub3MiLCJyb3V0ZSI6InRlcm1pbm9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711058),
('Lv2GcHU0nRneJlOkPW73MYZB7x73P5FF0FrI7tUs',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGUGVNMUh0ZnppeEhoY21jQzkybW53WUR3QVVWeWY1SzdmYnJaVTJEIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvaW1wdWxzYXIifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2ltcHVsc2FyIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmltcHVsc2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713875),
('lv53JYFZ1QhU1TzsyHGuqFqgWyH8MuSiWoWH6D3w',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJNY21OenZyRmQzZjVsSjJsdGVLc1RqRVl0cnJpTkNrUkhTNDlTSk5vIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712583),
('LVhWcVvzw5vH80Hkj1ivlWKDP4thmWRJJg4n9b24',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4blNsMjdBMDJ6WTFQS3B0TXBmekRNMFJSVTFtdXZhZGR2SnJOcWQ5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710664),
('LypzG3eiqhCO7ymabub0OFGGKUzJVEAxH5p53Rz4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJXSEIzakNBZnRBbDZ4QTJjY2xBVndJWHRCekVTeEtuMzhQUEEyYUNEIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9waWRlbG9cL251ZXZhIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL3BpZGVsb1wvbnVldmEiLCJyb3V0ZSI6InBpZGVsby5udWV2YSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711451),
('lzSh2Vs7PQwEh9LeDPUogIZUoI2494zyfwoH83KT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ5bndLS2dCdFZJdVFjYVVwc1RjU0JuV0lUUGRkYmVmSHpyQnBzc0VGIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712194),
('M71asbjedLmeRI2azi2NWrK2hpgTnpFavM3HklO8',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ1VzhjdUZGNkdnSm1aYW9kYjlaYkxNY0hHWkh1UVNDSnJHM1dLQkdyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZSIsInJvdXRlIjoic29wb3J0ZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716259),
('m73Gjf7iWZpN9RNwfGD5g8jY1t2HX4Go8gnFW3gy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJXVkdabUtCdkwyUGhBU0tSRGRBemNYVlE3NVY3Vmk3WWNsSnpSQmREIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGlkZWxvXC8yIiwicm91dGUiOiJwaWRlbG8uc2hvdyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713003),
('mctoTJN3txn0Z9Uu67GipLTVweSThugf9F72ZKnZ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJWekxRa3FOb243ZzBINktCSGZaOVY0Q0VQNm9tVmRCb1Q3TG1kYUtrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712631),
('mi7C51eHimt1HlsawdjyHDfN7GD0VJXA2fh2GIio',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4UEFGMklJSjhyV3lVVWE5Yko1VW5WZ3ZvWHhYRjc2VWxQWVRHME14IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714480),
('mIrmwOpIaFQAQHdTREyHCHkPArns0ybQH13tqDXn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJaaTZqNnowSlFDNGwzalNSUFpYY3FrYVJZNEl2RlRYTmE5WVByOUJOIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713339),
('mPyOgTnOnuvAAqTkKlgRGFYb1SVOPZiOe9ArMfKI',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIyajZ4bGh6ajVERm5FMGwwTUFQRFNhR2VSQlVpVmNRUEpiaTZ2UzF2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3ppcGFxdWlyYSIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715816),
('MQqnEcTaufAE26ffDTQP5ZuafSK0SYByTTSikCcl',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJvWXI3TXFEU0tvdVJiRExUMGU5Mjk5ZWUzZk51N25tNTNBc3djSGlFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL2xvZ2luIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715468),
('MXotVU6qDAsv5whGlLD4eDbdNkJxwq6EbTh4kRiJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTU2NaalZZaTRsVWhWMlNrWHQ5b0hLMnFIcGdUeDdNQUU5OGVWbUdFIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712630),
('myvFQBEds6JoFmLS7VaXvQxamH7deTCpb2DgDnp9',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCNURibUVhSTJiTzRmRDMxcW14Qk9hbU5id1VhR203UGhpY0Vic2xNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715627),
('mzc7KbYLW1IWOxjExTqk41Mq8Gb3rTMqkB4kkh5L',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJoTVdnbFg3QlBLSHZKVGI4cmNsQnNmN1N3YUFvemIxdzZCbzJPM1V3IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmlzdGEtcHJldmlhIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92aXN0YS1wcmV2aWEiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmlzdGEtcHJldmlhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715354),
('N0XBN4z3qtoTHGKJp91Lub10yvL6RNPXEEZ7ADzQ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIyZGVpcmN1YXdibU1Db281cU1qV3FvYjZxaG9WT0ROVkZhOEg4dEFHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711070),
('N3l8vdTOFbqueGyIX7z51AwOzt2KMEol4s8Wg7N2',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJIdXU0amloZUNHZVczZkRJbjZPN2pWVVB1QWIzVGNSV1hyUk9Hc0gyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714056),
('N71ggxQN9ZbwoIbVtVynOzIc2l4JBfso6TbjZQy7',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJlMU5IYTRyN1F2QnFFdEdYd2RYSVBtYll4RTVmelZHa2JlZWs5STJHIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715624),
('N8PtZ6Q858UY9Epfkb1KoPejkGvsCH7UobZiy4fy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJISmx6V1lveFBmSEJ1SHUzZHFmYlowMWRBWXcwbkVoaGc5a2NBOHV6IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714468),
('NAUZwtulSNgCaPSqUAHycOsiPIvzpOQeIXQ0U4HC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5OGtWc0pGR1NZa25UZ2U2THNWOHJDNG85VzVnSkR2TnlvTTRzZWphIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713456),
('ncocbcBQZQc9mx7H59Dq4sP8mRRAB5bOC4xzUcM2',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPTW4xY05LNWt1b0lqVjh1cG84RmdqZjhzamZ4Zk1iR250WDVWN3U5IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvMlwvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvMlwvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711518),
('ncviRBOntnxA62vs75LbdUt5YdABMOUc4vVYBayy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6YkRoZndCM3k2N3hHVmhRVE9KNFByb3NXbnZCSUlTalJHV0ZRenJGIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714062),
('ncYbJF8wZPjYO8VQI2k7qHAxQCOU8hN8wnzyxKaw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqakpnb05PR1ozazhpT21CNXA3RFZweU9qdTZyQlFNeGZxcG9mdzRvIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714504),
('Nk09S14fJMjak1zARj298N38A8GisGsasG5pOdzL',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJYT2RpZ1lQV3dZZ2R5WlNrblQ2NzdISWZWWjZrc3ZMa2VOUjdDV2tJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714080),
('nkUIf3FOiMDMPCQDg3V2XXDkYHdueLAG9MmhwyZl',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4ZU5Fd0hFUWp1M3lUdzFTU3NiY2xuWjJCckJLZEQxbWtDSGJrV1h4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713143),
('Nlrek36cojNeNrtQhtTaQEp8xRiacHXz4SyEtm3A',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIxMXpnd3FZOWRjQlR1M2xsTHNwbTJ4MlNTTjBYWVVyb2NGcEhuZDZRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714508),
('nMaSBt2CTEQmoBxsygdaSHronHiIvVaa4joDZF06',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKMzlUNE9wSmhvcVdhU2VobU9vNFZmelRYanpIRDM3NG1KTmtZN2FkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711048),
('NN3NT3b0beVSh4JJZeQ8xRJFgo6tSo6STpsOg8AK',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJU0llQjlNNWk0aXZhcUF2U2tybDNCWXRLcHQxMDZYblJaMjUzMkF2IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712745),
('NNr8P3wm8JIYbINCttfBKdd2iXJbbFOcDdoYltuG',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQZVFnTWdHS2g3NUVnZjE2TTgwN0xUUlp0VFVVWm5PSzhjVUNxUWpmIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713130),
('npBbGxn6g8jROOBCNEkrToDpUEuLsUHIYu6UHYXP',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIyaTI3WVpLVXFFVWVJODUzbFBHWDdTNmxKNk5Da05WM01PRmdNZlFkIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29waWxvdG8ifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2NvcGlsb3RvIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmNvcGlsb3RvIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713148),
('NsKTlpiaNbbNXC7i7teDXKtmhH56t9gz2FNVxgtf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJsUm4zcXZuVHdZM2FJZHR1eVlUTzAzYkR4MWFFMVBwNXl5ZlViU25rIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715624),
('NwM2SLqqUpIPcbkksEnJuZgMUvnBuFvGHFsoWX4P',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0VnZWN0RiWHJZMnhWcTVDWTNsZDRqeEZSVjRZYlhLWE94NUNUb3VZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711093),
('Nzs0EzmDBmIcd1rT6yflk0ulwi7jmhFwvnmJKHho',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJBT0xDdHBSZzFWVkFCZmhUNW9jejRjZWFUdHA5RkptVERCT3NUbnRlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712101),
('o0EGFMIEL7rOhqri2FIUEoyErUY73yAVK3gejOWW',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJZVHVEVkVqN0xvMWV0Mmw0VlVoY3EyNEU4WUt6Wld3UnBNdEZ5MzZvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL2xvZ2luIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715421),
('o0MOiHbtbIybOX19KVcmx74nyOKGhbIo16h2uQFO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJIZzBKUFNYODRUUmFyV3NQcVFIT0tiYVp0YXNwazJYRGIxdnlkaGtzIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvMVwvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8xXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715506),
('o0rbuhNFZvpOUneuT0uiNEma6pvpNgPOlh10cazY',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzWVJBV3dYbTlqUm9QRDJibDhkazhKUTVsUVVzNjlmbFJKNVEwNTRBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9zZXJ2aWNpb3MtcHJvZmVzaW9uYWxlcyIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716356),
('O4EIOrJjrjuzQ8oFis0gHiTaHGCSvT5DoUkjpnic',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJaQTZISnBwanB1aVhyWWl5Wlh0WjluN2FJVVUwamEyVENjaEdxYmhPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713119),
('O5XhqZIwdDdZB0WHf4a9YqTsFlUyiDkPT1t8LvzM',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJkdVBXcWg2cXlrUHdKMExaMUp1R3VkdkJCbVMxbThlSTl3bXRZZFRxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710996),
('oBTwuNv0x2hqEaT9zK4sYYlEQrgXNbf0x7p2Pby6',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJMYjl5NnhoMU5wSEU1dUZacnpuOU5iazF0RGE0M2UwbGZTMjdjTUdyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712905),
('oeerZgjc9j5RLFlnZn1ISHFxFktavBNtgMGP0des',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJaWnJZaHdLcDVqS3VyMkFGbUZDU2lvUmpYQVRockhaNGE0SGVDR3IzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714246),
('OF8b5MjuQhbCvg5asrbUznSr4t7MHX86queiRMmP',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTQnFyUjZXamtVZ1c3R1lyU3M0c2ZMOHc1OE50WVFJMTVHNXhIU0FzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvY29ydGluYXMteS1wZXJzaWFuYXMtZGF2aXUtZGVjY28iLCJyb3V0ZSI6InZpdHJpbmFzLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785714965),
('OfDDZ9fSoY9vUbO1NeiTC7ik2DxUyd55BqwIgGmb',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJmZWRKWkN2SXQyT2Iyb2NvbzBZbUtFS1NYWUFvOWd4Y3pxOTB3VlVTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714504),
('oh6yKpnYtIzjslbBd63SP6pTknaFNzp2KdbbZ0f4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPbHp4cWhvbW40N1d6QkpRWG5lVGx3SnRLUDZ0elJ3YnR0czAzZEc0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711093),
('oiq5JWJbGeZ9Ql0Y0yle8qD8CnjwmqeYmYu3dPS9',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqSHRibnBkekZnTG9kSDl5R1JiaUZQUE5aTTFTQUVEdmZYOGhpY2VaIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712953),
('oithYNUeDJuNc7X2V63UCzcHAwmtInFiObDcKOD9',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJrUVJSV09ldkxKMW83OGhUU3Q1NG1vUWIwMklEc2tlclpWQlFHb3hYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714536),
('osD40aXwYqrgQKlIUt1QITymjpao9mBtamtEHJ7I',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIyaU1RRUtMbU96aEl4QzVzTXd0eEpKRkt5cnpSSFJZN3doaTJLMXVsIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710912),
('oTq3cDUtvMVnN3Os796w27hRYqrXTI6wPPhfq0b7',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3QzBCcTlkRmRVOTJIWEVrV3pOVWxtb05IeDJ4a284cEg0dlVzZkozIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL2xvZ2luIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715438),
('oUyQCzgkbu3DOtnmz5VtuyY9RjnQp8p7odZTsbYV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJEeDBoVVM3UXZSczkyWXBJaUJocXN4QXJyNVQzVHlXbXZERFpmNGFIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvaG90ZWwtYmFjYXRhLXBsYXphLTIiLCJyb3V0ZSI6InZpdHJpbmFzLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715646),
('oVXF6Nj8tHc3aTobQn08z7eOJyv7VBh9eCsgqhPT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQQjFJdXU2VEwwYnBDdU9PV1oyUVhKWTJSeUNaMlhWQnVYS005WFdLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9ob2dhci15LWRlY29yYWNpb24iLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716361),
('ow4G3jMyaLe62cozDmpAB6t7O8DbYCVyO1TdzqVa',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ0b3lWeXZTMmNZSXhWd1BWejlGeFRHaEQzVk1OemNUd2pRS1VJY0NyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711310),
('owdws4j1J90yBh29fEQDr9MhxmgEW9thC9DwBDwR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJwQVVWMUc2cGNpREdYT1YyVTllNDVsRmhNVk9UU1JNUTR4UVRmV1NLIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714478),
('OwRi915XQpi8WcPA0nUZNKRAFcz1Kx3nRGcgZ3Tc',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJIdk5vOFRMS0ZBRFBvcUdRWkhKbXd4UkhHVDl6cjM2NTk3MEFoSWtqIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713831),
('oxxQiRrrVVtORXBmZrNclVk1vxir4fStQxCpn36L',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJRVm5lSXowWlBveTBWYm1ETVNEZ05GV3N2QUN3ZVZPQ0NWTTd1UWNiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL2JvZ290YSIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711293),
('oXyzBzaBjhc5ScHcyDm2QiKR8ZbYCMzaEDW6x3R1',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGYmg4VThWSXBqbjJtZXpKdnFQeUQzV2NEWjBNNUxkWnAyWnhybW1EIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9ob2dhci15LWRlY29yYWNpb24iLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716355),
('p02jKnf0G2r7uu1LSBAXmQqFQQFvINFXIK3XdHas',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQUTRYZEIwd090alpNZlZXRUQwUE5Sd1hvM083aEFzN0lHYW03UVVTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713143),
('P2Wf26qNV0zrPz0aFf4ehZel8sP16mz4rev5AwtN',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJwTVdLWFVEc0lqYXFHdWdma3RZdElyM1g5VXlvb1V4SjRCT1VXaEhuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710905),
('P8oeAaOgOvLBYHiMiGeaYURIHmrK3mYaacLn0OXj',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGeHkzQ1VmQWUyUUVXdHJTN0tKdE9mSjF5azJrcTM3ZEV5UjRKZnlPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712897),
('PA5hvNBcnq6exIZ4GhIqdzKKc7TwjlSe0WbGRtU7',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtOU9FQmdtSlFyREU2VDBmM0x1aGt2cnRxRzR3ajhpUmFlSTE3a2x2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712577),
('PBSsrI23ghjA4qvlXCwiBWo2w8payzKzFaqphznE',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI2dlFBc0NzQXhoY0lRWGt5dWVxVTk0akNyMlFrbzJvSFdnNm5aMW1SIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713143),
('PDdnjYyXBilz62TUW4X1v8LHjNODt1Q9k2ZiGczy',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTRFQxcUxodldQUmxFT1RZYXZoWkxjV0xmVFNDdXpUM0dpSEYxaHlwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714053),
('pebjs4DV2vBCmgzTKPd7BnevNd6raLPwO3H2RaUV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJzMXdkVlRWYzZCdmZ4QzJRMHNLcmNHbDVJQ0NtdmZXNU9sQlk2TllmIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714130),
('PEcwBHjvBhDlu8pJKciJ4zjiW3hN30Ff8pXhM7Sn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOVExNT1piYTZSZXRoV05zb0d4dXhENGlUNlowNnp4cmpvMmJBdHI4IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pbiIsInJvdXRlIjoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715421),
('pgCNefAQ0fLCqOkPuWGjvAajAx7TdqOpm8LBWnDK',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI1U0tlcXdiV1JYU0NldlFGcmVocGZkdlJJMTVLUGpVYVQ1ckhBdm5FIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714230),
('pGN2jT1GQrp1PZ4HPCaBHSSwPMhvtZhRKckSZh4Q',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzd3JMMlRNakd2ZXZ4WHcycTFEY2VaNzR1TlV0Z1FrR0dSaVFLTTZNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715642),
('pgyIAK71K2nnzhxiENGJ3FJFpudioqUU5TEUDG2L',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCNzJWdkp3YUJxcHNpNTdvVXhOd1A2aFBRSE5wMFRMMmxJUVZ2SzUxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710934),
('pJRVf6DPG4wT5rH1VIWHTZBZeKH5IUNjUSj4Rjfx',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4VzdTZ1BURTByQkcxeE9JUG90SnFZSlM0ekNncHowV0VNZUJIRGJkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712575),
('pO0rfTTnv96XYBTb2jo54cm7qX65E4vbl98KODQI',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTckp1VUtYMlBTcngzYVZOZkFkQzZ5NmVDdFlKd1lTZGJsRXJrWDFyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3ppcGFxdWlyYSIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716230),
('PPPZRKsHUuG9Az9bqiCevSbexl7kPlAZgerHmZx0',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJmbTROckk3bTdIWnFMQUZyQlZweW0xOTQwcTFQd2wxWXdUZWRJOEpuIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712614),
('PqMQrhwH4wdgQndVApiK9htVTfBWWk5M7zNubyzI',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJnamFpME9WVEVjaVl4eG5IcW5CY0NTdlRMUDJuejdGb2VEM2ZudzBVIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711310),
('PqyRW6enVfnaTNdgUrg2ICP383isu7sVgBtoWAfz',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxdjRzQTlTUnQzV3QxNlRibFExNTQwM3I3dmZOaFNBdFdXRlc2NUd1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711296),
('pttpvobh5yeSU4zCVXguH26qrk3U2zPMJiSYBwN7',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJiS3J6MERmaUl6UUYwN21WN0ROU3pBWDFSY0FkVm9mejY1U2NDUmdQIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712195),
('pTU8WM3NilmYjcbmCj9WbZGLKAwPyau1wvk0nXEY',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJSUFnRFBTUk5veUdVU21QbFVaOUV0TnpWckI0SDVRQk90M1kxcFgxIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711030),
('pU1AjhWZbKdKeZ4GB2kFCsmFfTzlicgdxdizOPEk',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxT0txNFlTZVphM2FwSU53NUEzU1ZKWXdEc011OE5zOVFLa3ZqcDFVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9hbGltZW50b3MteS1iZWJpZGFzIiwicm91dGUiOiJidXNjYXIifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785716354),
('px7XaZT76ZmJMsOsOe4xP648FTtY2xpMRGxTQknn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJYd1k0Y1ZDcHJHTFlJMlJaQjRsaWE1Qm4xUlVNVUZjOVp5MmxldHBvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716579),
('pxi0Jxs4oV9lXPx5AFbqxdDyE67g5gTK1UcwtvDp',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOSlhnZkhlTm9hQ096aktKcXVyQnlBR3Q4TmFlZ3dGOXVyWlQ4dmhNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9iZWxsZXphLXktY3VpZGFkby1wZXJzb25hbCIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716359),
('q3qPvKa2uM8ZYy9IPCJmqHn6ZLxkeluywbKTdM3Q',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTRXpiU05MVTNLR1VpSjh2Z3hmbWFBRkE4a0xNanJocEpOZEpZQ1prIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712425),
('q5tZd3Hme8bXpPzO2k7hyArATiTY60uhNVV4joLD',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3cWZHY0pVZlJaVk5NZmtDRTR0eVJSOXI5TzRtZ2FwTDY4QWN0VjF2IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9waWRlbG9cL251ZXZhIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL3BpZGVsb1wvbnVldmEiLCJyb3V0ZSI6InBpZGVsby5udWV2YSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711449),
('qbjSn2j9J1LbhPg56e0d7uu6lxEH2Gd7t6rXPPSR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJHa3czU3hGNkdyalBaMkJnRXI5YU15SGlhREVHTjE4ZVN6VkNGUDdJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715405),
('QDoVMAyGZ6nrOCW2lJzlFARJr9XSDnyheQRsBML2',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOTU9ET2hZTHF3dGtJSVpaZlVnN29aR3RvdVhSNEtRYW5xVktqRDRDIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712583),
('qfXjJEV7cRx0QtbxGsmGBHuPAFufCKjrq8ESGXJ6',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGdHpUbnNiNFdqeWZCdTZxaXNYTk5hYUVCcnp0eXNtS3ZLbWZMeGhSIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9taXMtc29saWNpdHVkZXMifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbWlzLXNvbGljaXR1ZGVzIiwicm91dGUiOiJtaXMtc29saWNpdHVkZXMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785711450),
('QmmKCUWkjPDrjLVagNuRDHiRLgD2BBK4dmFtri1b',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJSSmdlbHZPZk1DWHVRQnBVWGtsTWNWVDFJYzg0OUN1d05qSUZDWW13IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711321),
('qMPc2ON2vY1GmooS2LtRT6fTUsrICCRSzjPnSBoG',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzdjJNM0ozUUlLb2phWmluNHlIdjRjMzZZM3dFS1ZOZFQxVWZ4TURIIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713876),
('qmxEfXlOP0jUc9r3ufAJtfyCsH3ZnxYmoUdSNCkQ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPVkFjdkFKZFBERDlqcG1YM1VVNmlIcTZlSG1LdmR6MGN5REJKUThxIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713135),
('qp3pBRFaP1r7oc9hyKr8CaSfasXe0vrl2zSSsKSo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOczJZWHNHYjhaSk8yQ3JxT3JLanI2ZGx6Wm13Z3ZxU0hKb2FEQzBNIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvMlwvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvMlwvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711433),
('qpqKPw07uZSyVCUhtlujiJLDNdwLKJn44LCYKw7G',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJkR1dVSnJ1UUdOV2lZdzR1RlNCV1ZOcFBHRjNYMjc0Nm9vRkRYYmxOIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713319),
('Qw1p5dWSZwPjsybov8uoaA1dwdwjTQB6ICkAU8O8',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJoU0xjU3FEN0tJRmR5dXIwTzAwY2M2RzZ5WEkyVGJDUzBxWklkZDNZIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711470),
('qw4GhnVbayYguYNoCr97diVUI9y8uUQ3SyOJrMxn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0SFlJdFVpbTV4aWlBdjJpU29lMUVUQnJ3QUE2ZWtsSHhGSFhmWjdrIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715215),
('QyVkFrlybhiwtPYBVymka8yvC1uRAlYQQOIiTCfu',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJSaklObFI1WEJ3WGl2SUh0UXRxanhBaVpJU0NITXJodUhyNkE4WnVYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711309),
('qYYOb86qNaNv90YLehMigJFtrKj5BRItLPHancl7',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJa2p1SzN0cURPUTd6cm5DM2k1bkdVN2JUYzFSbVJZUGlUNTdIZ01zIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713878),
('R4cvknp8LOWhYjampKyeWBF34J2In2Y6UeNt278C',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJUbmJidnJUYkxsRWJQNzNEeVBzSlV5ZkpseGlPZW1QRUFBSmcyYjR3IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712254),
('r5iCyp20XOfud96iXxwgVti67WBk2LdN03v2LedZ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJmeDlSYkw4T3JLa0hmaXhoZVk0bVhvcEUyOGl4OHoyMUc1bG5jSVE2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710840),
('r8pve20niWg0ixoXLoyYYbEyGbdfxDPvfoS2NpxU',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5aW95U29ybTZNNmxSMHZTV3F6ckkxZWpqTkNSeVAxZlpOVmJtVDZtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714344),
('rbTrFc6k6xunThuze3fmEZqHDhuCpy9l2L8A1zfJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPWHJweHRveHpQcFlWZURKeGJRWFVsM1NaM1NOWlJEdElHdWxUR1ZzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9ob3RlbGVzLXktcmVudGEtZGUtaW5tdWVibGVzIiwicm91dGUiOiJidXNjYXIifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785716371),
('RdE0RVeDwaDosgeUKxkgPcodoz9ZlLHgKnzVDcc0',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4a05hM2VRVjdvcG5aY21QQ21ZSXE5T3Q3NG9lTUtCQk5FSmlnRUE4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715360),
('Rf6NbRv4lSEPXy0JDFheJJ9vYZxVcs30q511QYIF',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGN3hJVGkyaVdFYjRoZWVXQWF5TXhUU0lyNWxSSlBpWFpjVE1FVEx5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbGFic1wvemlwYS1pbm1lcnNpdmEiLCJyb3V0ZSI6ImxhYnMuemlwYS1pbm1lcnNpdmEifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715691),
('rFkJOrDTf2oUvhMEHGvKmdWaOBbnzq6DU4KF1vpF',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJhM3JPNjdHWTg3OXVnZUNGekdBSW9ORDRaRlROUzVYOXVRVXY1ZWxJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714293),
('rgPxZcRqXlpEBjM0ZDJOLBa7H2YeXqQMLAtOZQNW',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJWMEp4TTJBSUh6QlVIQUtDZ3NEb3lnWGtDR0pXQ0lOZW4xajVFRXJWIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785710840),
('rhr6aK7O9euG3l966NBCr457pMcvIbwa4zBIEqhO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJubktFV1BlODZ3QzJLMHY1VEdRMjFtMjVMUE01eVl2WEZEZDhyc05oIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712604),
('RisnE1gqk66JeomHiR38QfvPR8i8e564nlvNsTwW',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6cllOVkJkNm5FQ3o5YjE0V3lWWDRzaDB3V2o5aXJVQUVvbFdjZlNyIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713130),
('rJDYxL6UM0m9LpIPvOA0i5ecY4TlVQBJtbFqtNfx',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJiS1dIOXlqSUFBcTJrVXo2bWMzT20yYU05WUsybDFLb0hRdWtObVB0IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713128),
('rKArUWFBe6SQ9SRBi6S3oWCMTMRAhl62COHR2ukO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ5OERXeDBVMktCbGRFak1kbnBSZlNwY2FpZ2hSUERDRjFLUmlJSHlGIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713860),
('rlI7TF7bCyhvVuJTG6k1K2RcvsSDQIwmUF9CpvOP',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzbDhUOGJrNFg4QUdPaWE2MGtnZmJmQ0RUMnpqWnJORFhHeDRYYUlZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714566),
('rMpD1BBOg2QwWrTUHpVIGC4deZjgLVY00XhJe5Tn',	2,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJXT3Rpck12SllHcmdTZUtKc0lBZ3ptTmhnTVR0b05lNEJEeGtXSnVVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvY29ydGluYXMteS1wZXJzaWFuYXMtZGF2aXUtZGVjY29cL3Byb2R1Y3Rvc1wvaGFuYXNcL3FyIiwicm91dGUiOiJ2aXRyaW5hcy5xci5wcm9kdWN0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjIsInBhc3N3b3JkX2hhc2hfd2ViIjoiZWE1MTYxYzkzZDU3MjkwOTY0OWI4ZGE1MmYxYjExZDBkOWQzY2MyZmIzNTc3ZThkMGI0YzUzN2E4MTQzOGEzNyIsInRhYmxlcyI6eyI0ZTc4ZmM5YmRmZjA1ZWU1ZmI3NmFmZDc5MTVjOGIyM19jb2x1bW5zIjpbeyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InVzZXIubmFtZSIsImxhYmVsIjoiQ29tcHJhZG9yIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InRpdGxlIiwibGFiZWwiOiJOZWNlc2lkYWQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoibXVuaWNpcGFsaXR5Lm5hbWUiLCJsYWJlbCI6Ik11bmljaXBpbyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJzdGF0dXMiLCJsYWJlbCI6IkVzdGFkbyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJvZmZlcnNfY291bnQiLCJsYWJlbCI6IlByb3B1ZXN0YXMiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3VzcGVuZGVkX2F0IiwibGFiZWwiOiJTdXNwZW5kaWRhIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH1dLCI4ZmFjNmViMWNlYzI2ODAzYjNmN2ZiNDQwYTI3MTExYl9jb2x1bW5zIjpbeyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImJ1c2luZXNzLm5hbWUiLCJsYWJlbCI6Ik5lZ29jaW8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoibmFtZSIsImxhYmVsIjoiUHJvZHVjdG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoidHlwZSIsImxhYmVsIjoiVGlwbyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJpc19hdmFpbGFibGUiLCJsYWJlbCI6IkRpc3BvbmlibGUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3RhdHVzIiwibGFiZWwiOiJFc3RhZG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3VzcGVuZGVkX2F0IiwibGFiZWwiOiJTdXNwZW5kaWRvIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH1dLCI2OGI2YmViYmUzMjdhNGZkNGNiYmIwNDczNzViYzM0Nl9jb2x1bW5zIjpbeyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InJlcG9ydGFibGUiLCJsYWJlbCI6IkNvbnRlbmlkbyByZXBvcnRhZG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoicmVhc29uIiwibGFiZWwiOiJNb3Rpdm8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoicmVwb3J0ZXJfZW1haWwiLCJsYWJlbCI6IkNvcnJlbyBkZWwgcmVwb3J0YW50ZSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJzdGF0dXMiLCJsYWJlbCI6IkVzdGFkbyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJjcmVhdGVkX2F0IiwibGFiZWwiOiJSZXBvcnRhZG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfV0sImQzZDQwMmIyZmIzMWI0NjU1MDdkZDRjMDM3ODY5ZDAxX2NvbHVtbnMiOlt7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoibmFtZSIsImxhYmVsIjoiTmVnb2NpbyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJtdW5pY2lwYWxpdHkubmFtZSIsImxhYmVsIjoiTXVuaWNpcGlvIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Im11bmljaXBhbGl0aWVzLm5hbWUiLCJsYWJlbCI6Ik11bmljaXBpb3MgYWRpY2lvbmFsZXMiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6ZmFsc2UsImlzVG9nZ2xlYWJsZSI6dHJ1ZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0Ijp0cnVlfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY2F0ZWdvcnkubmFtZSIsImxhYmVsIjoiQ2F0ZWdvclx1MDBlZGEiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3RhdHVzIiwibGFiZWwiOiJFc3RhZG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiaXNfZmVhdHVyZWQiLCJsYWJlbCI6IkRlc3RhY2FkbyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJjcmVhdGVkX2F0IiwibGFiZWwiOiJDcmVhZG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6ZmFsc2UsImlzVG9nZ2xlYWJsZSI6dHJ1ZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0Ijp0cnVlfV0sIjdiNDg2NjMzZjA5YzY0ZjY1ZmY4MTg5MDJmOTIyNjRmX2NvbHVtbnMiOlt7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3ViamVjdCIsImxhYmVsIjoiQXN1bnRvIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImNvbnRhY3QiLCJsYWJlbCI6IkNvbnRhY3RvIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Im1lc3NhZ2UiLCJsYWJlbCI6Ik1lbnNhamUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3RhdHVzIiwibGFiZWwiOiJFc3RhZG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3JlYXRlZF9hdCIsImxhYmVsIjoiUmVjaWJpZGEiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfV0sIjFhY2U2OGViYWYzNWZhODQ4MGY0MWNjZmUzODRlNDFkX2NvbHVtbnMiOlt7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoibmFtZSIsImxhYmVsIjoiTmFtZSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJzbHVnIiwibGFiZWwiOiJTbHVnIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImtpbmQiLCJsYWJlbCI6IlRpcG8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoicHJpY2VfY2VudHMiLCJsYWJlbCI6IlByZWNpbyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJpc19hY3RpdmUiLCJsYWJlbCI6IklzIGFjdGl2ZSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9XSwiMWRhMzc2NWFjNjdjMmFmNjNmODViMmEzNzU0YjI4ODNfY29sdW1ucyI6W3sidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJjb3Zlcl9wYXRoIiwibGFiZWwiOiJQb3J0YWRhIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Im5hbWUiLCJsYWJlbCI6Ik5hbWUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic2x1ZyIsImxhYmVsIjoiU2x1ZyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJkZXBhcnRtZW50IiwibGFiZWwiOiJEZXBhcnRtZW50IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Imhlcm9fdmlkZW9fcGF0aCIsImxhYmVsIjoiVmlkZW8iLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiaXNfYWN0aXZlIiwibGFiZWwiOiJJcyBhY3RpdmUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3JlYXRlZF9hdCIsImxhYmVsIjoiQ3JlYXRlZCBhdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjpmYWxzZSwiaXNUb2dnbGVhYmxlIjp0cnVlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOnRydWV9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ1cGRhdGVkX2F0IiwibGFiZWwiOiJVcGRhdGVkIGF0IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOmZhbHNlLCJpc1RvZ2dsZWFibGUiOnRydWUsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6dHJ1ZX1dLCIxZGEzNzY1YWM2N2MyYWY2M2Y4NWIyYTM3NTRiMjg4M19wZXJfcGFnZSI6IjI1IiwiZGRjMWQwOGViZWZhNjUyMjkwM2FiMWYzN2MzY2I4YWNfY29sdW1ucyI6W3sidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJuYW1lIiwibGFiZWwiOiJOYW1lIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InNsdWciLCJsYWJlbCI6IlNsdWciLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiaXNfYWN0aXZlIiwibGFiZWwiOiJJcyBhY3RpdmUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3JlYXRlZF9hdCIsImxhYmVsIjoiQ3JlYXRlZCBhdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjpmYWxzZSwiaXNUb2dnbGVhYmxlIjp0cnVlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOnRydWV9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ1cGRhdGVkX2F0IiwibGFiZWwiOiJVcGRhdGVkIGF0IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOmZhbHNlLCJpc1RvZ2dsZWFibGUiOnRydWUsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6dHJ1ZX1dLCI4ZGVhZGYzYzA2NWVkOTkwZWUyMDFjOTQwNjEzMGVjOF9jb2x1bW5zIjpbeyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Im5hbWUiLCJsYWJlbCI6Ik5hbWUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic2x1ZyIsImxhYmVsIjoiU2x1ZyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJpc19hY3RpdmUiLCJsYWJlbCI6IklzIGFjdGl2ZSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJjcmVhdGVkX2F0IiwibGFiZWwiOiJDcmVhdGVkIGF0IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOmZhbHNlLCJpc1RvZ2dsZWFibGUiOnRydWUsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6dHJ1ZX0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InVwZGF0ZWRfYXQiLCJsYWJlbCI6IlVwZGF0ZWQgYXQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6ZmFsc2UsImlzVG9nZ2xlYWJsZSI6dHJ1ZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0Ijp0cnVlfV19LCJmaWxhbWVudCI6W119',	1785715343),
('rnKdCwP2CqgiFFRIu4UZJpBOBfqqJMDo5pkAkLB3',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtaHpUTWx4WmxOTDJVd3lmbUw4bEhCbnZabHlLaWtTN1JsSkxyM1ZQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9zYWx1ZC15LWJpZW5lc3RhciIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716358),
('ro1rBHQUvfAAlP77OA9ipinBymlt5nAc4JHdRCIM',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJSQldoTlpWRDc0dTV5WUxZQlo3aWxkZmtNbWthYnhoRE5KaTBiN2p4IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713067),
('rOI0fRwa69WZpsImvMHrp4UkGCXUxZOHiwZJGNqu',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCSUNCVWFuNXhrWFdSMVoyR3VLakdaMTFVTUtLZjgyT2FabUduZU1xIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712619),
('RQQuwMnbPjuXpEh9vuZxr3nqwLrGjQPFhDhbgoDD',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJVMjllT0tYb2FKcjg5eTdYS3pHbkZXQ3hER2NsM1JDREJndTN3YWZTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711450),
('rQZqMnT1OqAkC8xuM6fMpCJTZ174tnADA4tTmGO1',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ0VUluZGZseFZLeUpYdEl3YUIxYWc5emRQbXd0VEJ5eDByMU1qRG9IIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvNVwvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC81XC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715513),
('RsW4TzO2596N45A5EriayUurgBpZmQZEwhQqjXEJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ3ajRzSVZHbUZuZlRKM3hudHhvaEMzSnFnZmNDRVhQS0hJSE9NNEtpIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711984),
('RtNCfanmZZzh4Ws8YUvxkSkPcI225MdMQLkI5HeT',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJoN25MUzNzTEVCcEh4MEM4UkR6Mk5KUWhrdGFWRnhud2pqZElFT1hhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711305),
('RupwqOJY6lH7sBX5tnzKg3DwN3eoN8vJxMoRAhhj',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ0Z1FlRXZaOFZNYmx0VnVENHRPc3VrQVZITDFheGtRVk9GTTdmZUFSIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715510),
('rvxvqz60curZolHjLRbgeCQ08oUAW0Z1XO9gngUv',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIxQWZFOVNpWHF1djdUNTZlQTVCVTQ1TjdUNmViY2t5dGUzZjRyb3JOIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713130),
('ryPUXnxNwvizIj5fQZCeBHWk2PN5cNI2NiTBaHvJ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6UnA1NGJHdmVBOHRDWWVjdlRROXMyTDdOOHZQcTlRNHZVVjNydHVTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713582),
('RZgKSUTSVHsaOQTCfJAhCfeHbIYztVBAao5uWU1E',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCbnQ0MDl6QnNpdEJaNDlqZGNCRjZFdWFuMGdabzI5eXpMUldZVFRyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710907),
('RzI2OpibwqgK0jFv3Q1hEQGYHPfXiNGF5LvSsZz5',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqOHp6NHRLR3A2dndYcmxiaXVaM3doamxiZWxkeXJQV0hLZDNZcXdvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714036),
('s2SznycGgeDEQK3O5Vy28RZdIlCc6HtGpC4qxQAC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJZbjh3bjFQN3RYdWhIdWVuRFNZSjg4ZXVZelBIU05UcFRnVmhJdVhiIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714474),
('s6GyLlhfyBVnjPfu2xo91Owb6CToBWfw6ku0TGaL',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJiNUt5MHpBalJPZzFmVXFKV0xQMk1QdmJZZkJZWmNZSUN2algyZzFEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712745),
('S6H9CKdg3Lh9rbE8NvKJocW7jgIZdah1inJDszhw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIwbzg0eTBkWERvUDlzSnRnVGo3aVI5VGdjNTNkUlNzSFdHTTB4WFRjIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9waWRlbG9cL251ZXZhIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL3BpZGVsb1wvbnVldmEiLCJyb3V0ZSI6InBpZGVsby5udWV2YSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714566),
('safOruIycy1kFCVQtnmShAzte8CfnxJEtyZDXszB',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0MERuN3BpdXNYckw1NHRJU21lQ1MwYkhmNG9xbUlOZnI2NnpRZ3hGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714478),
('scyIpR3cipvzm7xooGrUtxqzrNMkkO7y6mrFXS8e',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJER0JaVFJieldLQkxKUmR4M3Bob3RYSWVlOGpzRkZQWXo4NUpUcjRGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712630),
('sEZHXstH8UQmde3ACjBrdWfWh95EFujGqPTisvdW',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJMU2ZNNmNWSk53UVNHa3R4clBONVBTWDdDMFJaTnRYMGh2ZzYxVVpHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvdGVybWlub3MiLCJyb3V0ZSI6InRlcm1pbm9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711061),
('ssvK9dOASeKnoFUAg7xjsKowVRGbxUqmZd1UiYzo',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJNdlF0d0xnb3JiTGluem84Q2NCMXNlaWVCa0JOWVNJTlBaT1BsVTJlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713835),
('sSXeg1fC1CUMIpbSIDL7vWJK3nhD8yl8SVopf5a6',	NULL,	'127.0.0.1',	'WhatsApp/2.23.20.0',	'eyJfdG9rZW4iOiI5U0JYRGtWVHZwUjRsWk1tc1kyRTNtbnBONTJZT3o3UThMT3JqME5hIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvaG90ZWwtYmFjYXRhLXBsYXphLTJcL3Byb2R1Y3Rvc1wvaGFiaXRhY2lvbi1kb2JsZSIsInJvdXRlIjoidml0cmluYXMucHJvZHVjdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715603),
('suSNdzE6cBcstGcQUDDw7TuGj3wQCGSXqQ7ozgvh',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJtc0FGTnV5d1B2a3o5UTFEa2haSnEyZnp3WGRKUExvNmRWRnBjRjZVIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714536),
('SYlfIdxRBKMq71h3auTfK2kHwYLPz3d6sN6qiXMk',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3dndOcWlKY1dUSFc1NlFJdTdFU0xqc2ptMVg2WmJBSk9MVUM1alRVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9iZWxsZXphLXktY3VpZGFkby1wZXJzb25hbCIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716355),
('sYSjxhLXqF6VOGTQTrWqt7ZJ6KAQ2HDRqqIxqVl3',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIwVzlTR3BDVm5IV252RHdUQlltUmd1OERnc2k0M0Z0RkFlVzlVRnRQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713430),
('SZBY81QTNq7NKcztnRvY5GvsKOY03wPSv8urXZwS',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4bFhpUWw1VmpLOURQRDA0S1dWTDBhOE56SEJuRTVoWlY1aEw4SGUzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711474),
('t3kDesT2sSAARRAYNi9ElGL7yWSmPcAsHObH0Kkg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0QzhNZURpTUJpa3czcVowRDZkVFk4VHB3WVpaYTZuNXNBWTlaWnFLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713851),
('t3wQip8lJGIkhfwBz35HKpT3t4mkdRp0WX6Che8h',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJHZWFTYXViSXp3WUhxV3ZGemFCR2EzaXlSUzlkeTRUc01KWmNZQ1paIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712602),
('tA1v2skgkMvAaYZQTk4YcQdBMCnY2eprPbvWtniB',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0bVBHMlhTQ0NJNEoxVU5sZkxIdElkZFVyMjF0VUQzNUE0NjRBOVpDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715235),
('TFdcsBbw5yfZcoWBsSPwahbwGPVBYie1HJystujN',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4a1JnYWtPbEw1Qm5XaG1OaDhsaHBSRndzRnZrMWdGRzAzMnRXU1ZZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713875),
('TiacJeh7W3jkuffeOwGxK6NhxNLrH8FC9ngMafkq',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCR29weTJud3NQMWpyc0NpbDREOHRCd0R4N0FXdXpjNk1tQmFLZWNuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713067),
('TjxG0xDB3OEiAsXbU9v5Cz3W4n2KCjjNcrOANvqM',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJpaEJvcjNlTkZ3NFBSc2ppbDFxZVpRcnhKMHI1azNXODNCSjZ6ZHNzIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713835),
('tLddy6L54piqLVPv5VqHBDTlbiOODBz4jIfbTnUQ',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4RjdYbjVpTGlJOXR6WmU3bkNkSzd1Ylh5djF2bFQ5RlFUbzBmemw3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714569),
('tQbLBCWXxsKKPpk8WrOt9cS2IpRKMG3lvjZTAAiL',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6a2pLUFdNazYybkxqTWtkZm44Mmx6N1ppU0lkZDEwR3VzZkFJdjd3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvaG90ZWwtYmFjYXRhLXBsYXphLTJcL3Byb2R1Y3Rvc1wvaGFiaXRhY2lvbi1kb2JsZSIsInJvdXRlIjoidml0cmluYXMucHJvZHVjdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715579),
('tSYmFdp8tdyymHnDD8BSD9nh8znfWfSAoyD1R2zv',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ3M0ZwMEJiUXg2MUE0VEozV1N4M01GOWhDUTRWalV6TVkyS1RIMEpjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9tb2RhLXktYWNjZXNvcmlvcyIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716361),
('tttKOUukT9g6PRsZjP2IhdWffjIKYaMPKaIEY68c',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJVSHpUTFM5WUdJdThOak9VSVdqazBoblViZk9XZ0c0OGFTYVJzakpZIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713067),
('TYgjPiM5bueeBzsjF369S4AW7lH4Ygx7Rd2d1Wi1',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJTd2doTTJUUG5SYTZ3ZmE5aWdiTnVpdVdPYURmaVIxdmZQS2tDcW5WIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713872),
('tYjPCgkwasRfhZBxq2ETPArfafenhcdmbzPivr8L',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLczQwM0tUQlFHTklUeDNLNkhWNnp0Rm94U1R5SlhzTkVGRUdMclNVIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pbiIsInJvdXRlIjoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715398),
('tyOw4DYBBpieamkneskkW2et5lkzK9FTqQncsCrH',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0UllHbUI4Vm94RldTcGtwcmJ3eTBmWW0wODQ1N1liVUhaaERtSlNXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713135),
('TZBCVP1xj8sxcum9TG2QJbM2vfnVkoPal3XXPXwr',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLYVVYbFJjdXl2cjNhQkZTR2ZpUlVNdDlZMHROSmxDMm96TGdMZ3l2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713995),
('u6ytLMOD1kz66KqBrIBNZPWJ9VzKLMPB8keBx1oC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4cGpDOU5paVVoSndOVXYybTJiMnpDTThGMVd5dXFFRm13VHFRYXV6IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712927),
('u96HiEHCmr3BmvNpvpr2gey6bOSmjhUQHpDQmU4O',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJFaXZzcThIak5oS2w5RUthc2tDb3NBVVBtQmEzZ05KaUFZNGR6ejZjIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714480),
('Ua9hiTu8PZTNOl30JTmRkpu6L8u2dcuDJK1vHVEf',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJvUjczb0k1NzFQcmFJYWxZS1lKYXViYkM2bDE5ckZxb21lRkVzWFl4IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711708),
('uDewuH8pCrEUm9KYk0MfbAPVylPbBiCsdyI0oCbK',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLZ25MUERhcXZtdHVraDdCVDFhSmd1c0FuQ3hkdDJFSkw5VXhtdW1rIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785711282),
('UgTDClpqa3xZkB5k4eBIZpIX7r7WVqWUHB4bCIp2',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJiVE1KWlJFQUtlb3RGaDRzdnpaYUVnWlFMT1FrNm9mNTd4WXNDWWxwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712629),
('uhOXnBppSIW0yMiQQFLvnBXrrEKGo6ktQ1rcWv3m',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqSlJvRWxhbUkzS01YV25NTkFydjlITVE0d25Pc3poVTJ2Y1pVcVRqIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29waWxvdG8ifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvbmVnb2Npb3NcLzNcL2NvcGlsb3RvIiwicm91dGUiOiJlbXByZW5kZWRvcmVzLm5lZ29jaW9zLmNvcGlsb3RvIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713871),
('UjcFEyDj5kNoN5iin1XS3RiDR3MVaciiwvnAwZPN',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4Y1pycWZURWZsWjBDeWxtdWxPNXA2M0JMdUlnTUNHMmJwUEhhOWkyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712005),
('uLVLVc3CYWlMEWmi5CMzWkykjjmm1iNsuOGkSArC',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJORkRWRkhXQnhzeGo1TnBwSWh1TXp6a1FvaDlvZVN6Rm9pdGFJTUloIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710996),
('uN1Uo53eMZvM7KoNB0LFt5IDHhwKIrg5ygpb0Skt',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJNR3FHZ3lzS2ZRVnphM0d2WlF4RVRJOFlpUnlzaVJKTGVwYTU3QnBXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713130),
('uN7DA5CYQFVGdkrQH0Tjq27ZbjFSKrVtMbnWpyqR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzUHlvYXJKMGJiUFRtMXppeEhhQ0pJdTdkc1E2TmxpNm8wSkVrN2d2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712578),
('uSzfmyMoFSFk4qr8FosBIhqqmeehMlepc7PLZFIb',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJHS3ZrV2ZhUWVhcmhWbnJ1QmhvTFNLczJFV0k0V215c05aMk1XUXpiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZVwvc29saWNpdHVkIiwicm91dGUiOiJzb3BvcnRlLnNvbGljaXR1ZC5jcmVhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785714030),
('V04DH0NHVKhJzPXgDhFaxtC8ncJmIs7khy2VAlbO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJU094d3JERTN0bHBGZ1VDMFBiU09xS1dGVDNWQXpkWXRqMjhmeFFrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716319),
('V9RJsnsedyMkA8tbDRKsFDwmenzxh6Nvf2JBadfg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI2cExzN21ZRTB2OWN6NGlub1VTSnpKdGh1V2Zpbkt4UWhLbXJyTFFPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713860),
('VCRcdJPpZ4ljzyGHpYRbbow7zKQ8vz0GJAGAQooR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI1VmpzR29pUnkwalJEanNPRXpKWkRwVlBhd1A4R0VsWWV4dzk1VVdtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL2xvZ2luIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715463),
('VDJC3dgo4xNlcarVAqyUYwbQ7XzdivrEI1xTU3qj',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxWjdQWTJPUHlmMlV4b2ZzZFNrSHNTcndYUTh6dGIwZEdsR2phc3R2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714488),
('vJJQ8eo2EuhgDGPRuChf3OrK6D4VbeB80lBMLjZO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIwazVIbm9CUGhHa2ZoSnF0eWlONUVXVE5XeGhNTUROSThxbk1uZ0JaIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711311),
('vKmT2pZyIWm6fgVR4GFycx2qX3UywYh3UzQAzwi4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ2RUNzVkxvVFVKUFBoaE1JYTVhaFZKRlJHenR5NUJCQ2EwNUp5UXBaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713148),
('VmDYTWGuOhI28ZQ4byMMbCdzQmblPG6g8XwOrmG9',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJDUDRuRDFRb2RBOWxSRXRwZ29sUmFnWG1nMXNTSGFneHdCeEZZcVNGIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711093),
('vQnyPUCRXxRYjfSAPvxTn6RuylSZgPm8Y3SMFLh6',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJCTGVRMWR4TUZ3WXV2VnY3c1VwQmd3VzZralU1cWxhclNCMzFJTFBCIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712627),
('VRlG4EZIPdHerMk3CdU6Z7YaoFiTzQqh8HvLclHF',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJMOWZOMnZZN0ZkblBEZWZLWWtDeHVtQjJEczFjWlpqckRhaW5QeTFkIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713106),
('Vt9sjd3YyQW8q8wQpdtYYhXcgfpkRHgK4J7MyXU3',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJIdGxpTWtZWkFDWExzWjByUTVob3hReWJUdXVac1U2NmwyUTkzR3lJIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712575),
('VtZ4CuvKIHQoo4rjk6iG0J9jcpTEdQtmFg4i9nhu',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJkd0hjQU16akRZNnVJdnYwOXBRMHA5ZFhuWFdSOWp3akFDdjNxOXVuIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdmVyaWZpY2FjaW9uIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC92ZXJpZmljYWNpb24iLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MudmVyaWZpY2FjaW9uIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712579),
('vUtGB24oXMagrSTQkGSdVSyfmnFrG6CbOujZz1kO',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5cmFpdGZtZk9XTHBjTjhNUXhrWnIwUWtadEdSb3NXYWV4aHBQMHI0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785711440),
('Vx1DYJcGrlz8figYXfRXTktzpNPjuCpgwX8rLSbI',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI0ZHliU1hWcHgwZmFvdncyYUhkaTRMRU9zcFdCOFp0R1AyVFp2U3ZvIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9zZXR0aW5nc1wvcHJvZmlsZSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9zZXR0aW5nc1wvcHJvZmlsZSIsInJvdXRlIjoicHJvZmlsZS5lZGl0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710905),
('vzWm9piNcSjGi8XbcWU2L7lqASQa6G54CO8qggFi',	NULL,	'127.0.0.1',	'WhatsApp/2.23.20.0',	'eyJfdG9rZW4iOiJzOUhKVFpnTDRJMG9mQmZOdGtScGpiT25rQnFSQmhUcVgwejBFN0QyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvbVwvY29ydGluYXMteS1wZXJzaWFuYXMtZGF2aXUtZGVjY28iLCJyb3V0ZSI6InZpdHJpbmFzLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713292),
('w7ZTOSGeHQHil6gPJJhFxxnsQ8zF18dGDxP5TlBs',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqTGdMTU02RUh2d21vMUl3WHB4RnNFTWF2SkE5dHppUEFwdzVPM2thIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712577),
('w8Ve9D6oxOA16dareNwNd1bJrzVkJ55AFvggJlUm',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3cnMyc09tZGNWZVNwYlVpaW9kVjl5cExrMXEzMUZTdUJuOENQSVJNIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710664),
('WaTBFNxWy2TdwWE0653X7Mik5aJQ9wG8d9w5JBhE',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzR29wQk04VFM4NFJ5T1NHWlhmRUZFeVNsRGRRb2hHYU9NbFd1b1BvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713106),
('WcjcW611Atlfr2DTqSCVbappDxQxMI9QNtsyrRzW',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5M3E5a09IeWlKOUU0dW5XSG55Y2JQMVpHWTRsYnA1NkxjNkhpY1dYIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712897),
('WcM2UH1kGo49FdXTjqKmxFYMyds0LgdkmPtsNVi5',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzaW9EQ3FvUVZWWDUwT0JNb2xwYTJFc2QydWZVZXBINThMV2wzckh3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711485),
('WDUOKVN9zfnUFnyhA9u8kY9240t5DP8saBo5rMI8',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4YkZINkk3Nmk0NDFSc2RBV3JSN09Kd1NyQ1BQNDNkUDFiWVJxcDF0IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pblwvcHJvZHVjdHNcLzJcL2VkaXQifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL3Byb2R1Y3RzXC8yXC9lZGl0Iiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5yZXNvdXJjZXMucHJvZHVjdHMuZWRpdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715468),
('WEoIn9U1QEirbBgJy7gCE8sVsuWRa4f9RMcDU54t',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ6aFlDVlg3TWZ0bG5xaWZMWXpCZnVUTTY1WHlLZEZKWEx2M096cHVkIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711321),
('wgJ3QNFbufgToK2ereqFuOKKAWTUmeJVw8gxJqVw',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJaUkU1aTZsdEpZTUVaczVIZjg1THNtbGZCTnhMSVRGOWRKWFNlajVWIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712633),
('whDcktpOfYT2iS2x4Mskpjowk5ILC6uK4dOZPmzs',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJNcGdZSlJUblRqUmU1SnZueUsyUkZtTE84aTBRc0tsakthd1ZCdjZPIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715504),
('Wko6e6wEOtQAFbM0U79I7aQ0iBrwakfNSv8LArAR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5THFCQkoxZXBjM1dKWndzZE50UnU4UjNDNkRZOXRaV252aTlwZEdSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZVwvc29saWNpdHVkIiwicm91dGUiOiJzb3BvcnRlLnNvbGljaXR1ZC5jcmVhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716315),
('wmZ8vNzdRc5pxruQZAP4zxetQNYiiGsJK2i2wmWB',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJrcG1QNzNUalVWeU9ObkdsMkYwVzZPeVhUd0RLQTZaMjZpcGRXYVpWIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712147),
('wnMUTa2zdW6sIWMEP22OTmChuP2oHJGGplnYR8Ws',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJhdGRHV01mMVIzSGhnWENuRGJ2ZmF0UHdEY0Ric25rYk1rcms0bWFaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711708),
('WwyIQQOuNhMqq9JQmuFpfYViVZRoW9iw47HFZeni',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJGSlpqazFRMGo1cGZOckNUOUM1VUFteFpyOEtiTEN2eXMxcGgzWFZnIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713571),
('wzxIa0oKNPuInlq69s7RwGB7NLeShbgOQq2ciLlV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJJb2J4ME1oY2RoY2ZZeWF5ck1ZdDhwQVBKZ1lOZnEzdm5PcnkwdjlhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715574),
('XCdXUY0RLYCfD423YlaqrQzVOAKoyV0zr6aaup13',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJod1d1NnlQWHl4YnBGbEpDNm95NEEwTk93M2M0QWFpb25ESXlnTGJVIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvY29tcGFydGlyIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9jb21wYXJ0aXIiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MuY29tcGFydGlyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713119),
('XDyRj8yNu1mTFaVb75ydQx0LEYfM7y8vbTT3Yk5P',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5N3dpU0hFYlVHV3BPS0ZSMEFnNXlwZVptQ0p1NzdmM3I4QjBhSk5LIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712313),
('XEmgpbEkxhUjKLxCxYWy7KxYStnGBqXcwloleNpD',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4UU1EQTBuZFUzbm1pMG5leENOWk8yMTlvQmp2UDBFa0pXV1RZcE03IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711484),
('xMQbxciofYAyzkS2uzWwsYdICP79EZXOcusKHwlb',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJkSE5yc1FJTE9GdVdFQ25JTE82bGVSV1ZFOFdxSFl6N1lGR0dRdWljIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785713129),
('XQ6KBqCRS1PdiNrHINpmYG4NRCNjgFqUCkinPn9l',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ1RHhaQzlYVXZnTFZENlR2RWs0a2l0U1JUSFlXdUZ2ZFNVTW9vNzBXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZSIsInJvdXRlIjoic29wb3J0ZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716305),
('Xy0vLoIuw3K3brY3cj0Ur8cpcJrAowpgxWd9C30L',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJydmI3Q1l1eTlsMUVpMFc3bldidWxoOG5BV2dvcEI4WEVHbFN5cTZWIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9jbGllbnRlc1wvYWN0aXZpZGFkIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2NsaWVudGVzXC9hY3RpdmlkYWQiLCJyb3V0ZSI6ImNsaWVudGVzLmFjdGl2aWRhZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712633),
('XZC8oa5jd5SYjBbhHe2XdBuu7FxeKYZXFimydQuR',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJ4Tk5ScUZFYmV5ZmRvRDVrSnZHNFd6ZXN3cGdHT3lKYzEwNzI1T1hvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712540),
('Y22O6plAbyJf5C7aKKtMTctCeFLZo5O4MBfSDoLF',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI5bHpIYlpkcmNJbjdwQzNOTzVIRTZKYVAxWmM3UVZIQlVKQ0xQTWJQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712195),
('y5rKXA2X2XBBX21KnyKwOpgJSKtteHdpKl8h3X8E',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJnNHpwS0VPMU85SHBDVzRTSGxGUFhCMmQxU25OUXVKVEM0M1BCWHlPIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcHJvZHVjdG9zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXNcL25lZ29jaW9zXC8zXC9wcm9kdWN0b3MiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMubmVnb2Npb3MucHJvZHVjdG9zIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715176),
('y6koT1q3nzaqRbSvZWfv3rnlBknVkBrOrOB25dwV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJYaGNvTEVBZDF6UTZHbUxuYnd2U0Y0NGlqOUI3S0tLOU5tUDNJbE9uIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712954),
('yDgydZ5s0utW977zu1eDLwMTPD5RE6zi9UJsYhX4',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIyU0VRZ0RQVUxtc003UGxuU05HTnBVc3hDY2RmZnNtdUp0cGxTSTVJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714468),
('yeQFAgHv5uUX2mh7pTCH2duEnGEIitQX1ecA6OxL',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJLdlJjV2VuUlhCSkFGYnc0cjZwYk0xelM1ZVo3RU5wZUxjVk16aE9mIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9hZG1pblwvcHJvZHVjdHMifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvYWRtaW5cL3Byb2R1Y3RzIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5yZXNvdXJjZXMucHJvZHVjdHMuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715463),
('YGI9qf9El3BskOpoTP82fvHFFI5AF1GrOWnhqpVS',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKdFk3ek1tZXQwbTBud2pUNkRGQVNUclR4YmxLaFNyeWcxWnlGeWo2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785711312),
('YhQmCnnAFPumk1MXUfUoEsOfZ2MML38Tz14xqCKg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJDV1hmdVgzdk9OMFlORk9kVjdaeGFxR3BmOHBXc1NrWjBKT1d1Tmo5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785713570),
('yhukmr9SMn3gX9jHdjIvf2lbhH4NEAeYZ5xQSUkp',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJISUU4dnZwZlFJbGxCaGNYa0FQeDBSY1NCZWI4UWVseUg4YThNdTNEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712972),
('yj38vPnUmMdGbPDOa9RAsIhsk7T21Ic1r4xKDFRb',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJSSEFYMGVXUDNHTU9pd0xIRGZDS3hsOUtkSzh0VUR5NkdQV0tjR0Q1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785710719),
('YjT9Fpcapjw5lxiJR4Lc3jTjoBGPlIkB9818irsY',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJwQkNOa214TXpxYkxJeThmR3BJdzBxUzZ2WG03RlF6QjF1dWY0cTdsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3RvZG9zXC9zZXJ2aWNpb3MtcHJvZmVzaW9uYWxlcyIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716360),
('YLZTqmwWfDgEcsY0gz6ZGwINTxVQLduxahvyOfMp',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJQQU1reG1TTXBzRFRCYURtajNtZmx4NnlSR05KR1Q0eUlOZ3hNVTR0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785715417),
('YNipjzQ6MAl7rStBHNmZHIRegDaQtkTwVOyna5fj',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJPdzczdFZjcXNFUW1BU0pWNlRpUE9uQ1FDSlhYWEo1R2JXdHE0d0duIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemEiLCJyb3V0ZSI6ImJ1c2NhciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785712254),
('ysKU23jJsKZoOfLDsNvPVqcbd0K8TsU7taw9xjhn',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJqYW01Vmo5RkFqdzdoZjgyV1hpUjRISTRTYVlmYnRaVExjcnlycTFsIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713865),
('yuzBOGPBcYwEseECLZQ63tkRk4tbGUsxEbnyQxgY',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJxZ2pQbmcyUXkwdFJVdlFXWnRPMmFCNWhTTExTV3lCQ1hZTExsbGpaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785715486),
('yVV3U7bpFnWuvkohCD7sURvPqQ9WinKWfBmrrdiH',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJBWlRVcVVGSXRoRjU2UG9ReWYyak5TQnpKalQ0MTFqYmpmeVRwUEhrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712619),
('Yw213ook0bXQMFXNK2tI4dVcPPtxvOAkf0Tf1jpV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJKbHBFTkN4emJCVlhYWG5NamtJZFlXZFJORXYwdGlNWXdmWUdVVkVLIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvcGxhbiIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5wbGFuIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713995),
('Yy8wajd875A8P9uXthLnbOhaqnrwVkj2m9tiPKWU',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJMc25abUQ0QkxCVUo1TmNZbXZTQ1Q2RXlqc1BuSzE1aDJsYXhXSW5xIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714041),
('z82JbCjyoBBuHsR0T4v8h80qUXq3SBPJLvHjXnhG',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIweVpCb3NhWEhUMVQ2TW9kang1ZE5vQ1BPczhZOVlLQUZJNVZJRkdvIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvdml0cmluYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy52aXRyaW5hIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785712917),
('zAarRKKqEJYYJLzfnCYluuROB9un9Kx6yP9MvcXi',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI4UUppbVFkeE9SNzlKRlVLS0Q3bWNnTFBkOHlsU3dUVDJxMjVoYm5zIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvY2xpZW50ZXMiLCJyb3V0ZSI6ImNsaWVudGVzLmhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',	1785714540),
('Zd8XvqfdZxm1OCR5788H9EeFZlTcy4oMC21JkO0S',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJhQnlBdXp0RHZwZjh2SWFHclVFRTg1YzFxMnFqQWdMSWR5d2lRcXRuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711985),
('zfxfgmlC9TIyPPL3lJshJmLQvmzwPWg7BTJnKi4L',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIwUmE2UGJOVEdrWlJ4Z3dvQk4yTWdhMjRtSWgxYWs4T1hVVUt0cVNBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvZW1wcmVuZGVkb3Jlc1wvYmllbnZlbmlkYSIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5iaWVudmVuaWRhIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711053),
('zgefgQe8KNo3eTOAdDYbz5GOwZACgaz7P40GB4rt',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI1T1FlS0xYb3JWQUJxTlVZdnBKcXdlNGZScXptNGx6SG5UMjV4R2MwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714568),
('ZhdQnblE4bDUvZWvLB3ZcIPowPyxoShB9dRMvjzs',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiI3THlwYjFMYzE1OUQ3NzZEVkxyZHNYU2pkaEd1ZXdDamNybWFhYzk3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvc29wb3J0ZSIsInJvdXRlIjoic29wb3J0ZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785716323),
('zRggbbNvb2RdCR5a0AN7Xpa1xILpUEWhNj2WCddV',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJEb1JrczkwWWFEN2YyM3ZjdmlnN1V6S09NdnhEdlFVZDlKZTl0c0JwIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvbWVya2FtaWdvLnRlc3RcL2VtcHJlbmRlZG9yZXMiLCJyb3V0ZSI6ImVtcHJlbmRlZG9yZXMuaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',	1785710995),
('zsZZktODjogrn77F41D8AU2XXZQMwKhX156sl3bi',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiIzRk15cmEzSTJTZW1XcXFzSkJpM0J0ZFI1eTVOakNBdGZRUXhYekpNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcGxhemFcL3ppcGFxdWlyYSIsInJvdXRlIjoiYnVzY2FyIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785713021),
('zTtcMItRLC44u7iluRI4ytnnnbbM3dl8tLtH4CHg',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJvNFh6UUxnUFJjdUl5VXl5cUtEZFFaR25BNmoybmNGZUUzR1VneHRlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785715504),
('zuFLYLKKn7pRaHOx54MxyOqIKVI7M2PVDhzCO5ni',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJWYUxwenNPdVlOazA4NmRyakV6dFFiRjFzN0tkcVpuS2dxUTk2TmVwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvaW5ncmVzYXIiLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785711311),
('ZWE83DOH1oeOLATxwRT2h360JVtuQEteoTL8MKsk',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJYaEJ1STRWSHVyN3ZUbVh2Rk41OGNWSVFpcjlZalBrVFdBb2JicUM4IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL21lcmthbWlnby50ZXN0XC9lbXByZW5kZWRvcmVzXC9uZWdvY2lvc1wvM1wvb3BvcnR1bmlkYWRlcyIsInJvdXRlIjoiZW1wcmVuZGVkb3Jlcy5uZWdvY2lvcy5vcG9ydHVuaWRhZGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785714245),
('ZzJ4Nb30PQdycpuP8C8FnSw8nt3rZd1Fw1Ivp057',	NULL,	'127.0.0.1',	'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',	'eyJfdG9rZW4iOiJOaHJBNUdkYUhiQWcyWDc1MHZudk1IZ2FUV3VENDFZYVRQMUNKQnNvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9tZXJrYW1pZ28udGVzdFwvcHJlZ3VudGFzLWZyZWN1ZW50ZXMiLCJyb3V0ZSI6InByZWd1bnRhcy1mcmVjdWVudGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',	1785716261);

DROP TABLE IF EXISTS `storefronts`;
CREATE TABLE `storefronts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('borrador','publicado','suspendido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `storefronts_business_id_unique` (`business_id`),
  CONSTRAINT `storefronts_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `storefronts` (`id`, `business_id`, `headline`, `description`, `cover_path`, `cover_alt_text`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1,	1,	'Creamos aplicaciones intuitivas que simplifican procesos',	'Inggen crea aplicaciones empresariales intuitivas que simplifican procesos, aumentan productividad y mejoran la experiencia de clientes y equipos.',	'storefronts/1/Yfu4WIGADgmnu7Q8R9xqzn9IE9L127DeeYPgjz7B.jpg',	'',	'publicado',	'2026-08-01 03:11:07',	'2026-08-01 03:07:04',	'2026-08-01 04:11:36'),
(3,	3,	'Cortinas y Persianas de Lujo',	'Cortinas y persianas a medida con instalación incluida en Cajicá, Chía y Bogotá. Blackout, panel japonés, motorizadas y más. ¡Cotiza gratis!',	'storefronts/3/54HHgLYD2Td4aZRr0Dpc202Fwdqwva4SBExyHSci.webp',	'',	'publicado',	'2026-08-03 04:19:08',	'2026-07-29 18:44:04',	'2026-08-03 04:19:08'),
(5,	5,	'',	'El Hotel Bacatá Plaza, está ubicado en Zipaquirá a 600 metros de la Catedral de Sal. Allí encontrará modernas suites y habitaciones, para su descanso.',	'storefronts/5/N5vSuVrdLx4HAUOUOQERjuvTHWPY1w8AVSNLAz0h.jpg',	'',	'publicado',	'2026-08-02 08:10:01',	'2026-08-02 08:06:30',	'2026-08-02 08:11:13');

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `current_period_starts_at` timestamp NULL DEFAULT NULL,
  `current_period_ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `grace_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_plan_id_foreign` (`plan_id`),
  KEY `subscriptions_business_id_status_index` (`business_id`,`status`),
  CONSTRAINT `subscriptions_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subscriptions` (`id`, `business_id`, `plan_id`, `status`, `trial_ends_at`, `current_period_starts_at`, `current_period_ends_at`, `cancelled_at`, `grace_ends_at`, `created_at`, `updated_at`) VALUES
(1,	1,	2,	'prueba',	'2026-08-15 22:55:52',	'2026-08-01 22:55:52',	'2026-09-01 22:55:52',	NULL,	NULL,	'2026-08-01 22:55:52',	'2026-08-01 22:55:52'),
(2,	3,	2,	'prueba',	'2026-08-17 04:39:36',	'2026-08-03 04:39:36',	'2026-09-03 04:39:36',	NULL,	NULL,	'2026-08-03 04:39:36',	'2026-08-03 04:39:36'),
(3,	3,	1,	'activa',	NULL,	'2026-08-03 04:41:09',	NULL,	NULL,	NULL,	'2026-08-03 04:41:09',	'2026-08-03 04:41:09');

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `resolution_note` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_tickets_user_id_foreign` (`user_id`),
  KEY `support_tickets_resolved_by_foreign` (`resolved_by`),
  KEY `support_tickets_status_index` (`status`),
  CONSTRAINT `support_tickets_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `support_tickets` (`id`, `user_id`, `contact_email`, `subject`, `message`, `status`, `resolution_note`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1,	2,	NULL,	'No me permite agregar fondos a mi cuenta',	'sdfsdfsdf',	'en_progreso',	NULL,	2,	'2026-08-01 22:43:12',	'2026-08-01 22:42:47',	'2026-08-01 22:43:12');

DROP TABLE IF EXISTS `user_devices`;
CREATE TABLE `user_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `platform` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `push_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_devices_push_token_unique` (`push_token`),
  KEY `user_devices_user_id_foreign` (`user_id`),
  CONSTRAINT `user_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `avatar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_recently_viewed` tinyint(1) NOT NULL DEFAULT '0',
  `notification_channel_preferences` json DEFAULT NULL,
  `experience` enum('cliente','emprendedor') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `terms_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `phone`, `phone_verified_at`, `avatar_path`, `remember_recently_viewed`, `notification_channel_preferences`, `experience`, `password`, `terms_accepted_at`, `terms_version`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1,	'Test User',	'test@example.com',	'2026-08-01 02:47:35',	NULL,	NULL,	'users/1/HaNYC0KaUdbIrH8NJljXuE7OT0kiB78AGdeiEsNc.jpg',	0,	NULL,	'cliente',	'$2y$12$6sTf8TP4rHUm5S1dZ0M.VOm5Rfzmz3k.2RVziGAPEyDVdcxS7fD1G',	NULL,	NULL,	NULL,	NULL,	NULL,	'AHmoVjOF50TOKrHohip7tFCKA1PP2HSUX7PqVEiBN2b7m1uAY5xP5uhZ5UVt',	'2026-08-01 02:47:35',	'2026-08-03 05:02:39'),
(2,	'John Alexander Ramirez',	'inggensas@gmail.com',	NULL,	'+573213407772',	NULL,	'users/2/pj4dNxoJMeMjCUZfHBMrsykAUOGO6BB3gvKwq3wI.png',	0,	NULL,	'cliente',	'$2y$12$UQZOmBQnvKHtETPZeLLFze1048TAzT2UrREsuNLAJz2SyA/FflrtO',	'2026-08-01 03:06:24',	'1.1',	NULL,	NULL,	NULL,	'QF2owd7UWOaGJBn2vCpF4vGgJuq7kjo3519bHFjqebd81yfoAUCVY2JrbB9E',	'2026-08-01 03:06:25',	'2026-08-03 05:07:21');

DROP TABLE IF EXISTS `webhook_subscriptions`;
CREATE TABLE `webhook_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscribed_events` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_subscriptions_business_id_foreign` (`business_id`),
  CONSTRAINT `webhook_subscriptions_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `whatsapp_contents`;
CREATE TABLE `whatsapp_contents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_for` date DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `whatsapp_contents_product_id_foreign` (`product_id`),
  KEY `whatsapp_contents_business_id_created_at_index` (`business_id`,`created_at`),
  CONSTRAINT `whatsapp_contents_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_contents_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `wompi_settings`;
CREATE TABLE `wompi_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `active_env` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `sandbox_public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sandbox_private_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sandbox_integrity_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sandbox_events_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production_public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production_private_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production_integrity_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production_events_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wompi_settings` (`id`, `active_env`, `sandbox_public_key`, `sandbox_private_key`, `sandbox_integrity_secret`, `sandbox_events_secret`, `production_public_key`, `production_private_key`, `production_integrity_secret`, `production_events_secret`, `created_at`, `updated_at`) VALUES
(1,	'sandbox',	'pub_test_cKus3zTdsZkkdFTWNQAuIZhBIoRa6tjZ',	'prv_test_r0cITfXKtbGQAMPThlGPlSBiqDQmksHu',	'test_integrity_3KV0b5nkXDLn92wrGDrafqBdLVmn4x6C',	'test_events_9d5s3zSMQNl14phseTQfM5T2CuKSNgFN',	'pub_prod_vyGRtfoXYj4cV7tbHPAVsFxPBJCgFRdY',	'prv_prod_TA8PYPtQzMOYiuBhO21Xkx2xxXCqiAAV',	'prod_integrity_2AjsWN4gHeuKlsDlOaX6eAme93Xi7dE5',	'prod_events_c5ChIphL9mZmS4IQIsBHmY3dXL2RqOY0',	'2026-08-01 22:47:43',	'2026-08-01 22:51:11');

DROP TABLE IF EXISTS `wompi_webhook_events`;
CREATE TABLE `wompi_webhook_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wompi_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `checksum` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wompi_webhook_events_checksum_unique` (`checksum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2026-08-03 00:56:42
