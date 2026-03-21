<?php

return [
    'title' => 'Asset Request Approvals',

    'navigation' => [
        'title' => 'Asset Request Approvals',
        'group' => 'Asset Requests',
    ],

    'singular' => 'Asset Request Approval',
    'plural'   => 'Asset Request Approvals',

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'fields' => [
                    'request_type'     => 'Request Type',
                    'division'         => 'Division',
                    'division_helper'  => 'Fill according to the division value in asset-requests form. Leave blank if applicable to all divisions.',
                    'level'            => 'Approval Level',
                    'level_helper'     => 'Approval order (1 = first, 2 = second, etc.)',
                    'approver_name'    => 'Approver Name / Position',
                    'approver_email'   => 'Approver Email',
                ],
            ],
        ],
    ],

    'fields' => [
        'request_type'     => 'Request Type',
        'division'         => 'Division',
        'level'            => 'Approval Level',
        'approver_name'    => 'Approver Name',
        'approver_email'   => 'Approver Email',
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
            'request_type'     => 'Request Type',
            'division'         => 'Division',
            'level'            => 'Level',
            'approver_name'    => 'Approver Name',
            'approver_email'   => 'Approver Email',
            'created-at'       => 'Created At',
            'updated-at'       => 'Updated At',
        ],

        'groups' => [
            'request_type'     => 'Request Type',
            'division'         => 'Division',
            'updated-at'       => 'Updated At',
            'created-at'       => 'Created At',
        ],

        'filters' => [
            'request_type'     => 'Request Type',
            'division'         => 'Division',
            'updated-at'       => 'Updated At',
            'created-at'       => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Approval level updated',
                    'body'  => 'The approval level has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Approval level deleted',
                    'body'  => 'The approval level has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Approval levels deleted',
                    'body'  => 'The approval levels have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Approval level created',
                    'body'  => 'The approval level has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General Information',

                'entries' => [
                    'request_type'     => 'Request Type',
                    'division'         => 'Division',
                    'level'            => 'Approval Level',
                    'approver_name'    => 'Approver Name',
                    'approver_email'   => 'Approver Email',
                ],
            ],
        ],
    ],
];
