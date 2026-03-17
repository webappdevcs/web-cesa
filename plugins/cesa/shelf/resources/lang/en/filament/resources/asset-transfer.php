<?php

return [
    'title' => 'Asset Transfers',

    'navigation' => [
        'title' => 'Asset Transfers',
        'group' => 'Shelf',
    ],

    'singular' => 'Asset Transfer',
    'plural'   => 'Asset Transfers',

    'fields' => [
        'letter_number'      => 'Letter Number',
        'business_entity'    => 'Badan Usaha',
        'from_user'          => 'From',
        'to_user'            => 'To',
        'transfer_date'      => 'Transfer Date',
        'notes'              => 'Notes',
        'attachment'         => 'Attachment',
        'assets'             => 'Assets',
    ],

    'sections' => [
        'general'          => 'General Information',
        'transfer_details' => 'Transfer Details',
        'assets_list'      => 'Assets List',
    ],

    'table' => [
        'columns' => [
            'letter_number'      => 'Letter Number',
            'business_entity'    => 'Badan Usaha',
            'from_user'          => 'From',
            'to_user'            => 'To',
            'transfer_date'      => 'Transfer Date',
            'assets_count'       => 'Assets Count',
            'created-at'         => 'Created At',
            'updated-at'         => 'Updated At',
        ],

        'groups' => [
            'business_entity'    => 'Badan Usaha',
            'from_user'          => 'From',
            'to_user'            => 'To',
            'transfer_date'      => 'Transfer Date',
            'updated-at'         => 'Updated At',
            'created-at'         => 'Created At',
        ],

        'filters' => [
            'business_entity'    => 'Badan Usaha',
            'from_user'          => 'From',
            'to_user'            => 'To',
            'transfer_date'      => 'Transfer Date',
            'updated-at'         => 'Updated At',
            'created-at'         => 'Created At',
        ],

        'actions' => [
            'view' => [
                'notification' => [
                    'title' => 'Asset transfer viewed',
                    'body'  => 'The asset transfer has been viewed successfully.',
                ],
            ],

            'edit' => [
                'notification' => [
                    'title' => 'Asset transfer updated',
                    'body'  => 'The asset transfer has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Asset transfer deleted',
                    'body'  => 'The asset transfer has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Asset transfers deleted',
                    'body'  => 'The asset transfers have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Asset transfer created',
                    'body'  => 'The asset transfer has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'letter_number'      => 'Letter Number',
                    'business_entity'    => 'Badan Usaha',
                    'from_user'          => 'From',
                    'to_user'            => 'To',
                    'transfer_date'      => 'Transfer Date',
                    'notes'              => 'Notes',
                    'attachment'         => 'Attachment',
                ],
            ],

            'assets' => [
                'title' => 'Transferred Assets',
            ],
        ],
    ],
];
