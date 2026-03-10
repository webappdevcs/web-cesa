<?php

return [
    'notifications' => [
        'queue' => env('EXIT_CLEARANCE_NOTIFICATION_QUEUE', 'default'),
        'mail'  => [
            'enabled'        => env('EXIT_CLEARANCE_MAIL_ENABLED', true),
            'subject_prefix' => env('EXIT_CLEARANCE_MAIL_SUBJECT_PREFIX', '[Exit Clearance]'),
            'throttle'       => [
                'enabled'              => env('EXIT_CLEARANCE_MAIL_THROTTLE_ENABLED', false),
                'min_interval_seconds' => (int) env('EXIT_CLEARANCE_MAIL_THROTTLE_MIN_INTERVAL', 0),
                'max_interval_seconds' => (int) env('EXIT_CLEARANCE_MAIL_THROTTLE_MAX_INTERVAL', 0),
                'key'                  => env('EXIT_CLEARANCE_MAIL_THROTTLE_KEY', 'global'),
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
            'enabled'         => env('EXIT_CLEARANCE_RECAPTCHA_ENABLED', false),
            'site_key'        => env('EXIT_CLEARANCE_RECAPTCHA_SITE_KEY'),
            'secret_key'      => env('EXIT_CLEARANCE_RECAPTCHA_SECRET_KEY'),
            'action'          => env('EXIT_CLEARANCE_RECAPTCHA_ACTION', 'exit_clearance_request'),
            'score_threshold' => (float) env('EXIT_CLEARANCE_RECAPTCHA_SCORE_THRESHOLD', 0.5),
            'timeout'         => (int) env('EXIT_CLEARANCE_RECAPTCHA_TIMEOUT', 5),
        ],
    ],
];
