<?php

return [
    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Umum',

                'fields' => [
                    'name'  => 'Nama',
                    'code'  => 'Kode Identifikasi Bank',
                    'email' => 'Email',
                    'phone' => 'Telepon',
                ],
            ],

            'address' => [
                'title' => 'Alamat',

                'fields' => [
                    'address' => 'Alamat',
                    'city'    => 'Kota',
                    'street1' => 'Jalan 1',
                    'street2' => 'Jalan 2',
                    'state'   => 'Provinsi',
                    'zip'     => 'Kode Pos',
                    'country' => 'Negara',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'code'       => 'Kode Identifikasi Bank',
            'country'    => 'Negara',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
            'deleted-at' => 'Dihapus Pada',
        ],

        'groups' => [
            'country'    => 'Negara',
            'created-at' => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Bank diperbarui',
                    'body'  => 'Bank berhasil diperbarui.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Bank dipulihkan',
                    'body'  => 'Bank berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Bank dihapus',
                    'body'  => 'Bank berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Bank dihapus permanen',
                    'body'  => 'Bank berhasil dihapus permanen.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Bank dipulihkan',
                    'body'  => 'Bank berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Bank dihapus',
                    'body'  => 'Bank berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Bank dihapus permanen',
                    'body'  => 'Bank berhasil dihapus permanen.',
                ],
            ],
        ],
    ],
];
