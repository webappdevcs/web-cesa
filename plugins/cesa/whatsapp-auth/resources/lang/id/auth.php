<?php

return [
    'title'      => 'Masuk dengan WhatsApp',
    'heading'    => 'Masuk dengan WhatsApp',
    'subheading' => 'Masukkan nomor WhatsApp terdaftar untuk menerima kode OTP.',
    'or'         => 'atau',

    'message' => "Kode OTP login :app Anda adalah: *:code*\n\nKode berlaku :minutes menit. Jangan bagikan kode ini kepada siapa pun.",

    'form' => [
        'phone' => [
            'label' => 'Nomor WhatsApp',
        ],
        'code' => [
            'label'  => 'Kode OTP',
            'helper' => 'Kode dikirim ke :phone melalui WhatsApp.',
        ],
    ],

    'actions' => [
        'send_otp'             => 'Kirim Kode OTP',
        'verify_otp'           => 'Verifikasi & Masuk',
        'resend_otp'           => 'Ganti nomor / kirim ulang',
        'back_to_login'        => 'Kembali ke login email',
        'login_with_whatsapp'  => 'Masuk dengan WhatsApp',
    ],

    'messages' => [
        'phone_not_registered'    => 'Nomor WhatsApp tidak terdaftar atau akun tidak aktif.',
        'code_invalid'            => 'Kode OTP salah atau sudah kedaluwarsa.',
        'code_too_many_attempts'  => 'Terlalu banyak percobaan. Silakan minta kode baru.',
        'resend_cooldown'         => 'Mohon tunggu :seconds detik sebelum meminta kode lagi.',
    ],

    'notifications' => [
        'code_sent' => [
            'title' => 'Kode OTP telah dikirim ke WhatsApp Anda.',
        ],
        'delivery_failed' => [
            'title' => 'Gagal mengirim kode OTP.',
            'body'  => 'Terjadi kendala saat mengirim pesan WhatsApp. Coba lagi beberapa saat.',
        ],
        'cannot_access' => [
            'title' => 'Akun ini tidak memiliki akses ke panel.',
        ],
        'throttled' => [
            'title' => 'Terlalu banyak permintaan. Coba lagi dalam :seconds detik.',
        ],
    ],
];
