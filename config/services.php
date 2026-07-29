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

];
