<?php

return [
    'navigation' => [
        'title' => 'Coaches',
    ],

    'singular' => 'Coach',
    'plural'   => 'Coaches',

    'fields' => [
        'name'      => 'Name',
        'phone'     => 'Phone',
        'is_active' => 'Active',
        'sort'      => 'Sort',
    ],

    'table' => [
        'columns' => [
            'name'      => 'Name',
            'phone'     => 'Phone',
            'is_active' => 'Status',
            'sort'      => 'Sort',
        ],
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],
];
