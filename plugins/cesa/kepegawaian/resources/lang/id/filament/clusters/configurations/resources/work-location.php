<?php

return [
    'title' => 'Lokasi Kerja',

    'navigation' => [
        'title' => 'Lokasi Kerja',
        'group' => 'Karyawan',
    ],

    'form' => [
        'name'            => 'Nama',
        'company'         => 'Perusahaan',
        'location-type'   => 'Tipe Lokasi',
        'location-number' => 'Nomor Lokasi',
        'status'          => 'Status',
    ],

    'table' => [
        'columns' => [
            'id'              => 'ID',
            'name'            => 'Nama',
            'status'          => 'Status',
            'company'         => 'Perusahaan',
            'location-type'   => 'Tipe Lokasi',
            'location-number' => 'Nomor Lokasi',
            'deleted-at'      => 'Dihapus Pada',
            'created-by'      => 'Dibuat Oleh',
            'created-at'      => 'Dibuat Pada',
            'updated-at'      => 'Diperbarui Pada',
        ],

        'filters' => [
            'name'            => 'Nama',
            'status'          => 'Status',
            'created-by'      => 'Dibuat Oleh',
            'company'         => 'Perusahaan',
            'location-number' => 'Nomor Lokasi',
            'location-type'   => 'Tipe Lokasi',
            'updated-at'      => 'Diperbarui Pada',
            'created-at'      => 'Dibuat Pada',
        ],

        'groups' => [
            'name'          => 'Nama',
            'status'        => 'Status',
            'location-type' => 'Tipe Lokasi',
            'company'       => 'Perusahaan',
            'created-by'    => 'Dibuat Oleh',
            'created-at'    => 'Dibuat Pada',
            'updated-at'    => 'Diperbarui Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Lokasi kerja diperbarui',
                    'body'  => 'Lokasi kerja berhasil diperbarui.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Lokasi kerja dipulihkan',
                    'body'  => 'Lokasi kerja berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Lokasi kerja dihapus',
                    'body'  => 'Lokasi kerja berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Lokasi kerja dihapus permanen',
                    'body'  => 'Lokasi kerja berhasil dihapus permanen.',
                ],
            ],

            'empty-state' => [
                'notification' => [
                    'title' => 'Lokasi kerja dibuat',
                    'body'  => 'Lokasi kerja berhasil dibuat.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Lokasi kerja dihapus',
                    'body'  => 'Lokasi kerja terpilih berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Lokasi kerja dihapus permanen',
                    'body'  => 'Lokasi kerja terpilih berhasil dihapus permanen.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'name'            => 'Nama',
        'company'         => 'Perusahaan',
        'location-type'   => 'Tipe Lokasi',
        'location-number' => 'Nomor Lokasi',
        'status'          => 'Status',
    ],
];
