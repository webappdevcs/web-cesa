<?php

return [
    'title' => 'Atribut Aset Kustom',

    'navigation' => [
        'title' => 'Atribut Kustom',
        'group' => 'Master Aset',
    ],

    'singular' => 'Atribut Aset Kustom',
    'plural'   => 'Atribut Aset Kustom',

    'fields' => [
        'name'            => 'Nama Atribut',
        'type'            => 'Tipe Input',
        'required'        => 'Wajib Diisi',
        'is_active'       => 'Aktif',
        'category'        => 'Kategori',
    ],

    'sections' => [
        'basic_info'       => 'Informasi Dasar',
        'attribute_status' => 'Status Atribut',
    ],

    'options' => [
        'input_type' => [
            'text'     => 'Input Teks',
            'number'   => 'Input Angka',
            'textarea' => 'Textarea',
            'date'     => 'Pemilih Tanggal',
        ],
    ],

    'table' => [
        'columns' => [
            'name'            => 'Nama Atribut',
            'type'            => 'Tipe Input',
            'category'        => 'Kategori',
            'required'        => 'Wajib Diisi',
            'is_active'       => 'Aktif',
            'created-at'      => 'Dibuat Pada',
            'updated-at'      => 'Diperbarui Pada',
        ],

        'groups' => [
            'name'            => 'Nama Atribut',
            'type'            => 'Tipe Input',
            'category'        => 'Kategori',
            'updated-at'      => 'Diperbarui Pada',
            'created-at'      => 'Dibuat Pada',
        ],

        'filters' => [
            'name'            => 'Nama Atribut',
            'type'            => 'Tipe Input',
            'category'        => 'Kategori',
            'updated-at'      => 'Diperbarui Pada',
            'created-at'      => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Atribut kustom diperbarui',
                    'body'  => 'Atribut kustom berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Atribut kustom dihapus',
                    'body'  => 'Atribut kustom berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Atribut kustom dihapus',
                    'body'  => 'Atribut kustom berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Atribut kustom dibuat',
                    'body'  => 'Atribut kustom berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'name'            => 'Nama Atribut',
                    'type'            => 'Tipe Input',
                    'required'        => 'Wajib Diisi',
                    'is_active'       => 'Aktif',
                    'category'        => 'Kategori',
                ],
            ],
        ],
    ],
];
