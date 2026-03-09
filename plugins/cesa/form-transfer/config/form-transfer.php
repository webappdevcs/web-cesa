<?php

return [
    'notifications' => [
        'queue' => env('FORM_TRANSFER_NOTIFICATION_QUEUE', 'notifications'),
        'mail'  => [
            'enabled'        => env('FORM_TRANSFER_MAIL_ENABLED', true),
            'subject_prefix' => env('FORM_TRANSFER_MAIL_SUBJECT_PREFIX', '[Form Transfer]'),
            'throttle'       => [
                'enabled'              => env('FORM_TRANSFER_MAIL_THROTTLE_ENABLED', false),
                'min_interval_seconds' => (int) env('FORM_TRANSFER_MAIL_THROTTLE_MIN_INTERVAL', 0),
                'max_interval_seconds' => (int) env('FORM_TRANSFER_MAIL_THROTTLE_MAX_INTERVAL', 0),
                'key'                  => env('FORM_TRANSFER_MAIL_THROTTLE_KEY', 'global'),
            ],
        ],
        'whatsapp' => [
            'enabled'      => env('WHATSAPP_ENABLED', false),
            'provider'     => env('WHATSAPP_PROVIDER', 'fonnte'),
            'endpoint'     => env('WHATSAPP_API_ENDPOINT', 'https://api.fonnte.com/send'),
            'api_key'      => env('WHATSAPP_API_KEY'),
            'sender'       => env('WHATSAPP_SENDER_NUMBER'),
            'country_code' => env('WHATSAPP_COUNTRY_CODE', '62'),
            'throttle'     => [
                'enabled'              => env('WHATSAPP_THROTTLE_ENABLED', true),
                'min_interval_seconds' => (int) env('WHATSAPP_THROTTLE_MIN_INTERVAL', 2),
                'max_interval_seconds' => (int) env('WHATSAPP_THROTTLE_MAX_INTERVAL', 0),
                'key'                  => env('WHATSAPP_THROTTLE_KEY', 'global'),
            ],
            'queue'      => env('WHATSAPP_QUEUE', 'default'),
            'connection' => env('WHATSAPP_CONNECTION'),
            'timeout'    => (int) env('WHATSAPP_TIMEOUT', 10),
            'tries'      => (int) env('WHATSAPP_TRIES', 3),
            'backoff'    => array_values(array_filter(
                array_map('intval', explode(',', env('WHATSAPP_BACKOFF', '10,30,60'))),
                static fn (int $interval): bool => $interval >= 0
            )),
        ],
    ],

    'security' => [
        'recaptcha' => [
            'enabled'         => env('FORM_TRANSFER_RECAPTCHA_ENABLED', false),
            'site_key'        => env('FORM_TRANSFER_RECAPTCHA_SITE_KEY'),
            'secret_key'      => env('FORM_TRANSFER_RECAPTCHA_SECRET_KEY'),
            'action'          => env('FORM_TRANSFER_RECAPTCHA_ACTION', 'form_transfer_request'),
            'score_threshold' => (float) env('FORM_TRANSFER_RECAPTCHA_SCORE_THRESHOLD', 0.5),
            'timeout'         => (int) env('FORM_TRANSFER_RECAPTCHA_TIMEOUT', 5),
        ],
    ],

    'account_validation' => [
        'enabled'               => env('FORM_TRANSFER_ACCOUNT_VALIDATION_ENABLED', false),
        'endpoint'              => env('FORM_TRANSFER_ACCOUNT_VALIDATION_ENDPOINT', 'https://netovas.com/api/cekrek/v1/account-inquiry'),
        'timeout'               => (int) env('FORM_TRANSFER_ACCOUNT_VALIDATION_TIMEOUT', 5),
        'cache_ttl'             => (int) env('FORM_TRANSFER_ACCOUNT_VALIDATION_CACHE_TTL', 300),
        'allow_manual_fallback' => env('FORM_TRANSFER_ACCOUNT_VALIDATION_ALLOW_MANUAL', true),
        'rate_limit'            => [
            'max_attempts' => (int) env('FORM_TRANSFER_ACCOUNT_VALIDATION_RATE_LIMIT', 10),
            'decay'        => (int) env('FORM_TRANSFER_ACCOUNT_VALIDATION_RATE_DECAY', 60),
        ],
    ],
];
