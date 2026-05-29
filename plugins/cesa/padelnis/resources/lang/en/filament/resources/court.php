<?php

return [
    'navigation' => [
        'title' => 'Courts',
    ],

    'singular' => 'Court',
    'plural'   => 'Courts',

    'fields' => [
        'name'       => 'Name',
        'court_type' => 'Court Type',
        'is_active'  => 'Active',
        'sort'       => 'Sort',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Name',
            'court_type' => 'Court Type',
            'is_active'  => 'Status',
            'sort'       => 'Sort',
        ],
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],
];
