<?php

return [
    'title' => 'Vendors',

    'navigation' => [
        'title' => 'Vendors',
        'group' => 'Asset Master',
    ],

    'singular' => 'Vendor',
    'plural'   => 'Vendors',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'fields' => [
                    'name'       => 'Name',
                    'last-price' => 'Last Price',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Name',
            'last-price' => 'Last Price',
            'created-at' => 'Created At',
            'updated-at' => 'Updated At',
        ],

        'groups' => [
            'name'       => 'Name',
            'updated-at' => 'Updated At',
            'created-at' => 'Created At',
        ],

        'filters' => [
            'name'       => 'Name',
            'updated-at' => 'Updated At',
            'created-at' => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Vendor updated',
                    'body'  => 'The vendor has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Vendor deleted',
                    'body'  => 'The vendor has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Vendors deleted',
                    'body'  => 'The vendors have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Vendor created',
                    'body'  => 'The vendor has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'       => 'Name',
                    'last-price' => 'Last Price',
                ],
            ],
        ],
    ],
];
