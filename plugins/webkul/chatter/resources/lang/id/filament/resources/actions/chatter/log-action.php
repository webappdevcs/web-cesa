<?php

return [
    'setup' => [
        'title'        => 'Catatan Log',
        'submit-title' => 'Catat',

        'form' => [
            'fields' => [
                'hide-subject'            => 'Sembunyikan Subjek',
                'add-subject'             => 'Tambahkan Subjek',
                'subject'                 => 'Subjek',
                'write-message-here'      => 'Tulis pesan Anda di sini',
                'attachments-helper-text' => 'Ukuran file maks: 10MB. Tipe yang diizinkan: Gambar, PDF, Word, Excel, Teks',
            ],
        ],

        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Catatan log ditambahkan',
                    'body'  => 'Catatan log Anda berhasil ditambahkan.',
                ],

                'error' => [
                    'title' => 'Gagal menambahkan log',
                    'body'  => 'Gagal menambahkan catatan log Anda',
                ],
            ],
        ],
    ],
];
