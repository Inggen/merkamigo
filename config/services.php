<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'merkamigo' => [
        'support_whatsapp' => env('MERKAMIGO_SUPPORT_WHATSAPP'),
    ],

    'google_merchant' => [
        'enabled' => env('GOOGLE_MERCHANT_ENABLED', false),
        // Inventario local (fichas locales sin costo / anuncios de
        // inventario local): interruptor aparte del feed principal.
        // Apagado por defecto — no hay negocios con Perfil de Empresa real
        // vinculado todavía (TODO-Google-Merchant.md, post-cierre). Aunque
        // esto esté en `true`, cada negocio también necesita marcar
        // `has_physical_location` y tener `google_business_store_code`.
        'local_inventory_enabled' => env('GOOGLE_MERCHANT_LOCAL_INVENTORY_ENABLED', false),
        'account_id' => env('GOOGLE_MERCHANT_ACCOUNT_ID'),
        'data_source_id' => env('GOOGLE_MERCHANT_DATA_SOURCE_ID'),
        'credentials' => env('GOOGLE_MERCHANT_CREDENTIALS', 'storage/app/private/google-merchant-service-account.json'),
        'content_language' => env('GOOGLE_MERCHANT_CONTENT_LANGUAGE', 'es'),
        'feed_label' => env('GOOGLE_MERCHANT_FEED_LABEL', 'CO'),
        'currency' => env('GOOGLE_MERCHANT_CURRENCY', 'COP'),
        'endpoint' => env('GOOGLE_MERCHANT_ENDPOINT', 'https://merchantapi.googleapis.com'),
        'timeout' => (int) env('GOOGLE_MERCHANT_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI
    |--------------------------------------------------------------------------
    |
    | Texto asistido por IA para copilotos y ayudas futuras. La app puede
    | seguir funcionando sin esto: si no hay API key/modelo o está apagado
    | en admin, simplemente cae al comportamiento manual/determinístico.
    |
    */
    'openai' => [
        'enabled' => env('OPENAI_ENABLED', false),
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL'),
        'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-2'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 30),
        'temperature' => env('OPENAI_TEMPERATURE'),
        'max_output_tokens' => env('OPENAI_MAX_OUTPUT_TOKENS'),
        'system_prompt' => env('OPENAI_SYSTEM_PROMPT'),
        'entrepreneur_copilot_enabled' => env('OPENAI_ENTREPRENEUR_COPILOT_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Wompi (4.2 del TODO)
    |--------------------------------------------------------------------------
    |
    | Pasarela de pago para Colombia. En sandbox, las llaves públicas de
    | prueba empiezan con "pub_test_"; en producción, "pub_prod_". Nunca se
    | reciben datos de tarjeta en este servidor — el checkout es 100%
    | hospedado por Wompi.
    |
    */
    'wompi' => [
        'env' => env('WOMPI_ENV', 'sandbox'),
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET'),
        'events_secret' => env('WOMPI_EVENTS_SECRET'),
        'checkout_url' => env('WOMPI_CHECKOUT_URL', 'https://checkout.wompi.co/p/'),
        'api_url' => env('WOMPI_ENV', 'sandbox') === 'production'
            ? 'https://production.wompi.co/v1'
            : 'https://sandbox.wompi.co/v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace (checkout de terceros)
    |--------------------------------------------------------------------------
    |
    | El pago del cliente va directo a la cuenta Wompi DEL NEGOCIO (ver
    | `App\Domain\Marketplace\Models\BusinessWompiCredential`) — Merkamigo
    | nunca recauda dinero de terceros. Esta tasa es solo la comisión de
    | Merkamigo por facilitar la venta, cobrada aparte contra la tarjeta
    | ya guardada del negocio (decisión del usuario, sesión del 15 sep
    | 2026). Un solo valor global por ahora — tasas distintas por plan
    | quedan para cuando haya demanda real de esa diferenciación.
    |
    */
    'marketplace' => [
        'commission_rate' => (float) env('MARKETPLACE_COMMISSION_RATE', 0.05),
    ],

    'live_streaming' => [
        'driver' => env('LIVE_STREAMING_DRIVER', 'mediamtx'),
        'webrtc_internal_url' => env('LIVE_STREAMING_WEBRTC_INTERNAL_URL', 'http://127.0.0.1:8889'),
        'hls_url' => env('LIVE_STREAMING_HLS_URL', 'http://127.0.0.1:8888'),
        'hls_internal_url' => env('LIVE_STREAMING_HLS_INTERNAL_URL', 'http://127.0.0.1:8888'),
        'rtsp_internal_url' => env('LIVE_STREAMING_RTSP_INTERNAL_URL', 'rtsp://127.0.0.1:8554'),
        'proxy_hls' => (bool) env('LIVE_STREAMING_PROXY_HLS', env('APP_ENV') === 'local'),
        'rtmp_url' => env('LIVE_STREAMING_RTMP_URL', 'rtmp://127.0.0.1:1935'),
        'api_url' => env('LIVE_STREAMING_API_URL', 'http://127.0.0.1:9997'),
        'auth_url' => env('LIVE_STREAMING_AUTH_URL', env('APP_URL').'/api/streaming/auth'),
        'ffmpeg_binary' => env('LIVE_STREAMING_FFMPEG_BINARY', PHP_OS_FAMILY === 'Darwin' ? '/opt/homebrew/bin/ffmpeg' : '/usr/bin/ffmpeg'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (5.2 del TODO)
    |--------------------------------------------------------------------------
    |
    | Notificaciones push por Firebase Cloud Messaging HTTP v1.
    |
    */
    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'credentials' => env('FCM_CREDENTIALS', 'storage/app/private/firebase-service-account.json'),
        'endpoint' => env('FCM_ENDPOINT', 'https://fcm.googleapis.com'),
        'web' => [
            'api_key' => env('FIREBASE_WEB_API_KEY'),
            'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
            'project_id' => env('FIREBASE_WEB_PROJECT_ID', env('FCM_PROJECT_ID')),
            'storage_bucket' => env('FIREBASE_WEB_STORAGE_BUCKET'),
            'messaging_sender_id' => env('FIREBASE_WEB_MESSAGING_SENDER_ID'),
            'app_id' => env('FIREBASE_WEB_APP_ID'),
            'vapid_key' => env('FIREBASE_WEB_VAPID_KEY'),
        ],
    ],

];
