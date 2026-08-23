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
    | Firebase Cloud Messaging (5.2 del TODO)
    |--------------------------------------------------------------------------
    |
    | Notificaciones push. Sin `FCM_SERVER_KEY` real configurada, el envío
    | sigue siendo una llamada HTTP real (nunca simulada en código), solo
    | que Firebase la rechazará hasta que se configure una llave de
    | producción — igual criterio que Wompi en modo sandbox.
    |
    */
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'endpoint' => env('FCM_ENDPOINT', 'https://fcm.googleapis.com/fcm/send'),
    ],

];
