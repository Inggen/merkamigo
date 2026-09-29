-- URGENTE: esta es la migración que falta y está causando el error 500
-- en merkamigo.com/m/kebero-musica-cafe (y en cualquier otra vitrina).
-- No tiene relación con el SQL de producto_media que corriste antes; esa
-- tabla no se toca aquí.

-- 1) Opciones de contenido de la vitrina (mostrar/ocultar publicaciones,
--    reels, y el mapa embebido de Google Maps).
ALTER TABLE `storefronts`
    ADD COLUMN `show_posts` TINYINT(1) NOT NULL DEFAULT 1 AFTER `stand_color`,
    ADD COLUMN `show_reels` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_posts`,
    ADD COLUMN `google_maps_embed_url` TEXT NULL AFTER `show_reels`;

-- 2) Historias destacadas (columna que causó el error de hoy).
ALTER TABLE `stories`
    ADD COLUMN `is_highlighted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `views_count`,
    ADD INDEX `stories_business_id_is_highlighted_index` (`business_id`, `is_highlighted`);

-- 3) Registra la migración como aplicada.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_28_220000_add_content_options_to_storefronts_and_stories', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;
