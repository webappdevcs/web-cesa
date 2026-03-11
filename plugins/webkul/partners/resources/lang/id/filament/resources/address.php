<?php

return [
    'form' => [
        'partner' => 'Partner',
        'name'    => 'Nama',
        'email'   => 'Email',
        'phone'   => 'Telepon',
        'mobile'  => 'Ponsel',
        'type'    => 'Tipe',
        'address' => 'Alamat',
        'city'    => 'Kota',
        'street1' => 'Jalan 1',
        'street2' => 'Jalan 2',
        'state'   => 'Provinsi',
        'zip'     => 'Kode Pos',
        'code'    => 'Kode',
        'country' => 'Negara',
    ],

    'table' => [
        'header-actions' => [
            'create' => [
                'label' => 'Tambah Alamat',

                'notification' => [
                    'title' => 'Alamat dibuat',
                    'body'  => 'Alamat berhasil dibuat.',
                ],
            ],
        ],

        'columns' => [
            'type'    => 'Tipe',
            'name'    => 'Nama Kontak',
            'address' => 'Alamat',
            'city'    => 'Kota',
            'street1' => 'Jalan 1',
            'street2' => 'Jalan 2',
            'state'   => 'Provinsi',
            'zip'     => 'Kode Pos',
            'country' => 'Negara',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Alamat diperbarui',
                    'body'  => 'Alamat berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Alamat dihapus',
                    'body'  => 'Alamat berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Alamat dihapus',
                    'body'  => 'Alamat berhasil dihapus.',
                ],
            ],
        ],
    ],
];
