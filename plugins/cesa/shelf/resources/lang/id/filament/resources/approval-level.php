<?php

return [
    'title' => 'Persetujuan Pengajuan Aset',

    'navigation' => [
        'title' => 'Persetujuan Pengajuan Aset',
        'group' => 'Pengajuan Aset',
    ],

    'singular' => 'Persetujuan Pengajuan Aset',
    'plural'   => 'Persetujuan Pengajuan Aset',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'fields' => [
                    'request_type'    => 'Jenis Pengajuan',
                    'division'        => 'Divisi',
                    'division_helper' => 'Isi sesuai nilai divisi pada formulir pengajuan aset. Kosongkan jika berlaku untuk semua divisi.',
                    'level'           => 'Tingkat Persetujuan',
                    'level_helper'    => 'Urutan persetujuan (1 = pertama, 2 = kedua, dan seterusnya).',
                    'approver_name'   => 'Nama / Jabatan Penyetuju',
                    'approver_email'  => 'Email Penyetuju',
                ],
            ],
        ],
    ],

    'fields' => [
        'request_type'   => 'Jenis Pengajuan',
        'division'       => 'Divisi',
        'level'          => 'Tingkat Persetujuan',
        'approver_name'  => 'Nama Penyetuju',
        'approver_email' => 'Email Penyetuju',
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
            'request_type'   => 'Jenis Pengajuan',
            'division'       => 'Divisi',
            'level'          => 'Tingkat',
            'approver_name'  => 'Nama Penyetuju',
            'approver_email' => 'Email Penyetuju',
            'created-at'     => 'Dibuat Pada',
            'updated-at'     => 'Diperbarui Pada',
        ],

        'groups' => [
            'request_type' => 'Jenis Pengajuan',
            'division'     => 'Divisi',
            'updated-at'   => 'Diperbarui Pada',
            'created-at'   => 'Dibuat Pada',
        ],

        'filters' => [
            'request_type' => 'Jenis Pengajuan',
            'division'     => 'Divisi',
            'updated-at'   => 'Diperbarui Pada',
            'created-at'   => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tingkat persetujuan diperbarui',
                    'body'  => 'Tingkat persetujuan berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tingkat persetujuan dihapus',
                    'body'  => 'Tingkat persetujuan berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Tingkat persetujuan dihapus',
                    'body'  => 'Tingkat persetujuan berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Tingkat persetujuan dibuat',
                    'body'  => 'Tingkat persetujuan berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'request_type'   => 'Jenis Pengajuan',
                    'division'       => 'Divisi',
                    'level'          => 'Tingkat Persetujuan',
                    'approver_name'  => 'Nama Penyetuju',
                    'approver_email' => 'Email Penyetuju',
                ],
            ],
        ],
    ],
];
