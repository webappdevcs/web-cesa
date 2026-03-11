<?php

return [
    'setup' => [
        'title'               => 'Pengikut',
        'submit-action-title' => 'Tambah Pengikut',
        'tooltip'             => 'Tambah Pengikut',

        'form' => [
            'fields' => [
                'recipients'  => 'Penerima',
                'notify-user' => 'Beri Tahu Pengguna',
                'add-a-note'  => 'Tambahkan catatan',
            ],
        ],

        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Pengikut Ditambahkan',
                    'body'  => 'Pengikut berhasil ditambahkan.',
                ],

                'partial_message' => [
                    'title'    => 'Pesan terkirim dengan catatan',
                    'single'   => ':count pengikut tidak diberi tahu karena email tidak tersedia: :names',
                    'multiple' => ':count pengikut tidak diberi tahu karena email tidak tersedia: :names',
                ],

                'error' => [
                    'title' => 'Gagal menambahkan pengikut',
                    'body'  => 'Gagal menambahkan ":partner" sebagai pengikut',
                ],
            ],

            'mail' => [
                'subject' => 'Undangan untuk mengikuti :model: :department',
            ],
        ],
    ],
];
