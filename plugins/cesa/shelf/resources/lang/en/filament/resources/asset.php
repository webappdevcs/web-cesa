<?php

return [
    'title' => 'Assets',

    'navigation' => [
        'title' => 'Assets',
        'group' => 'Shelf',
    ],

    'singular' => 'Asset',
    'plural'   => 'Assets',

    'fields' => [
        'category'                       => 'Category',
        'brand'                          => 'Brand',
        'name'                           => 'Name',
        'attribute'                      => 'Attribute',
        'attribute_value'                => 'Attribute Value',
        'recipient_business_entity'      => 'Recipient Badan Usaha',
        'recipient'                      => 'Recipient',
        'purchase_date'                  => 'Purchase Date',
        'business_entity'                => 'Badan Usaha',
        'item_price'                     => 'Item Price',
        'qty'                            => 'Quantity',
        'asset_location'                 => 'Asset Location',
        'image'                          => 'Asset Image',
    ],

    'labels' => [
        'category'          => 'Category',
        'brand'             => 'Brand',
        'type'              => 'Type',
        'asset_image'       => 'Asset Image',
        'purchase_date'     => 'Purchase Date',
        'item_price'        => 'Price',
        'qty'               => 'Quantity',
        'business_entity'   => 'Badan Usaha',
        'asset_location'    => 'Asset Location',
    ],

    'lifecycle' => [
        'guide_title'           => 'Guide',
        'guide_content'         => 'Manage the physical condition of assets and NBH documents in case of loss or damage.',
        'condition_status'      => 'Condition Status',
        'condition_helper'      => 'Change to "Lost" or "Damaged" when an incident is found.',
        'nbh_status'            => 'NBH Status',
        'nbh_helper'            => 'Update when the replacement process is complete.',
        'incident_date'         => 'Incident Date',
        'incident_helper'       => 'Date when the lost or damaged asset was discovered.',
        'responsible_person'    => 'Responsible Person',
        'responsible_helper'    => 'The party responsible for NBH.',
        'audit_document'        => 'Audit Document',
        'audit_helper'          => 'Upload minutes or audit evidence (PDF/JPG, max 4 MB). Required when NBH is complete.',
        'nbh_document'          => 'Lost Goods Note (NBH)',
        'nbh_helper_text'       => 'Upload proof of replacement or completed NBH note.',
        'nbh_notes'             => 'NBH Notes',
        'nbh_notes_placeholder' => 'Enter a brief chronology, audit results, or follow-up.',
    ],

    'recipient' => [
        'guide_title'               => 'Recipient Settings',
        'guide_content'             => 'Optional: manually adjust asset recipient for special cases.',
        'recipient_helper'          => 'Leave blank to follow the last transfer data.',
        'recipient_select_helper'   => 'Select the current asset holder.',
    ],

    'filters' => [
        'label'                       => 'Filters',
        'serial_number'               => 'Serial Number',
        'serial_number_placeholder'   => 'Search serial number...',
        'imei'                        => 'IMEI',
        'imei_placeholder'            => 'Search IMEI 1 / IMEI 2...',
        'min_price'                   => 'Minimum Price',
        'max_price'                   => 'Maximum Price',
        'filter_audit'                => 'Filters',
        'category'                    => 'Category',
        'updated-at'                  => 'Updated At',
        'created-at'                  => 'Created At',
    ],

    'actions' => [
        'move_to_attributes' => 'Sync Asset Attributes',
    ],

    'info_section'        => 'Asset Information',
    'attributes_section'  => 'Custom Attributes',
    'purchase_section'    => 'Purchase Details',
    'status_section'      => 'Status & NBH',
    'documents_section'   => 'Supporting Documents',

    'validation_status'   => 'Validation Status',
    'valid'               => 'Valid',
    'invalid'             => 'Invalid',
    'asset_holder'        => 'Asset Holder',

    'table' => [
        'columns' => [
            'purchase_date'      => 'Purchase Date',
            'business_entity'    => 'Badan Usaha',
            'name'               => 'Name',
            'category'           => 'Category',
            'brand'              => 'Brand',
            'type'               => 'Type',
            'serial_number'      => 'Serial Number',
            'imei1'              => 'IMEI 1',
            'imei2'              => 'IMEI 2',
            'item_price'         => 'Item Price',
            'item_age'           => 'Item Age',
            'qty'                => 'Quantity',
            'asset_location'     => 'Asset Location',
            'condition_status'   => 'Condition Status',
            'nbh_status'         => 'NBH Status',
            'created-at'         => 'Created At',
            'updated-at'         => 'Updated At',
        ],

        'groups' => [
            'category'         => 'Category',
            'brand'            => 'Brand',
            'business_entity'  => 'Badan Usaha',
            'asset_location'   => 'Asset Location',
            'condition_status' => 'Condition Status',
            'nbh_status'       => 'NBH Status',
            'updated-at'       => 'Updated At',
            'created-at'       => 'Created At',
        ],

        'filters' => [
            'label'                       => 'Filters',
            'serial_number'               => 'Serial Number',
            'serial_number_placeholder'   => 'Search serial number...',
            'imei'                        => 'IMEI',
            'imei_placeholder'            => 'Search IMEI 1 / IMEI 2...',
            'min_price'                   => 'Minimum Price',
            'max_price'                   => 'Maximum Price',
            'filter_audit'                => 'Filters',
            'category'                    => 'Category',
            'updated-at'                  => 'Updated At',
            'created-at'                  => 'Created At',
        ],

        'actions' => [
            'move_to_attributes' => 'Sync Asset Attributes',

            'edit' => [
                'notification' => [
                    'title' => 'Asset updated',
                    'body'  => 'The asset has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Asset deleted',
                    'body'  => 'The asset has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Assets deleted',
                    'body'  => 'The assets have been deleted successfully.',
                ],
            ],

            'move_to_attributes' => [
                'notification' => [
                    'title' => 'Asset attributes synced',
                    'body'  => 'Asset attributes were synced successfully for the selected assets.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Asset created',
                    'body'  => 'The asset has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'info' => [
                'title' => 'Asset Information',

                'entries' => [
                    'name'           => 'Asset Name',
                    'category'       => 'Category',
                    'brand'          => 'Brand',
                    'type'           => 'Type',
                    'image'          => 'Asset Image',
                ],
            ],

            'attributes' => [
                'title' => 'Custom Attributes',
            ],

            'purchase' => [
                'title' => 'Purchase Details',

                'entries' => [
                    'purchase_date'   => 'Purchase Date',
                    'item_price'      => 'Price',
                    'qty'             => 'Quantity',
                    'business_entity' => 'Badan Usaha',
                ],
            ],

            'status' => [
                'title' => 'Status & NBH',

                'entries' => [
                    'condition_status'    => 'Condition Status',
                    'nbh_status'          => 'NBH Status',
                    'validation_status'   => 'Validation Status',
                    'valid'               => 'Valid',
                    'invalid'             => 'Invalid',
                    'asset_location'      => 'Asset Location',
                    'asset_holder'        => 'Asset Holder',
                    'incident_date'       => 'Incident Date',
                    'responsible_person'  => 'Responsible Person',
                    'nbh_notes'           => 'NBH Notes',
                ],
            ],

            'documents' => [
                'title' => 'Supporting Documents',

                'entries' => [
                    'audit_document' => 'Audit Document',
                    'nbh_document'   => 'Lost Goods Note (NBH)',
                ],
            ],
        ],
    ],

    'notifications' => [
        'success'           => 'Success',
        'attributes_moved'  => 'Asset attributes were synced successfully for the selected assets.',
    ],
];
