<?php

return [
    'title' => 'Vendor',

    'navigation' => [
        'title' => 'Vendor',
        'group' => 'Master Aset',
    ],

    'singular' => 'Vendor',
    'plural'   => 'Vendor',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'fields' => [
                    'name'       => 'Nama',
                    'last-price' => 'Harga Terakhir',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'last-price' => 'Harga Terakhir',
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
                    'title' => 'Vendor diperbarui',
                    'body'  => 'Vendor berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Vendor dihapus',
                    'body'  => 'Vendor berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Vendor dihapus',
                    'body'  => 'Vendor terpilih berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Vendor dibuat',
                    'body'  => 'Vendor berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'name'       => 'Nama',
                    'last-price' => 'Harga Terakhir',
                ],
            ],
        ],
    ],
];
