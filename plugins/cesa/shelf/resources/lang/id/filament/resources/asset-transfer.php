<?php

return [
    'title' => 'Transfer Aset',

    'navigation' => [
        'title' => 'Transfer Aset',
        'group' => 'Shelf',
    ],

    'singular' => 'Transfer Aset',
    'plural'   => 'Transfer Aset',

    'fields' => [
        'letter_number'      => 'Nomor Surat',
        'business_entity'    => 'Badan Usaha',
        'from_user'          => 'Dari',
        'to_user'            => 'Ke',
        'transfer_date'      => 'Tanggal Transfer',
        'notes'              => 'Catatan',
        'attachment'         => 'Lampiran',
        'assets'             => 'Aset',
    ],

    'sections' => [
        'general'          => 'Informasi Umum',
        'transfer_details' => 'Detail Transfer',
        'assets_list'      => 'Daftar Aset',
    ],

    'table' => [
        'columns' => [
            'letter_number'      => 'Nomor Surat',
            'business_entity'    => 'Badan Usaha',
            'from_user'          => 'Dari',
            'to_user'            => 'Ke',
            'transfer_date'      => 'Tanggal Transfer',
            'assets_count'       => 'Jumlah Aset',
            'created-at'         => 'Dibuat Pada',
            'updated-at'         => 'Diperbarui Pada',
        ],

        'groups' => [
            'business_entity'    => 'Badan Usaha',
            'from_user'          => 'Dari',
            'to_user'            => 'Ke',
            'transfer_date'      => 'Tanggal Transfer',
            'updated-at'         => 'Diperbarui Pada',
            'created-at'         => 'Dibuat Pada',
        ],

        'filters' => [
            'business_entity'    => 'Badan Usaha',
            'from_user'          => 'Dari',
            'to_user'            => 'Ke',
            'transfer_date'      => 'Tanggal Transfer',
            'updated-at'         => 'Diperbarui Pada',
            'created-at'         => 'Dibuat Pada',
        ],

        'actions' => [
            'view' => [
                'notification' => [
                    'title' => 'Transfer aset dilihat',
                    'body'  => 'Transfer aset berhasil dilihat.',
                ],
            ],

            'edit' => [
                'notification' => [
                    'title' => 'Transfer aset diperbarui',
                    'body'  => 'Transfer aset berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Transfer aset dihapus',
                    'body'  => 'Transfer aset berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Transfer aset dihapus',
                    'body'  => 'Transfer aset berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Transfer aset dibuat',
                    'body'  => 'Transfer aset berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'letter_number'      => 'Nomor Surat',
                    'business_entity'    => 'Badan Usaha',
                    'from_user'          => 'Dari',
                    'to_user'            => 'Ke',
                    'transfer_date'      => 'Tanggal Transfer',
                    'notes'              => 'Catatan',
                    'attachment'         => 'Lampiran',
                ],
            ],

            'assets' => [
                'title' => 'Aset yang Ditransfer',
            ],
        ],
    ],
];
