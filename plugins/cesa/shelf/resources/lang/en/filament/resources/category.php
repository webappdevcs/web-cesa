<?php

return [
    'title' => 'Categories',

    'navigation' => [
        'title' => 'Categories',
        'group' => 'Asset Master',
    ],

    'singular' => 'Category',
    'plural'   => 'Categories',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'fields' => [
                    'name'      => 'Name',
                    'parent-id' => 'Parent Category',
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
                    'title' => 'Category updated',
                    'body'  => 'The category has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Category deleted',
                    'body'  => 'The category has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Categories deleted',
                    'body'  => 'The categories have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Category created',
                    'body'  => 'The category has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'      => 'Name',
                    'parent-id' => 'Parent Category',
                ],
            ],
        ],
    ],
];
