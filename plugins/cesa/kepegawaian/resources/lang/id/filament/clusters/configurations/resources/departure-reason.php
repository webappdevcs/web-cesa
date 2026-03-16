<?php

return [
    'title' => 'Alasan Keluar',

    'navigation' => [
        'title' => 'Alasan Keluar',
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
            'name' => 'Nama',
        ],
    ],

    'table' => [
        'columns' => [
            'id'         => 'ID',
            'name'       => 'Nama',
            'created-by' => 'Dibuat Oleh',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'filters' => [
            'name'       => 'Nama',
            'employee'   => 'Karyawan',
            'created-by' => 'Dibuat Oleh',
            'updated-at' => 'Diperbarui Pada',
            'created-at' => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Alasan keluar diperbarui',
                    'body'  => 'Alasan keluar berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Alasan keluar dihapus',
                    'body'  => 'Alasan keluar berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Alasan keluar dihapus',
                    'body'  => 'Alasan keluar terpilih berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-action' => [
            'create' => [
                'notification' => [
                    'title' => 'Alasan keluar dibuat',
                    'body'  => 'Alasan keluar berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'name' => 'Nama',
    ],
];
