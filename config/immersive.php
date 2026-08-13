<?php

// Escenas inmersivas disponibles. Cada entrada es una ruta ya registrada en
// `routes/web.php` que sirve una escena. Una `immersive_experience` elige
// aquí CUÁL escena usar (campo `route_name`); agregar una escena nueva sigue
// requiriendo código (vista + JS), pero asignarla a una experiencia/
// municipio ya no.

return [

    'available_scenes' => [
        'labs.generic-plaza' => 'Escena genérica — usa los datos de la plaza (sin código)',
    ],

    // IMM-020b: cota manual conservadora al número de cajas voxel que puede
    // tener una `model_definition` generada por IA (ver VoxelDefinitionValidator),
    // inspirada en el presupuesto de public/js/lib/immersive-perf-budget.js
    // pero no derivada mecánicamente de él — un stand real (buildStandBooth)
    // usa ~8 cajas, así que 40 deja margen amplio sin acercarse al límite de
    // draw calls de la escena completa.
    'voxel_definition' => [
        'max_boxes' => (int) env('IMMERSIVE_VOXEL_MAX_BOXES', 40),
    ],

];
