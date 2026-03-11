<?php

return [
    'setup' => [
        'title'   => 'Lampiran',
        'tooltip' => 'Unggah Lampiran',

        'form' => [
            'fields' => [
                'files'                  => 'File',
                'attachment-helper-text' => 'Ukuran file maks: 10MB. Tipe yang diizinkan: Gambar, PDF, Word, Excel, Teks',

                'actions' => [
                    'delete' => [
                        'title' => 'File dihapus',
                        'body'  => 'File berhasil dihapus.',
                    ],
                ],
            ],
        ],

        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Lampiran Diunggah',
                    'body'  => 'Lampiran berhasil diunggah.',
                ],

                'warning' => [
                    'title' => 'Tidak ada file baru',
                    'body'  => 'Semua file sudah diunggah sebelumnya.',
                ],

                'error' => [
                    'title' => 'Gagal mengunggah lampiran',
                    'body'  => 'Gagal mengunggah lampiran ',
                ],
            ],
        ],
    ],
];
