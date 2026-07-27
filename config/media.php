<?php

// Límites de archivos por contexto (0.6 del TODO: "validar y limitar
// archivos por tipo, tamaño y cantidad"). Todavía no hay una funcionalidad
// de subida construida (llega en Fase 1/3); estas reglas quedan listas
// para cuando se implemente, evitando decidir los límites bajo presión.

return [

    'avatar' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 2048,
        'max_files' => 1,
    ],

    'business_logo' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 2048,
        'max_files' => 1,
    ],

    'product_photo' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 5120,
        'max_files' => 8,
    ],

    'storefront_cover' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 5120,
        'max_files' => 1,
    ],

    'verification_document' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_kb' => 8192,
        'max_files' => 5,
    ],

    'need_photo' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 5120,
        'max_files' => 4,
    ],

];
