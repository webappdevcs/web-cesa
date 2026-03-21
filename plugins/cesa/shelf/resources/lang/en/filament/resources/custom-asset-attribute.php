<?php

return [
    'title' => 'Custom Asset Attributes',

    'navigation' => [
        'title' => 'Custom Attributes',
        'group' => 'Asset Master',
    ],

    'singular' => 'Custom Asset Attribute',
    'plural'   => 'Custom Asset Attributes',

    'fields' => [
        'name'            => 'Attribute Name',
        'type'            => 'Input Type',
        'required'        => 'Required',
        'is_active'       => 'Active',
        'category'        => 'Category',
    ],

    'sections' => [
        'basic_info'       => 'Basic Information',
        'attribute_status' => 'Attribute Status',
    ],

    'options' => [
        'input_type' => [
            'text'     => 'Text Input',
            'number'   => 'Number Input',
            'textarea' => 'Textarea',
            'date'     => 'Date Picker',
        ],
    ],

    'table' => [
        'columns' => [
            'name'            => 'Attribute Name',
            'type'            => 'Input Type',
            'category'        => 'Category',
            'required'        => 'Required',
            'is_active'       => 'Active',
            'created-at'      => 'Created At',
            'updated-at'      => 'Updated At',
        ],

        'groups' => [
            'name'            => 'Attribute Name',
            'type'            => 'Input Type',
            'category'        => 'Category',
            'updated-at'      => 'Updated At',
            'created-at'      => 'Created At',
        ],

        'filters' => [
            'name'            => 'Attribute Name',
            'type'            => 'Input Type',
            'category'        => 'Category',
            'updated-at'      => 'Updated At',
            'created-at'      => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Custom attribute updated',
                    'body'  => 'The custom attribute has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Custom attribute deleted',
                    'body'  => 'The custom attribute has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Custom attributes deleted',
                    'body'  => 'The custom attributes have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Custom attribute created',
                    'body'  => 'The custom attribute has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'name'            => 'Attribute Name',
                    'type'            => 'Input Type',
                    'required'        => 'Required',
                    'is_active'       => 'Active',
                    'category'        => 'Category',
                ],
            ],
        ],
    ],
];
