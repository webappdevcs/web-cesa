<?php

return [
    'title' => 'Asset Requests',

    'navigation' => [
        'title' => 'Asset Requests',
        'group' => 'Shelf',
    ],

    'singular' => 'Asset Request',
    'plural'   => 'Asset Requests',

    'fields' => [
        'request_type'      => 'Request Type',
        'requester_name'    => 'Requester Name',
        'email'             => 'Email',
        'division'          => 'Division',
        'placement'         => 'Placement',
        'item_name'         => 'Item Name',
        'qty'               => 'Quantity',
        'attachment'        => 'Attachment',
        'status'            => 'Status',
        'admin_notes'       => 'Admin Notes',
    ],

    'options' => [
        'request_type' => [
            'pengadaan_aset'   => 'Asset Procurement',
            'perbaikan_aset'   => 'Asset Repair',
            'penarikan_aset'   => 'Asset Withdrawal',
        ],
    ],

    'table' => [
        'columns' => [
            'request_type'      => 'Request Type',
            'requester_name'    => 'Requester Name',
            'division'          => 'Division',
            'item_name'         => 'Item Name',
            'status'            => 'Status',
            'created-at'        => 'Created At',
            'updated-at'        => 'Updated At',
        ],

        'groups' => [
            'request_type'      => 'Request Type',
            'division'          => 'Division',
            'status'            => 'Status',
            'updated-at'        => 'Updated At',
            'created-at'        => 'Created At',
        ],

        'filters' => [
            'request_type'      => 'Request Type',
            'division'          => 'Division',
            'status'            => 'Status',
            'updated-at'        => 'Updated At',
            'created-at'        => 'Created At',
        ],

        'actions' => [
            'view' => [
                'notification' => [
                    'title' => 'Asset request viewed',
                    'body'  => 'The asset request has been viewed successfully.',
                ],
            ],

            'approve' => [
                'notification' => [
                    'title' => 'Asset request approved',
                    'body'  => 'The asset request has been approved successfully.',
                ],
            ],

            'reject' => [
                'notification' => [
                    'title' => 'Asset request rejected',
                    'body'  => 'The asset request has been rejected successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Asset requests deleted',
                    'body'  => 'The asset requests have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Asset request created',
                    'body'  => 'The asset request has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'request_type'      => 'Request Type',
                    'requester_name'    => 'Requester Name',
                    'email'             => 'Email',
                    'division'          => 'Division',
                    'placement'         => 'Placement',
                ],
            ],

            'item_details' => [
                'title' => 'Item Details',

                'entries' => [
                    'item_name'         => 'Item Name',
                    'qty'               => 'Quantity',
                    'attachment'        => 'Attachment',
                ],
            ],

            'status' => [
                'title' => 'Status & Notes',

                'entries' => [
                    'status'            => 'Status',
                    'admin_notes'       => 'Admin Notes',
                ],
            ],
        ],
    ],
];
