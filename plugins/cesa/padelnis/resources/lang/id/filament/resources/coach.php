<?php

return [
    'navigation' => [
        'title' => 'Coach',
    ],

    'singular' => 'Coach',
    'plural'   => 'Coach',

    'fields' => [
        'name'      => 'Nama',
        'phone'     => 'Telepon',
        'is_active' => 'Aktif',
        'sort'      => 'Urutan',
    ],

    'table' => [
        'columns' => [
            'name'      => 'Nama',
            'phone'     => 'Telepon',
            'is_active' => 'Status',
            'sort'      => 'Urutan',
        ],
    ],

    'status' => [
        'active'   => 'Aktif',
        'inactive' => 'Nonaktif',
    ],
];
