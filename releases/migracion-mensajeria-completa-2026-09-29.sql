-- Reemplaza al archivo migracion-mensajeria-adjuntos-2026-09-29.sql (bórralo,
-- ese asumía que las tablas de mensajería ya existían en producción y
-- probablemente por eso falló: NUNCA se crearon).
--
-- Este script arma TODO desde cero, en el orden correcto: primero crea las
-- tablas base del chat interno, después agrega los campos de adjuntos y
-- borrado suave. Corre cada bloque en orden. Si algún ALTER te da
-- "Duplicate column name" es porque esa columna específica ya existía de
-- antes — está bien, sáltate solo esa línea y sigue con la siguiente.

-- 1) Canal de contacto del negocio (necesario para que el botón "Mensaje"
--    interno funcione en la vitrina).
ALTER TABLE `businesses`
    ADD COLUMN `contact_channel` VARCHAR(24) NOT NULL DEFAULT 'merkamigo' AFTER `whatsapp_number`;

UPDATE `businesses`
SET `contact_channel` = 'whatsapp'
WHERE `whatsapp_number` IS NOT NULL AND `whatsapp_number` != '';

-- 2) Tablas del chat interno (conversaciones y mensajes).
CREATE TABLE IF NOT EXISTS `business_conversations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `customer_user_id` BIGINT UNSIGNED NOT NULL,
    `context_key` VARCHAR(100) NOT NULL DEFAULT 'business',
    `context_type` VARCHAR(24) NULL,
    `context_id` BIGINT UNSIGNED NULL,
    `context_label` VARCHAR(255) NULL,
    `context_url` VARCHAR(2048) NULL,
    `last_message_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `business_conversations_unique_context` (`business_id`, `customer_user_id`, `context_key`),
    KEY `business_conversations_business_id_last_message_at_index` (`business_id`, `last_message_at`),
    KEY `business_conversations_customer_user_id_last_message_at_index` (`customer_user_id`, `last_message_at`),
    CONSTRAINT `business_conversations_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `business_conversations_customer_user_id_foreign` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `business_messages` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_conversation_id` BIGINT UNSIGNED NOT NULL,
    `sender_user_id` BIGINT UNSIGNED NOT NULL,
    `body` TEXT NOT NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `business_messages_business_conversation_id_created_at_index` (`business_conversation_id`, `created_at`),
    KEY `business_messages_sender_user_id_read_at_index` (`sender_user_id`, `read_at`),
    CONSTRAINT `business_messages_business_conversation_id_foreign` FOREIGN KEY (`business_conversation_id`) REFERENCES `business_conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `business_messages_sender_user_id_foreign` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Adjuntos + borrado suave de conversaciones.
ALTER TABLE `business_messages`
    ADD COLUMN `attachment_path` VARCHAR(255) NULL AFTER `body`;

ALTER TABLE `business_conversations`
    ADD COLUMN `deleted_at` TIMESTAMP NULL;

-- 4) Registra ambas migraciones como aplicadas.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_24_090000_create_internal_messaging_tables', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_28_090000_add_attachments_and_soft_deletes_to_messaging', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;
