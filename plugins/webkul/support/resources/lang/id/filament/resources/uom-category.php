<?php

return [
    'navigation' => [
        'group' => 'Pengaturan',
        'title' => 'Kategori UOM',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Umum',

                'fields' => [
                    'name' => 'Nama',
                ],
            ],

            'uoms' => [
                'title' => 'Satuan Ukur',

                'fields' => [
                    'uoms'     => 'Satuan',
                    'type'     => 'Tipe',
                    'name'     => 'Nama',
                    'factor'   => 'Faktor',
                    'rounding' => 'Presisi Pembulatan',
                ],

                'actions' => [
                    'add' => 'Tambah Satuan',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'uoms-count' => 'Satuan',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'groups' => [
            'created-at' => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Kategori UOM diperbarui',
                    'body'  => 'Kategori UOM berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Kategori UOM dihapus',
                    'body'  => 'Kategori UOM berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Kategori UOM dihapus',
                    'body'  => 'Kategori UOM berhasil dihapus.',
                ],
            ],
        ],
    ],
];
