<?php

// Límites de archivos por contexto (0.6 del TODO: "validar y limitar
// archivos por tipo, tamaño y cantidad"). `max_width`, `target_extension`
// y `quality` (1.2 del TODO: "optimizar, comprimir y generar variantes
// de imágenes") se aplican en `App\Support\Media\MediaUploader`:
// orienta por EXIF, reduce el ancho si lo excede, nunca agranda, remueve
// metadatos al recodificar y, cuando el servidor soporta WebP, convierte
// automáticamente a ese formato con compresión suave. No aplica a
// `verification_document`, que admite PDF.

return [

    'avatar' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 2048,
        'max_files' => 1,
        'max_width' => 512,
        'target_extension' => 'webp',
        'quality' => 88,
    ],

    'business_logo' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 2048,
        'max_files' => 1,
        'max_width' => 512,
        'target_extension' => 'webp',
        'quality' => 90,
    ],

    'product_photo' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 5120,
        'max_files' => 8,
        'max_width' => 1600,
        'target_extension' => 'webp',
        'quality' => 85,
    ],

    'storefront_cover' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 5120,
        'max_files' => 1,
        'max_width' => 1920,
        'target_extension' => 'webp',
        'quality' => 86,
    ],

    'municipality_cover' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 5120,
        'max_files' => 1,
        'max_width' => 1920,
        'target_extension' => 'webp',
        'quality' => 86,
    ],

    'municipality_hero_video' => [
        'mimes' => ['mp4', 'webm', 'mov'],
        'max_kb' => 51200,
        'max_files' => 1,
    ],

    'verification_document' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_kb' => 8192,
        'max_files' => 5,
        'disk' => 'private',
    ],

    'need_photo' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 5120,
        'max_files' => 4,
        'max_width' => 1600,
        'target_extension' => 'webp',
        'quality' => 85,
    ],

];
