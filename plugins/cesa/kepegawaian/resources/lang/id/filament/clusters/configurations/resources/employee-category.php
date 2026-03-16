<?php

return [
    'title' => 'Tag',

    'navigation' => [
        'title' => 'Tag',
        'group' => 'Karyawan',
    ],

    'groups' => [
        'status'     => 'Status',
        'created-by' => 'Dibuat Oleh',
        'created-at' => 'Dibuat Pada',
        'updated-at' => 'Diperbarui Pada',
    ],

    'form' => [
        'fields' => [
            'name'             => 'Nama',
            'name-placeholder' => 'Masukkan nama tag',
            'color'            => 'Warna',
        ],
    ],

    'table' => [
        'columns' => [
            'id'         => 'ID',
            'name'       => 'Nama',
            'color'      => 'Warna',
            'created-by' => 'Dibuat Oleh',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'filters' => [
            'name'       => 'Nama',
            'created-by' => 'Dibuat Oleh',
            'updated-by' => 'Diperbarui Oleh',
            'updated-at' => 'Diperbarui Pada',
            'created-at' => 'Dibuat Pada',
        ],

        'groups' => [
            'name'         => 'Nama',
            'job-position' => 'Posisi Jabatan',
            'color'        => 'Warna',
            'created-by'   => 'Dibuat Oleh',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tag diperbarui',
                    'body'  => 'Tag berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tag dihapus',
                    'body'  => 'Tag berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Tag dihapus',
                    'body'  => 'Tag terpilih berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-action' => [
            'create' => [
                'notification' => [
                    'title' => 'Tag dibuat',
                    'body'  => 'Tag berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'name'  => 'Nama',
        'color' => 'Warna',
    ],
];
