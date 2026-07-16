<?php

return [
    'store_branches' => [
        'Complete Selular Babakan',
        'Complete Selular Cilacap',
        'Complete Selular Ciledug',
        'Complete Selular Gebang',
        'Complete Selular Jatiwangi',
        'Complete Selular Jatiwangi2 (Cibolerang)',
        'Complete Selular Kroya',
        'Complete Selular Pabuaran',
        'Complete Selular Patrol',
        'Complete Selular Perumnas',
        'Complete Selular Plaza Cell',
        'Complete Selular Sindang',
        'Complete Selular Surya 1',
        'Complete Selular Tegal',
        'Complete Selular Tuparev',
        'Global Selular Jatibarang',
        'HP Mart Ciledug',
        'HP Mart Sindang',
        'Intiphone',
        'Mi Shop Ciledug',
        'Oppo Store Tentara Pelajar',
        'Selular Plus Jatibarang',
        'Unboxing Megu',
    ],

    'whatsapp_validation' => [
        'enabled'               => env('WHATSAPP_ENABLED', false),
        'provider'              => env('LEAD_WHATSAPP_VALIDATION_PROVIDER', 'gateway_hub'),
        'endpoint'              => env('LEAD_WHATSAPP_VALIDATION_ENDPOINT', env('WHATSAPP_VALIDATION_ENDPOINT')),
        'token'                 => env('LEAD_WHATSAPP_VALIDATION_TOKEN', env('WHATSAPP_API_KEY')),
        'route_key'             => env('LEAD_WHATSAPP_VALIDATION_ROUTE_KEY', 'lead-number-check'),
        'country_code'          => env('WHATSAPP_COUNTRY_CODE', '62'),
        'timeout'               => (int) env('WHATSAPP_VALIDATION_TIMEOUT', 5),
        'cache_ttl'             => (int) env('WHATSAPP_VALIDATION_CACHE_TTL', 300),
        'allow_manual_fallback' => env('WHATSAPP_VALIDATION_ALLOW_MANUAL', false),
        'rate_limit'            => [
            'max_attempts' => (int) env('WHATSAPP_VALIDATION_RATE_LIMIT', 10),
            'decay'        => (int) env('WHATSAPP_VALIDATION_RATE_DECAY', 60),
        ],
    ],

    'security' => [
        'recaptcha' => [
            'enabled'         => env('LEAD_RECAPTCHA_ENABLED', false),
            'site_key'        => env('LEAD_RECAPTCHA_SITE_KEY'),
            'secret_key'      => env('LEAD_RECAPTCHA_SECRET_KEY'),
            'action'          => env('LEAD_RECAPTCHA_ACTION', 'lead_request'),
            'score_threshold' => (float) env('LEAD_RECAPTCHA_SCORE_THRESHOLD', 0.5),
            'timeout'         => (int) env('LEAD_RECAPTCHA_TIMEOUT', 5),
        ],
    ],
];
