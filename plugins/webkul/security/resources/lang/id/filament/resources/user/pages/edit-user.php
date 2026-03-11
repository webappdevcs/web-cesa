<?php

return [
    'notification' => [
        'title' => 'Pengguna diperbarui',
        'body'  => 'Pengguna berhasil diperbarui.',
    ],

    'header-actions' => [
        'change-password' => [
            'label' => 'Ubah Kata Sandi',

            'notification' => [
                'title' => 'Kata sandi diubah',
                'body'  => 'Kata sandi berhasil diubah.',
            ],

            'form' => [
                'new-password'         => 'Kata Sandi Baru',
                'confirm-new-password' => 'Konfirmasi Kata Sandi Baru',
            ],
        ],

        'delete' => [
            'notification' => [
                'title' => 'Pengguna dihapus',
                'body'  => 'Pengguna berhasil dihapus.',
            ],
        ],
    ],
];
