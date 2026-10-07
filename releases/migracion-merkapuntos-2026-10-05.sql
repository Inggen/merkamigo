-- Ejecutar en phpMyAdmin (o el gestor SQL de cPanel) sobre la base de datos
-- de producción de Merkamigo. TODO_Merkapuntos.md, F5.5: "Migraciones
-- aditivas... no exponer una ruta web pública para migrar." Este proyecto
-- no tiene acceso a CLI/artisan en producción, así que este script
-- equivale exactamente a las tres migraciones de Merkapuntos ya probadas
-- en local:
--   - 2026_10_04_090000_create_loyalty_tables
--   - 2026_10_04_120000_add_encrypted_value_to_loyalty_identity_tokens
--   - 2026_10_04_130000_add_encrypted_value_to_loyalty_redemptions
--
-- 100% aditivo: crea 8 tablas nuevas (prefijo `loyalty_`), ninguna tabla
-- existente se modifica ni se borra. `CREATE TABLE IF NOT EXISTS` hace el
-- script seguro de correr más de una vez. El orden importa: cada tabla
-- hace referencia (`FOREIGN KEY`) solo a tablas que ya existen en
-- producción o a tablas `loyalty_*` creadas antes en este mismo script.
--
-- Después de correr esto, el programa de recompensas sigue APAGADO para
-- los visitantes: el interruptor global es `LOYALTY_ENABLED` en el `.env`
-- de producción (por defecto `false`, ver `config/loyalty.php`), y cada
-- negocio además necesita su propia adhesión activa (`loyalty_enrollments.status
-- = 'activa'`) con una política publicada — nada de esto se activa solo
-- por correr este script. No hay ningún dato de ejemplo ni valores de
-- política en este archivo: eso es una decisión de cada negocio, no un
-- paso de esta migración (ver "Decisiones pendientes" en TODO_Merkapuntos.md).

-- 1) Adhesión de un negocio al programa.
CREATE TABLE IF NOT EXISTS `loyalty_enrollments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    `consent_version` VARCHAR(20) NOT NULL,
    `consented_at` TIMESTAMP NOT NULL,
    `responsible_user_id` BIGINT UNSIGNED NOT NULL,
    `budget_cents` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `loyalty_enrollments_business_id_unique` (`business_id`),
    KEY `loyalty_enrollments_status_index` (`status`),
    CONSTRAINT `loyalty_enrollments_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_enrollments_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Política de acumulación, versionada por negocio.
CREATE TABLE IF NOT EXISTS `loyalty_policies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `version` INT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'borrador',
    `rule` JSON NOT NULL,
    `eligible_base_notes` TEXT NULL,
    `effective_from` TIMESTAMP NULL,
    `effective_until` TIMESTAMP NULL,
    `created_by_user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `loyalty_policies_business_id_version_unique` (`business_id`, `version`),
    KEY `loyalty_policies_business_id_status_index` (`business_id`, `status`),
    CONSTRAINT `loyalty_policies_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_policies_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) QR de identificación del cliente (F1.8). `token_encrypted` se agrega
--    más abajo (paso 9) — se separó en su propia migración el mismo día
--    que se detectó la necesidad de poder re-mostrar el QR sin rotarlo.
CREATE TABLE IF NOT EXISTS `loyalty_identity_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `rotated_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `loyalty_identity_tokens_user_id_unique` (`user_id`),
    UNIQUE KEY `loyalty_identity_tokens_token_hash_unique` (`token_hash`),
    CONSTRAINT `loyalty_identity_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Cuenta de puntos del cliente, única por (negocio, cliente).
CREATE TABLE IF NOT EXISTS `loyalty_accounts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'activa',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `loyalty_accounts_business_id_user_id_unique` (`business_id`, `user_id`),
    CONSTRAINT `loyalty_accounts_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Libro de movimientos — única fuente de verdad del saldo (F1.2).
CREATE TABLE IF NOT EXISTS `loyalty_movements` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `account_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(24) NOT NULL,
    `points` INT NOT NULL,
    `reference_type` VARCHAR(255) NULL,
    `reference_id` BIGINT UNSIGNED NULL,
    `idempotency_key` VARCHAR(80) NOT NULL,
    `actor_user_id` BIGINT UNSIGNED NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `loyalty_movements_idempotency_key_unique` (`idempotency_key`),
    KEY `loyalty_movements_reference_type_reference_id_index` (`reference_type`, `reference_id`),
    KEY `loyalty_movements_account_id_created_at_index` (`account_id`, `created_at`),
    CONSTRAINT `loyalty_movements_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `loyalty_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_movements_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) Compra presencial registrada por un empleado autorizado (F1.3/F1.4).
CREATE TABLE IF NOT EXISTS `loyalty_purchases` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `customer_user_id` BIGINT UNSIGNED NOT NULL,
    `eligible_amount_cents` BIGINT UNSIGNED NOT NULL,
    `origin` VARCHAR(20) NOT NULL DEFAULT 'presencial',
    `external_reference` VARCHAR(120) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'registrada',
    `policy_id` BIGINT UNSIGNED NOT NULL,
    `policy_snapshot` JSON NOT NULL,
    `points_awarded` INT UNSIGNED NOT NULL,
    `employee_user_id` BIGINT UNSIGNED NOT NULL,
    `idempotency_key` VARCHAR(80) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `loyalty_purchases_business_id_external_reference_unique` (`business_id`, `external_reference`),
    UNIQUE KEY `loyalty_purchases_idempotency_key_unique` (`idempotency_key`),
    KEY `loyalty_purchases_business_id_customer_user_id_created_at_index` (`business_id`, `customer_user_id`, `created_at`),
    CONSTRAINT `loyalty_purchases_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_purchases_customer_user_id_foreign` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `loyalty_purchases_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `loyalty_policies` (`id`),
    CONSTRAINT `loyalty_purchases_employee_user_id_foreign` FOREIGN KEY (`employee_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) Premio publicado por un negocio (F3.5). El presupuesto/stock se
--    comprometen aquí mismo con columnas agregadas — ver nota en la
--    migración original sobre por qué no hay una tabla aparte de
--    "reserva de presupuesto".
CREATE TABLE IF NOT EXISTS `loyalty_rewards` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `type` VARCHAR(20) NOT NULL DEFAULT 'producto',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `points_cost` INT UNSIGNED NOT NULL,
    `full_cost_cents` BIGINT UNSIGNED NOT NULL,
    `stock_total` INT UNSIGNED NULL,
    `stock_reserved` INT UNSIGNED NOT NULL DEFAULT 0,
    `stock_delivered` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_budget_cents` BIGINT UNSIGNED NULL,
    `budget_reserved_cents` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `budget_spent_cents` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `valid_from` DATE NULL,
    `valid_until` DATE NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'borrador',
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `terms` TEXT NULL,
    `created_by_user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `loyalty_rewards_business_id_status_index` (`business_id`, `status`),
    CONSTRAINT `loyalty_rewards_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_rewards_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
    CONSTRAINT `loyalty_rewards_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8) Canje reservado por un cliente (F1.5/F1.6/F2.5). `token_encrypted`
--    se agrega más abajo (paso 9).
CREATE TABLE IF NOT EXISTS `loyalty_redemptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `account_id` BIGINT UNSIGNED NOT NULL,
    `reward_id` BIGINT UNSIGNED NOT NULL,
    `reward_snapshot` JSON NOT NULL,
    `points_reserved` INT UNSIGNED NOT NULL,
    `cost_reserved_cents` BIGINT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'reservado',
    `delivered_by_user_id` BIGINT UNSIGNED NULL,
    `delivered_at` TIMESTAMP NULL,
    `cancelled_reason` VARCHAR(60) NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `idempotency_key` VARCHAR(80) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `loyalty_redemptions_token_hash_unique` (`token_hash`),
    UNIQUE KEY `loyalty_redemptions_idempotency_key_unique` (`idempotency_key`),
    KEY `loyalty_redemptions_account_id_status_index` (`account_id`, `status`),
    KEY `loyalty_redemptions_status_expires_at_index` (`status`, `expires_at`),
    CONSTRAINT `loyalty_redemptions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `loyalty_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_redemptions_reward_id_foreign` FOREIGN KEY (`reward_id`) REFERENCES `loyalty_rewards` (`id`),
    CONSTRAINT `loyalty_redemptions_delivered_by_user_id_foreign` FOREIGN KEY (`delivered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8b) Solicitud excepcional con recibo (F2.7). `receipt_path` apunta al
--     disco `private` — nunca se sirve una URL directa y permanente.
CREATE TABLE IF NOT EXISTS `loyalty_receipt_claims` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `customer_user_id` BIGINT UNSIGNED NOT NULL,
    `receipt_path` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    `linked_purchase_id` BIGINT UNSIGNED NULL,
    `reviewed_by_user_id` BIGINT UNSIGNED NULL,
    `reviewed_at` TIMESTAMP NULL,
    `decision_reason` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    KEY `loyalty_receipt_claims_business_id_status_index` (`business_id`, `status`),
    CONSTRAINT `loyalty_receipt_claims_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `loyalty_receipt_claims_customer_user_id_foreign` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `loyalty_receipt_claims_linked_purchase_id_foreign` FOREIGN KEY (`linked_purchase_id`) REFERENCES `loyalty_purchases` (`id`) ON DELETE SET NULL,
    CONSTRAINT `loyalty_receipt_claims_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) Copia cifrada de ambos tokens (F1.8/F2.5): permite volver a mostrar
--    el mismo QR de identidad y el mismo código de canje sin haberlos
--    guardado nunca en texto plano — `token_hash` sigue siendo lo único
--    que usa el escáner del negocio para validar. Si alguna de las dos
--    columnas ya existe (por ejemplo porque ya corriste este script
--    antes), MySQL da "Duplicate column name" en esa línea — está bien,
--    sáltatela y sigue con la siguiente.
ALTER TABLE `loyalty_identity_tokens`
    ADD COLUMN `token_encrypted` TEXT NULL AFTER `token_hash`;

ALTER TABLE `loyalty_redemptions`
    ADD COLUMN `token_encrypted` TEXT NULL AFTER `token_hash`;

-- 10) Registra las tres migraciones como aplicadas, para que si algún día
--     se obtiene acceso a CLI en producción, `php artisan migrate` no
--     intente volver a crear estas tablas.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_04_090000_create_loyalty_tables', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_04_120000_add_encrypted_value_to_loyalty_identity_tokens', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_04_130000_add_encrypted_value_to_loyalty_redemptions', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;

-- No incluido a propósito: ninguna fila de ejemplo en `loyalty_enrollments`,
-- `loyalty_policies` ni `loyalty_rewards`. Cada negocio se adhiere y
-- publica su política desde `/emprendedores/negocios/{id}/merkapuntos` —
-- eso es trabajo del propio negocio, no de este script. Recuerda también
-- poner `LOYALTY_ENABLED=true` en el `.env` de producción cuando el
-- piloto esté listo para arrancar (F5.6) — mientras tanto el programa
-- sigue completamente apagado aunque las tablas ya existan.
