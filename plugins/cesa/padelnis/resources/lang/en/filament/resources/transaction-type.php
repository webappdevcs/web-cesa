<?php

return [
    'navigation' => [
        'title' => 'Transaction Type Master',
    ],

    'singular' => 'Transaction Type',
    'plural'   => 'Transaction Types',

    'fields' => [
        'code'                  => 'Code',
        'name'                  => 'Name',
        'requires_coach'        => 'Requires Coach',
        'requires_catalog_item' => 'Requires Service / Package',
        'is_active'             => 'Active',
        'sort'                  => 'Sort',
    ],

    'helpers' => [
        'code' => 'Use a short code, for example regular, coaching, academy, event, or membership.',
    ],

    'table' => [
        'columns' => [
            'code'                  => 'Code',
            'name'                  => 'Name',
            'requires_coach'        => 'Requires Coach',
            'requires_catalog_item' => 'Requires Service / Package',
            'is_active'             => 'Status',
            'sort'                  => 'Sort',
        ],
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
        'yes'      => 'Yes',
        'no'       => 'No',
    ],
];
