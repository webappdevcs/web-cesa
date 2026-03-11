<?php

return [
    'form' => [
        'name'      => 'Nama',
        'full-name' => 'Nama Lengkap',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'full-name'  => 'Nama Lengkap',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Industri diperbarui',
                    'body'  => 'Industri berhasil diperbarui.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Industri dipulihkan',
                    'body'  => 'Industri berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Industri dihapus',
                    'body'  => 'Industri berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Industri dihapus permanen',
                    'body'  => 'Industri berhasil dihapus permanen.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Industri dipulihkan',
                    'body'  => 'Industri berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Industri dihapus',
                    'body'  => 'Industri berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Industri dihapus permanen',
                    'body'  => 'Industri berhasil dihapus permanen.',
                ],
            ],
        ],
    ],
];
