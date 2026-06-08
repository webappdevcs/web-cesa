<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Panel Target
    |--------------------------------------------------------------------------
    |
    | ID panel Filament yang mendapatkan halaman login WhatsApp.
    |
    */
    'panel' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Gateway Pengirim Pesan
    |--------------------------------------------------------------------------
    |
    | Mengikuti WHATSAPP_PROVIDER yang dipakai bersama fitur WhatsApp lain.
    | "fonnte" memakai kredensial WHATSAPP_API_* yang sama. "log" hanya
    | menuliskan OTP ke log (berguna untuk pengembangan/pengujian).
    |
    | Supported: "fonnte", "log"
    |
    */
    'gateway' => env('WHATSAPP_PROVIDER', 'fonnte'),

    'fonnte' => [
        'endpoint'     => env('WHATSAPP_API_ENDPOINT', 'https://api.fonnte.com/send'),
        'token'        => env('WHATSAPP_API_KEY'),
        'country_code' => env('WHATSAPP_COUNTRY_CODE', '62'),
        'timeout'      => (int) env('WHATSAPP_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi OTP
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'length'                  => (int) env('WHATSAPP_AUTH_OTP_LENGTH', 6),
        'expires_in_seconds'      => (int) env('WHATSAPP_AUTH_OTP_TTL', 300),
        'max_attempts'            => (int) env('WHATSAPP_AUTH_OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('WHATSAPP_AUTH_OTP_RESEND_COOLDOWN', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sumber Nomor WhatsApp
    |--------------------------------------------------------------------------
    |
    | Kolom pada tabel employee yang dicocokkan dengan nomor yang dimasukkan
    | pengguna. Urutan menentukan prioritas pencarian.
    |
    */
    'employee_phone_columns' => [
        'mobile_phone',
        'work_phone',
        'private_phone',
    ],
];
