<?php

return [
    'title' => 'Tasks',

    'navigation' => [
        'title' => 'Tasks',
        'group' => 'Shelf',
    ],

    'singular' => 'Task',
    'plural'   => 'Tasks',

    'fields' => [
        'code'               => 'Letter Number',
        'name'               => 'Task Name',
        'cost'               => 'Cost',
        'work_timestamp'     => 'Work Date',
        'description'        => 'Description',
        'location'           => 'Location',
        'business_entity'    => 'Badan Usaha',
        'pic'                => 'PIC',
        'vendor'             => 'Vendor',
        'status'             => 'Status',
        'document_upload'    => 'Document Upload',
    ],

    'sections' => [
        'general'          => 'General Information',
        'vendor_info'      => 'Vendor Information',
        'attachment'       => 'Attachment',
    ],

    'options' => [
        'status' => [
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
        ],
    ],

    'table' => [
        'columns' => [
            'code'               => 'Letter Number',
            'business_entity'    => 'Badan Usaha',
            'name'               => 'Task Name',
            'vendor'             => 'Vendor',
            'cost'               => 'Cost',
            'location'           => 'Location',
            'pic'                => 'PIC',
            'status'             => 'Status',
            'work_timestamp'     => 'Work Date',
            'created-at'         => 'Created At',
            'updated-at'         => 'Updated At',
        ],

        'groups' => [
            'business_entity'    => 'Badan Usaha',
            'vendor'             => 'Vendor',
            'status'             => 'Status',
            'pic'                => 'PIC',
            'updated-at'         => 'Updated At',
            'created-at'         => 'Created At',
        ],

        'filters' => [
            'business_entity'    => 'Badan Usaha',
            'vendor'             => 'Vendor',
            'status'             => 'Status',
            'pic'                => 'PIC',
            'updated-at'         => 'Updated At',
            'created-at'         => 'Created At',
        ],

        'actions' => [
            'view' => [
                'notification' => [
                    'title' => 'Task viewed',
                    'body'  => 'The task has been viewed successfully.',
                ],
            ],

            'edit' => [
                'notification' => [
                    'title' => 'Task updated',
                    'body'  => 'The task has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Task deleted',
                    'body'  => 'The task has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Tasks deleted',
                    'body'  => 'The tasks have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Task created',
                    'body'  => 'The task has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'code'               => 'Letter Number',
                    'name'               => 'Task Name',
                    'cost'               => 'Cost',
                    'work_timestamp'     => 'Work Date',
                    'description'        => 'Description',
                    'location'           => 'Location',
                    'business_entity'    => 'Badan Usaha',
                    'pic'                => 'PIC',
                    'status'             => 'Status',
                ],
            ],

            'vendor' => [
                'title' => 'Vendor Information',

                'entries' => [
                    'vendor'             => 'Vendor',
                ],
            ],

            'document' => [
                'title' => 'Document',

                'entries' => [
                    'document_upload'    => 'Document Upload',
                ],
            ],
        ],
    ],
];
