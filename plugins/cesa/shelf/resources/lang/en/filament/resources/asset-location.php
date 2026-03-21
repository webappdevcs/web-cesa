<?php

return [
    'title' => 'Asset Locations',

    'navigation' => [
        'title' => 'Asset Locations',
        'group' => 'Asset Master',
    ],

    'singular' => 'Asset Location',
    'plural'   => 'Asset Locations',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'fields' => [
                    'name'        => 'Name',
                    'address'     => 'Address',
                    'description' => 'Description',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'        => 'Name',
            'address'     => 'Address',
            'description' => 'Description',
            'created-at'  => 'Created At',
            'updated-at'  => 'Updated At',
        ],

        'groups' => [
            'name'       => 'Name',
            'address'    => 'Address',
            'updated-at' => 'Updated At',
            'created-at' => 'Created At',
        ],

        'filters' => [
            'name'       => 'Name',
            'address'    => 'Address',
            'updated-at' => 'Updated At',
            'created-at' => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Asset Location updated',
                    'body'  => 'The asset location has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Asset Location deleted',
                    'body'  => 'The asset location has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Asset Locations deleted',
                    'body'  => 'The asset locations have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Asset Location created',
                    'body'  => 'The asset location has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'        => 'Name',
                    'address'     => 'Address',
                    'description' => 'Description',
                ],
            ],
        ],
    ],
];
