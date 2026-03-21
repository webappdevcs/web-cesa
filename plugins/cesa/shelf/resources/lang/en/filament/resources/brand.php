<?php

return [
    'title' => 'Brands',

    'navigation' => [
        'title' => 'Brands',
        'group' => 'Asset Master',
    ],

    'singular' => 'Brand',
    'plural'   => 'Brands',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'fields' => [
                    'name' => 'Name',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Name',
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
                    'title' => 'Brand updated',
                    'body'  => 'The brand has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Brand deleted',
                    'body'  => 'The brand has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Brands deleted',
                    'body'  => 'The brands have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Brand created',
                    'body'  => 'The brand has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name' => 'Name',
                ],
            ],
        ],
    ],
];
