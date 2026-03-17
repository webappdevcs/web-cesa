<?php

return [
    'title' => 'Pengajuan Aset',

    'navigation' => [
        'title' => 'Pengajuan Aset',
        'group' => 'Shelf',
    ],

    'singular' => 'Pengajuan Aset',
    'plural'   => 'Pengajuan Aset',

    'fields' => [
        'request_type'      => 'Jenis Pengajuan',
        'requester_name'    => 'Nama Pemohon',
        'email'             => 'Email',
        'division'          => 'Divisi',
        'placement'         => 'Penempatan',
        'item_name'         => 'Nama Barang',
        'qty'               => 'Jumlah',
        'attachment'        => 'Lampiran',
        'status'            => 'Status',
        'admin_notes'       => 'Catatan Admin',
    ],

    'options' => [
        'request_type' => [
            'pengadaan_aset'   => 'Pengadaan Aset',
            'perbaikan_aset'   => 'Perbaikan Aset',
            'penarikan_aset'   => 'Penarikan Aset',
        ],
    ],

    'table' => [
        'columns' => [
            'request_type'      => 'Jenis Pengajuan',
            'requester_name'    => 'Nama Pemohon',
            'division'          => 'Divisi',
            'item_name'         => 'Nama Barang',
            'status'            => 'Status',
            'created-at'        => 'Dibuat Pada',
            'updated-at'        => 'Diperbarui Pada',
        ],

        'groups' => [
            'request_type'      => 'Jenis Pengajuan',
            'division'          => 'Divisi',
            'status'            => 'Status',
            'updated-at'        => 'Diperbarui Pada',
            'created-at'        => 'Dibuat Pada',
        ],

        'filters' => [
            'request_type'      => 'Jenis Pengajuan',
            'division'          => 'Divisi',
            'status'            => 'Status',
            'updated-at'        => 'Diperbarui Pada',
            'created-at'        => 'Dibuat Pada',
        ],

        'actions' => [
            'view' => [
                'notification' => [
                    'title' => 'Permintaan aset dilihat',
                    'body'  => 'Permintaan aset berhasil dilihat.',
                ],
            ],

            'approve' => [
                'notification' => [
                    'title' => 'Permintaan aset disetujui',
                    'body'  => 'Permintaan aset berhasil disetujui.',
                ],
            ],

            'reject' => [
                'notification' => [
                    'title' => 'Permintaan aset ditolak',
                    'body'  => 'Permintaan aset berhasil ditolak.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Permintaan aset dihapus',
                    'body'  => 'Permintaan aset berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Permintaan aset dibuat',
                    'body'  => 'Permintaan aset berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'request_type'      => 'Jenis Pengajuan',
                    'requester_name'    => 'Nama Pemohon',
                    'email'             => 'Email',
                    'division'          => 'Divisi',
                    'placement'         => 'Penempatan',
                ],
            ],

            'item_details' => [
                'title' => 'Detail Barang',

                'entries' => [
                    'item_name'         => 'Nama Barang',
                    'qty'               => 'Jumlah',
                    'attachment'        => 'Lampiran',
                ],
            ],

            'status' => [
                'title' => 'Status & Catatan',

                'entries' => [
                    'status'            => 'Status',
                    'admin_notes'       => 'Catatan Admin',
                ],
            ],
        ],
    ],
];
