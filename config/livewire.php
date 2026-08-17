<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Payload Guards
    |---------------------------------------------------------------------------
    |
    | El editor espacial de plaza (`PlazaSpatialEditor`) mantiene toda la
    | escena (props, slots, definiciones de objetos) en una propiedad
    | pública de Livewire, así que se serializa completa en cada petición.
    | Una plaza con bastante contenido ya supera el 1MB por defecto de
    | Livewire (`PayloadTooLargeException` real reportada por el usuario en
    | el editor-espacial admin) — se sube el límite en vez de bajar cuánto
    | manda el componente, que exigiría reestructurar cómo se sincroniza el
    | estado del visor 3D con el servidor.
    |
    | Solo se sobreescribe `payload` — Laravel hace `mergeConfigFrom()` a
    | nivel de la clave raíz, no un merge profundo, así que hay que repetir
    | el resto de sub-claves del default del paquete para no perderlas.
    |
    */

    'payload' => [
        'max_size' => 8 * 1024 * 1024, // 8MB (default del paquete: 1MB)
        'max_nesting_depth' => 10,
        'max_calls' => 50,
        'max_components' => 200,
    ],
];
