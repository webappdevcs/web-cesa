<?php

return [
    'navigation' => [
        'title' => 'Master Jenis Transaksi',
    ],

    'singular' => 'Jenis Transaksi',
    'plural'   => 'Jenis Transaksi',

    'fields' => [
        'code'                  => 'Kode',
        'name'                  => 'Nama',
        'requires_coach'        => 'Wajib Coach',
        'requires_catalog_item' => 'Wajib Layanan / Paket',
        'is_active'             => 'Aktif',
        'sort'                  => 'Urutan',
    ],

    'helpers' => [
        'code' => 'Gunakan kode singkat, misalnya regular, coaching, academy, event, atau membership.',
    ],

    'table' => [
        'columns' => [
            'code'                  => 'Kode',
            'name'                  => 'Nama',
            'requires_coach'        => 'Wajib Coach',
            'requires_catalog_item' => 'Wajib Layanan / Paket',
            'is_active'             => 'Status',
            'sort'                  => 'Urutan',
        ],
    ],

    'status' => [
        'active'   => 'Aktif',
        'inactive' => 'Nonaktif',
        'yes'      => 'Ya',
        'no'       => 'Tidak',
    ],
];
