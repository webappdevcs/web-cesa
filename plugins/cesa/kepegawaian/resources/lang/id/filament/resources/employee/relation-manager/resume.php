<?php

return [
    'title' => 'Riwayat',

    'form' => [
        'sections' => [
            'fields' => [
                'title'        => 'Judul',
                'type'         => 'Tipe',
                'name'         => 'Nama',
                'create-type'  => 'Buat Tipe',
                'duration'     => 'Durasi',
                'start-date'   => 'Tanggal Mulai',
                'end-date'     => 'Tanggal Selesai',
                'display-type' => 'Tipe Tampilan',
                'description'  => 'Deskripsi',
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'title'        => 'Judul',
            'start-date'   => 'Tanggal Mulai',
            'end-date'     => 'Tanggal Selesai',
            'display-type' => 'Tipe Tampilan',
            'description'  => 'Deskripsi',
            'created-by'   => 'Dibuat Oleh',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],

        'groups' => [
            'group-by-type'         => 'Kelompok Berdasarkan Tipe',
            'group-by-display-type' => 'Kelompok Berdasarkan Tipe Tampilan',
        ],

        'header-actions' => [
            'add-resume' => 'Tambah Riwayat',
        ],

        'filters' => [
            'type'            => 'Tipe',
            'start-date-from' => 'Tanggal Mulai Dari',
            'start-date-to'   => 'Tanggal Mulai Sampai',
            'created-from'    => 'Dibuat Dari',
            'created-to'      => 'Dibuat Sampai',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Riwayat diperbarui',
                    'body'  => 'Riwayat berhasil diperbarui.',
                ],
            ],

            'create' => [
                'notification' => [
                    'title' => 'Riwayat dibuat',
                    'body'  => 'Riwayat berhasil dibuat.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Riwayat dihapus',
                    'body'  => 'Riwayat berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Riwayat dihapus',
                    'body'  => 'Riwayat terpilih berhasil dihapus.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'entries' => [
            'title'        => 'Judul',
            'display-type' => 'Tipe Tampilan',
            'type'         => 'Tipe',
            'description'  => 'Deskripsi',
            'duration'     => 'Durasi',
            'start-date'   => 'Tanggal Mulai',
            'end-date'     => 'Tanggal Selesai',
        ],
    ],
];
