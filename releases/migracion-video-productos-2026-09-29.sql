-- Ejecutar en phpMyAdmin (o el gestor SQL de cPanel) sobre la base de datos
-- de producción de Merkamigo. Son dos pasos independientes; corre primero
-- el ALTER TABLE y confirma que no dio error antes de correr el INSERT.

-- 1) Agrega la columna que distingue foto/video en la galería de productos.
--    Aditiva: no borra ni modifica datos existentes. Todas las filas
--    actuales quedan marcadas como 'image' (su valor real ya lo eran).
ALTER TABLE `product_media`
    ADD COLUMN `type` VARCHAR(20) NOT NULL DEFAULT 'image' AFTER `path`,
    ADD INDEX `product_media_product_id_type_index` (`product_id`, `type`);

-- 2) Registra la migración como ya ejecutada, para que si en el futuro
--    corres `php artisan migrate` (por ejemplo si consigues acceso a
--    terminal), Laravel no intente aplicarla de nuevo y falle porque la
--    columna ya existe.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_28_230000_add_type_to_product_media_table', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;
