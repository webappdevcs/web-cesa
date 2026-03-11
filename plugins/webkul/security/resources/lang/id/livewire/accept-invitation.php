<?php

return [
    'header' => [
        'sub-heading' => [
            'accept-invitation' => 'Terima Undangan',
        ],
    ],

    'title' => 'Daftar',

    'heading' => 'Buat akun',

    'actions' => [

        'login' => [
            'before' => 'atau',
            'label'  => 'masuk ke akun Anda',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Alamat email',
        ],

        'name' => [
            'label' => 'Nama',
        ],

        'password' => [
            'label'                => 'Kata sandi',
            'validation_attribute' => 'kata sandi',
        ],

        'password_confirmation' => [
            'label' => 'Konfirmasi kata sandi',
        ],

        'actions' => [

            'register' => [
                'label' => 'Daftar',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Terlalu banyak percobaan pendaftaran',
            'body'  => 'Silakan coba lagi dalam :seconds detik.',
        ],

    ],

];
