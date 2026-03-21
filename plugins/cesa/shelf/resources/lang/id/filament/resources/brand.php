<?php

return [
    'title' => 'Merek',

    'navigation' => [
        'title' => 'Merek',
        'group' => 'Master Aset',
    ],

    'singular' => 'Merek',
    'plural'   => 'Merek',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'fields' => [
                    'name' => 'Nama',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'groups' => [
            'name'       => 'Nama',
            'updated-at' => 'Diperbarui Pada',
            'created-at' => 'Dibuat Pada',
        ],

        'filters' => [
            'name'       => 'Nama',
            'updated-at' => 'Diperbarui Pada',
            'created-at' => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Merek diperbarui',
                    'body'  => 'Merek berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Merek dihapus',
                    'body'  => 'Merek berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Merek dihapus',
                    'body'  => 'Merek terpilih berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Merek dibuat',
                    'body'  => 'Merek berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'name' => 'Nama',
                ],
            ],
        ],
    ],
];
