<?php

return [
    'title' => 'Tugas',

    'navigation' => [
        'title' => 'Tugas',
        'group' => 'Shelf',
    ],

    'singular' => 'Tugas',
    'plural'   => 'Tugas',

    'fields' => [
        'code'               => 'Nomor Surat',
        'name'               => 'Nama Pekerjaan',
        'cost'               => 'Biaya',
        'work_timestamp'     => 'Tanggal Pekerjaan',
        'description'        => 'Deskripsi',
        'location'           => 'Lokasi',
        'business_entity'    => 'Badan Usaha',
        'pic'                => 'PIC',
        'vendor'             => 'Vendor',
        'status'             => 'Status',
        'document_upload'    => 'Upload Dokumen',
    ],

    'sections' => [
        'general'          => 'Informasi Umum',
        'vendor_info'      => 'Informasi Vendor',
        'attachment'       => 'Lampiran',
    ],

    'options' => [
        'status' => [
            'open'        => 'Terbuka',
            'in_progress' => 'Dalam Proses',
            'completed'   => 'Selesai',
        ],
    ],

    'table' => [
        'columns' => [
            'code'               => 'Nomor Surat',
            'business_entity'    => 'Badan Usaha',
            'name'               => 'Nama Pekerjaan',
            'vendor'             => 'Vendor',
            'cost'               => 'Biaya',
            'location'           => 'Lokasi',
            'pic'                => 'PIC',
            'status'             => 'Status',
            'work_timestamp'     => 'Tanggal Pekerjaan',
            'created-at'         => 'Dibuat Pada',
            'updated-at'         => 'Diperbarui Pada',
        ],

        'groups' => [
            'business_entity'    => 'Badan Usaha',
            'vendor'             => 'Vendor',
            'status'             => 'Status',
            'pic'                => 'PIC',
            'updated-at'         => 'Diperbarui Pada',
            'created-at'         => 'Dibuat Pada',
        ],

        'filters' => [
            'business_entity'    => 'Badan Usaha',
            'vendor'             => 'Vendor',
            'status'             => 'Status',
            'pic'                => 'PIC',
            'updated-at'         => 'Diperbarui Pada',
            'created-at'         => 'Dibuat Pada',
        ],

        'actions' => [
            'view' => [
                'notification' => [
                    'title' => 'Tugas dilihat',
                    'body'  => 'Tugas berhasil dilihat.',
                ],
            ],

            'edit' => [
                'notification' => [
                    'title' => 'Tugas diperbarui',
                    'body'  => 'Tugas berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tugas dihapus',
                    'body'  => 'Tugas berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Tugas dihapus',
                    'body'  => 'Tugas berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Tugas dibuat',
                    'body'  => 'Tugas berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'code'               => 'Nomor Surat',
                    'name'               => 'Nama Pekerjaan',
                    'cost'               => 'Biaya',
                    'work_timestamp'     => 'Tanggal Pekerjaan',
                    'description'        => 'Deskripsi',
                    'location'           => 'Lokasi',
                    'business_entity'    => 'Badan Usaha',
                    'pic'                => 'PIC',
                    'status'             => 'Status',
                ],
            ],

            'vendor' => [
                'title' => 'Informasi Vendor',

                'entries' => [
                    'vendor'             => 'Vendor',
                ],
            ],

            'document' => [
                'title' => 'Dokumen',

                'entries' => [
                    'document_upload'    => 'Upload Dokumen',
                ],
            ],
        ],
    ],
];
