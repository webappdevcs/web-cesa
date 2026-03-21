<?php

return [
    'title' => 'Kategori',

    'navigation' => [
        'title' => 'Kategori',
        'group' => 'Master Aset',
    ],

    'singular' => 'Kategori',
    'plural'   => 'Kategori',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'fields' => [
                    'name'      => 'Nama',
                    'parent-id' => 'Kategori Induk',
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
                    'title' => 'Kategori diperbarui',
                    'body'  => 'Kategori berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Kategori dihapus',
                    'body'  => 'Kategori berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Kategori dihapus',
                    'body'  => 'Kategori terpilih berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Kategori dibuat',
                    'body'  => 'Kategori berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'name'      => 'Nama',
                    'parent-id' => 'Kategori Induk',
                ],
            ],
        ],
    ],
];
