<?php

return [
    'notifications' => [
        'queue' => env('EXIT_CLEARANCE_NOTIFICATION_QUEUE', 'notifications'),
        'mail'  => [
            'enabled'        => env('EXIT_CLEARANCE_MAIL_ENABLED', true),
            'subject_prefix' => env('EXIT_CLEARANCE_MAIL_SUBJECT_PREFIX', '[Exit Clearance]'),
            'throttle'       => [
                'enabled'              => env('NOTIFICATION_MAIL_THROTTLE_ENABLED', env('EXIT_CLEARANCE_MAIL_THROTTLE_ENABLED', true)),
                'min_interval_seconds' => (int) env('NOTIFICATION_MAIL_THROTTLE_MIN_INTERVAL', env('EXIT_CLEARANCE_MAIL_THROTTLE_MIN_INTERVAL', 2)),
                'max_interval_seconds' => (int) env('NOTIFICATION_MAIL_THROTTLE_MAX_INTERVAL', env('EXIT_CLEARANCE_MAIL_THROTTLE_MAX_INTERVAL', 5)),
                'key'                  => env('NOTIFICATION_MAIL_THROTTLE_KEY', env('EXIT_CLEARANCE_MAIL_THROTTLE_KEY', 'global')),
            ],
        ],
        'whatsapp' => [
            'enabled'  => env('WHATSAPP_ENABLED', false),
            'throttle' => [
                'enabled'              => env('WHATSAPP_THROTTLE_ENABLED', true),
                'min_interval_seconds' => (int) env('WHATSAPP_THROTTLE_MIN_INTERVAL', 5),
                'max_interval_seconds' => (int) env('WHATSAPP_THROTTLE_MAX_INTERVAL', 10),
                'key'                  => env('WHATSAPP_THROTTLE_KEY', 'global'),
            ],
            'queue'      => env('WHATSAPP_QUEUE', 'whatsapp'),
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
