<?php

/**
 * Pedido del usuario (2026-10-06): "revisa que todos los mensajes de
 * validación estén en español" — el proyecto tenía `APP_LOCALE=es` pero
 * nunca publicó este archivo, así que cualquier regla sin mensaje
 * personalizado (la inmensa mayoría de los `->validate([...])` de todo
 * el proyecto) caía al `validation.php` en inglés que trae Laravel por
 * defecto (`vendor/laravel/framework/.../lang/en/validation.php`) — el
 * caso exacto reportado: "The attendee phone field is required."
 *
 * Traducción completa de las ~100 reglas de validación de Laravel, más
 * el mapa de `attributes`: los ~200 nombres de campo reales que se
 * validan en todo el proyecto (extraídos de cada `->validate([...])` y
 * `Validator::make(...)` existente), para que el propio nombre del
 * campo también salga en español — sin este mapa, ":attribute" se
 * rellena con el nombre crudo del campo (ej. "attendee phone" en vez de
 * "teléfono"), que seguía quedando en inglés aunque el resto de la
 * frase ya estuviera traducido.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Líneas de idioma para validación
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas contienen los mensajes de error por defecto
    | que usa la clase Validator. Algunas de estas reglas tienen varias
    | versiones, como las reglas de tamaño. Siéntete libre de ajustar
    | cada uno de estos mensajes aquí.
    |
    */

    'accepted' => 'El campo :attribute debe ser aceptado.',
    'accepted_if' => 'El campo :attribute debe ser aceptado cuando :other es :value.',
    'active_url' => 'El campo :attribute debe ser una URL válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha' => 'El campo :attribute solo debe contener letras.',
    'alpha_dash' => 'El campo :attribute solo debe contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo debe contener letras y números.',
    'any_of' => 'El campo :attribute no es válido.',
    'array' => 'El campo :attribute debe ser una lista.',
    'ascii' => 'El campo :attribute solo debe contener caracteres alfanuméricos y símbolos de un solo byte.',
    'base64' => 'El campo :attribute debe ser una cadena Base64 válida.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file' => 'El campo :attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'can' => 'El campo :attribute contiene un valor no autorizado.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'contains' => 'Al campo :attribute le falta un valor requerido.',
    'current_password' => 'La contraseña es incorrecta.',
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'date_equals' => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute debe coincidir con el formato :format.',
    'decimal' => 'El campo :attribute debe tener :decimal decimales.',
    'declined' => 'El campo :attribute debe ser rechazado.',
    'declined_if' => 'El campo :attribute debe ser rechazado cuando :other es :value.',
    'different' => 'Los campos :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions' => 'El campo :attribute tiene dimensiones de imagen no válidas.',
    'distinct' => 'El campo :attribute tiene un valor duplicado.',
    'doesnt_contain' => 'El campo :attribute no debe contener ninguno de los siguientes valores: :values.',
    'doesnt_end_with' => 'El campo :attribute no debe terminar con ninguno de los siguientes: :values.',
    'doesnt_start_with' => 'El campo :attribute no debe comenzar con ninguno de los siguientes: :values.',
    'email' => 'El campo :attribute debe ser una dirección de correo electrónico válida.',
    'encoding' => 'El campo :attribute debe estar codificado en :encoding.',
    'ends_with' => 'El campo :attribute debe terminar con uno de los siguientes: :values.',
    'enum' => 'El valor seleccionado para :attribute no es válido.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'extensions' => 'El campo :attribute debe tener una de las siguientes extensiones: :values.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'filled' => 'El campo :attribute debe tener un valor.',
    'gt' => [
        'array' => 'El campo :attribute debe tener más de :value elementos.',
        'file' => 'El campo :attribute debe pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'string' => 'El campo :attribute debe tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => 'El campo :attribute debe tener :value elementos o más.',
        'file' => 'El campo :attribute debe pesar :value kilobytes o más.',
        'numeric' => 'El campo :attribute debe ser mayor o igual que :value.',
        'string' => 'El campo :attribute debe tener :value caracteres o más.',
    ],
    'hex_color' => 'El campo :attribute debe ser un color hexadecimal válido.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'in_array' => 'El campo :attribute debe existir en :other.',
    'in_array_keys' => 'El campo :attribute debe contener al menos una de las siguientes claves: :values.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'ip' => 'El campo :attribute debe ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute debe ser una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute debe ser una dirección IPv6 válida.',
    'json' => 'El campo :attribute debe ser una cadena JSON válida.',
    'list' => 'El campo :attribute debe ser una lista.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'lt' => [
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
        'file' => 'El campo :attribute debe pesar menos de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'string' => 'El campo :attribute debe tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'El campo :attribute no debe tener más de :value elementos.',
        'file' => 'El campo :attribute debe pesar :value kilobytes o menos.',
        'numeric' => 'El campo :attribute debe ser menor o igual que :value.',
        'string' => 'El campo :attribute debe tener :value caracteres o menos.',
    ],
    'mac_address' => 'El campo :attribute debe ser una dirección MAC válida.',
    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El campo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'max_digits' => 'El campo :attribute no debe tener más de :max dígitos.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'min_digits' => 'El campo :attribute debe tener al menos :min dígitos.',
    'missing' => 'El campo :attribute debe estar ausente.',
    'missing_if' => 'El campo :attribute debe estar ausente cuando :other es :value.',
    'missing_unless' => 'El campo :attribute debe estar ausente a menos que :other sea :value.',
    'missing_with' => 'El campo :attribute debe estar ausente cuando :values está presente.',
    'missing_with_all' => 'El campo :attribute debe estar ausente cuando :values están presentes.',
    'multiple_of' => 'El campo :attribute debe ser un múltiplo de :value.',
    'not_in' => 'El valor seleccionado para :attribute no es válido.',
    'not_regex' => 'El formato del campo :attribute no es válido.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'password' => [
        'letters' => 'El campo :attribute debe contener al menos una letra.',
        'mixed' => 'El campo :attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute debe contener al menos un número.',
        'symbols' => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'El valor indicado para :attribute ha aparecido en una filtración de datos. Elige un :attribute diferente.',
    ],
    'present' => 'El campo :attribute debe estar presente.',
    'present_if' => 'El campo :attribute debe estar presente cuando :other es :value.',
    'present_unless' => 'El campo :attribute debe estar presente a menos que :other sea :value.',
    'present_with' => 'El campo :attribute debe estar presente cuando :values está presente.',
    'present_with_all' => 'El campo :attribute debe estar presente cuando :values están presentes.',
    'prohibited' => 'El campo :attribute está prohibido.',
    'prohibited_if' => 'El campo :attribute está prohibido cuando :other es :value.',
    'prohibited_if_accepted' => 'El campo :attribute está prohibido cuando :other es aceptado.',
    'prohibited_if_declined' => 'El campo :attribute está prohibido cuando :other es rechazado.',
    'prohibited_unless' => 'El campo :attribute está prohibido a menos que :other esté en :values.',
    'prohibits' => 'El campo :attribute impide que :other esté presente.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_array_keys' => 'El campo :attribute debe contener entradas para: :values.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted' => 'El campo :attribute es obligatorio cuando :other es aceptado.',
    'required_if_declined' => 'El campo :attribute es obligatorio cuando :other es rechazado.',
    'required_unless' => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de :values está presente.',
    'same' => 'Los campos :attribute y :other deben coincidir.',
    'size' => [
        'array' => 'El campo :attribute debe contener :size elementos.',
        'file' => 'El campo :attribute debe pesar :size kilobytes.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute debe comenzar con uno de los siguientes: :values.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'timezone' => 'El campo :attribute debe ser una zona horaria válida.',
    'unique' => 'El valor del campo :attribute ya ha sido registrado.',
    'uploaded' => 'No se pudo subir el archivo de :attribute.',
    'uppercase' => 'El campo :attribute debe estar en mayúsculas.',
    'url' => 'El campo :attribute debe ser una URL válida.',
    'ulid' => 'El campo :attribute debe ser un ULID válido.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Líneas de idioma personalizadas
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar mensajes de validación personalizados para
    | atributos usando la convención "atributo.regla" para nombrar las
    | líneas. Esto permite especificar rápido un mensaje a medida para
    | una regla puntual de un campo puntual.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de atributos personalizados
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas reemplazan el marcador :attribute por algo
    | más amigable para quien lee, por ejemplo "Correo electrónico" en
    | vez de "email". Esto ayuda a que el mensaje final se lea natural.
    |
    | Lista extraída de todos los `->validate([...])` y
    | `Validator::make(...)` reales de este proyecto (2026-10-06) — no
    | son nombres inventados, son los campos que de verdad se validan.
    |
    */

    'attributes' => [
        // Comunes / compartidos entre varios formularios.
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'current_password' => 'contraseña actual',
        'login' => 'correo o teléfono',
        'address' => 'dirección',
        'message' => 'mensaje',
        'subject' => 'asunto',
        'reason' => 'motivo',
        'status' => 'estado',
        'type' => 'tipo',
        'role' => 'rol',
        'quantity' => 'cantidad',
        'description' => 'descripción',
        'content' => 'contenido',
        'body' => 'texto',
        'title' => 'título',
        'headline' => 'titular',
        'caption' => 'descripción',
        'terms' => 'términos y condiciones',
        'file' => 'archivo',
        'document' => 'documento',
        'attachment' => 'archivo adjunto',
        'video' => 'video',
        'path' => 'ruta',
        'metadata' => 'metadatos',
        'details' => 'detalles',
        'action' => 'acción',
        'history' => 'historial',
        'question' => 'pregunta',
        'mode' => 'modo',
        'version' => 'versión',
        'app_version' => 'versión de la app',
        'frequency' => 'frecuencia',
        'id' => 'identificador',
        'date' => 'fecha',
        'business_id' => 'negocio',
        'category_id' => 'categoría',
        'categoria_id' => 'categoría',
        'product_id' => 'producto',
        'product_ids' => 'productos',
        'municipality_id' => 'municipio',
        'municipio_id' => 'municipio',
        'latitude' => 'latitud',
        'longitude' => 'longitud',
        'experience' => 'experiencia',
        'locked' => 'bloqueado',

        // Datos de contacto (formularios de pedidos, reclamos, soporte, etc.).
        'contact_name' => 'nombre de contacto',
        'contact_email' => 'correo de contacto',
        'contact_document_type' => 'tipo de documento',
        'contact_document_number' => 'número de documento',
        'contact_channel' => 'canal de contacto',
        'customer_email' => 'correo del cliente',
        'reporter_email' => 'correo de quien reporta',
        'whatsapp_number' => 'número de WhatsApp',
        'extra_notes' => 'notas adicionales',
        'request_note' => 'nota de la solicitud',

        // Reseñas/reportes.
        'reportReason' => 'motivo del reporte',
        'reportDetails' => 'detalle del reporte',

        // Eventos — reserva de cupo (widget "Reserva tu cupo").
        'attendeeName' => 'nombre',
        'attendeeEmail' => 'correo electrónico',
        'attendeePhone' => 'teléfono',

        // Eventos — reserva del espacio (cotizador de evento privado).
        'prospectName' => 'nombre',
        'prospectEmail' => 'correo electrónico',
        'prospectPhone' => 'teléfono',
        'partySize' => 'cantidad de personas',
        'durationHours' => 'duración en horas',
        'startTime' => 'hora de inicio',
        'selectedDate' => 'fecha elegida',
        'termsAccepted' => 'aceptación de términos',

        // Eventos — configuración del panel del negocio.
        'maxCapacity' => 'capacidad máxima',
        'maxDurationHours' => 'duración máxima en horas',
        'minAdvanceHours' => 'horas mínimas de anticipación',
        'holdMinutes' => 'minutos de retención',
        'policyText' => 'política',
        'cancellationText' => 'política de cancelación',
        'pricingMode' => 'modalidad de tarifa',
        'hourlyRateCop' => 'tarifa por hora',
        'spaceName' => 'nombre del espacio',
        'spaceCapacity' => 'capacidad del espacio',
        'spaceImage' => 'imagen del espacio',
        'spaceId' => 'espacio',
        'blockedDate' => 'fecha bloqueada',
        'blockedReason' => 'motivo del bloqueo',
        'dishName' => 'nombre del plato',
        'dishDescription' => 'descripción del plato',
        'dishPriceCop' => 'precio del plato',
        'customEquipmentName' => 'nombre del equipo',
        'customEquipmentFeeCop' => 'costo del equipo',
        'eventTitle' => 'título del evento',
        'eventCategory' => 'categoría del evento',
        'eventDescription' => 'descripción del evento',
        'eventCover' => 'imagen de portada',
        'eventMunicipalityId' => 'municipio del evento',
        'eventLocationText' => 'ubicación del evento',
        'eventStartsAt' => 'fecha y hora de inicio',
        'eventEndsAt' => 'fecha y hora de fin',
        'eventCapacity' => 'cupo del evento',
        'eventBlocksSpace' => 'bloqueo del espacio',
        'eventSpaceId' => 'espacio del evento',

        // Merkapuntos.
        'rewardTitle' => 'título del premio',
        'rewardProductId' => 'producto del premio',
        'rewardImage' => 'imagen del premio',
        'rewardPointsCost' => 'costo en puntos',
        'rewardFullCostCop' => 'costo real del premio',
        'rewardStockTotal' => 'unidades disponibles',
        'rewardValidUntil' => 'vigencia del premio',
        'receiptClaimBusinessId' => 'negocio',
        'receiptDescription' => 'descripción de la compra',
        'receiptFile' => 'foto del recibo',

        // Vitrina / negocio.
        'legal_name' => 'razón social',
        'logo' => 'logo',
        'logo_alt_text' => 'texto alternativo del logo',
        'cover' => 'imagen de portada',
        'cover_alt_text' => 'texto alternativo de la portada',
        'remove_logo' => 'eliminar logo',
        'remove_cover' => 'eliminar portada',
        'has_physical_location' => 'punto físico',
        'show_posts' => 'mostrar publicaciones',
        'show_reels' => 'mostrar reels',
        'social_links' => 'redes sociales',
        'social_links.website' => 'sitio web',
        'google_maps_embed_url' => 'enlace de Google Maps',
        'google_business_store_code' => 'código de tienda de Google Business',
        'benefits' => 'beneficios',
        'newAdImage' => 'imagen del anuncio',

        // Producto.
        'product_name' => 'nombre del producto',
        'product_description' => 'descripción del producto',
        'product_price' => 'precio del producto',
        'product_price_type' => 'tipo de precio',
        'product_type' => 'tipo de producto',
        'product_unit' => 'unidad del producto',
        'product_is_available' => 'disponibilidad del producto',
        'productId' => 'producto',

        // Pedidos y pagos.
        'direct_order_customer_email' => 'correo del cliente',
        'direct_order_summary' => 'resumen del pedido',
        'payment_info' => 'información de pago',
        'payment_method_ids' => 'métodos de pago',
        'card_brand' => 'marca de la tarjeta',
        'card_last_four' => 'últimos 4 dígitos de la tarjeta',
        'card_token' => 'token de la tarjeta',
        'wompi_payment_source_id' => 'fuente de pago',
        'public_key' => 'llave pública',
        'private_key' => 'llave privada',
        'integrity_secret' => 'secreto de integridad',
        'events_secret' => 'secreto de eventos',
        'environment' => 'entorno',
        'trial_days' => 'días de prueba',

        // Promociones / contenido destacado.
        'promotionCategoryId' => 'categoría de la promoción',
        'promotionMunicipalityId' => 'municipio de la promoción',
        'promotionRadiusKm' => 'radio de la promoción',
        'promotionTarget' => 'objetivo de la promoción',

        // Lives / transmisiones.
        'destination_name' => 'nombre del destino',
        'destination_provider' => 'proveedor del destino',
        'destination_rtmp_url' => 'URL RTMP',
        'destination_stream_key' => 'clave de transmisión',
        'scheduledFor' => 'fecha programada',
        'scheduled_for' => 'fecha programada',

        // Asistente / chat IA.
        'pagina_actual' => 'página actual',
        'paso_actual' => 'paso actual',

        // FAQ del negocio.
        'faqDisponibilidad' => 'disponibilidad',
        'faqDomicilio' => 'domicilios',
        'faqHorario' => 'horario',

        // Notificaciones push.
        'push_disabled' => 'notificaciones desactivadas',
        'push_token' => 'token de notificaciones',

        // Editor espacial de la Plaza inmersiva (panel interno).
        'boxes' => 'elementos',
        'groups' => 'grupos',
        'groupId' => 'grupo',
        'collidable' => 'colisión',
        'emissive' => 'color de brillo',
        'tiling' => 'repetición de textura',
        'texture' => 'textura',
        'tone' => 'tono',
        'stand_color' => 'color del stand',
        'rotationX' => 'rotación en X',
        'rotationY' => 'rotación en Y',
        'rotationZ' => 'rotación en Z',
        'length' => 'largo',
        'zone' => 'zona',
        'additional_municipality_ids' => 'municipios adicionales',
    ],

];
