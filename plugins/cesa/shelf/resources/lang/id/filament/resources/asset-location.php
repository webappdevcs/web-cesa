<?php

return [
    'title' => 'Lokasi Aset',

    'navigation' => [
        'title' => 'Lokasi Aset',
        'group' => 'Master Aset',
    ],

    'singular' => 'Lokasi Aset',
    'plural'   => 'Lokasi Aset',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'fields' => [
                    'name'        => 'Nama',
                    'address'     => 'Alamat',
                    'description' => 'Deskripsi',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'        => 'Nama',
            'address'     => 'Alamat',
            'description' => 'Deskripsi',
            'created-at'  => 'Dibuat Pada',
            'updated-at'  => 'Diperbarui Pada',
        ],

        'groups' => [
            'name'       => 'Nama',
            'address'    => 'Alamat',
            'updated-at' => 'Diperbarui Pada',
            'created-at' => 'Dibuat Pada',
        ],

        'filters' => [
            'name'       => 'Nama',
            'address'    => 'Alamat',
            'updated-at' => 'Diperbarui Pada',
            'created-at' => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Lokasi aset diperbarui',
                    'body'  => 'Lokasi aset berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Lokasi aset dihapus',
                    'body'  => 'Lokasi aset berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Lokasi aset dihapus',
                    'body'  => 'Lokasi aset terpilih berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Lokasi aset dibuat',
                    'body'  => 'Lokasi aset berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'name'        => 'Nama',
                    'address'     => 'Alamat',
                    'description' => 'Deskripsi',
                ],
            ],
        ],
    ],
];
