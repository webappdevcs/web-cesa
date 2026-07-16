<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sam' => [
        'playstore'  => env('SAM_ANDROID_DOWNLOAD_URL', 'https://play.google.com/store/apps/details?id=com.mediaselularindonesia.sam'),
        'testflight' => env('SAM_IOS_TESTFLIGHT_URL', 'https://testflight.apple.com/join/yMZWH4CT'),
        'web'        => env('SAM_WEB_URL', 'https://sam.mediaselularindonesia.com/'),
    ],

    'whatsapp_gateway' => [
        'provider'          => env('WHATSAPP_GATEWAY_PROVIDER', 'gateway_hub'),
        'fallback_provider' => env('WHATSAPP_GATEWAY_FALLBACK_PROVIDER', 'fonnte'),
        'fallback_enabled'  => env('WHATSAPP_GATEWAY_FALLBACK_ENABLED', true),
        'gateway_hub'       => [
            'endpoint'  => env('WHATSAPP_GATEWAY_HUB_ENDPOINT'),
            'token'     => env('WHATSAPP_GATEWAY_HUB_TOKEN'),
            'route_key' => env('WHATSAPP_GATEWAY_HUB_ROUTE_KEY', 'web-cesa-messages'),
            'mode'      => env('WHATSAPP_GATEWAY_HUB_MODE', 'async'),
        ],
        'waha' => [
            'base_url' => env('WHATSAPP_GATEWAY_WAHA_BASE_URL', env('WAHA_API_ENDPOINT', env('WAHA_BASE_URL', 'http://localhost:3000'))),
            'api_key'  => env('WHATSAPP_GATEWAY_WAHA_API_KEY', env('WAHA_API_KEY')),
            'session'  => env('WHATSAPP_GATEWAY_WAHA_SESSION', env('WAHA_SESSION', 'default')),
        ],
        'fonnte' => [
            'endpoint' => env('WHATSAPP_GATEWAY_FONNTE_ENDPOINT', env('FONNTE_API_ENDPOINT', env('FONNTE_ENDPOINT', env('WHATSAPP_API_ENDPOINT', 'https://api.fonnte.com/send')))),
            'token'    => env('WHATSAPP_GATEWAY_FONNTE_TOKEN', env('FONNTE_TOKEN', env('WHATSAPP_API_KEY'))),
        ],
        'country_code'              => env('WHATSAPP_GATEWAY_COUNTRY_CODE', env('WHATSAPP_COUNTRY_CODE', env('FONNTE_COUNTRY_CODE', '62'))),
        'timeout'                   => env('WHATSAPP_GATEWAY_TIMEOUT', env('WHATSAPP_TIMEOUT', 15)),
        'min_seconds_between_sends' => env('WHATSAPP_GATEWAY_MIN_SECONDS_BETWEEN_SENDS', env('WHATSAPP_THROTTLE_MIN_INTERVAL', 3)),
        'min_digits'                => env('WHATSAPP_GATEWAY_MIN_DIGITS', 10),
        'max_digits'                => env('WHATSAPP_GATEWAY_MAX_DIGITS', 15),
    ],

];
