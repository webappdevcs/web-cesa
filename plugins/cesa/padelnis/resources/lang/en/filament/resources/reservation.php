<?php

return [
    'navigation' => [
        'title' => 'Reservations',
        'group' => 'Padelnis',
    ],

    'singular' => 'Reservation',
    'plural'   => 'Reservations',

    'fields' => [
        'id_reff'          => 'Reference ID',

        'transaction_type' => 'Transaction Type',
        'catalog_item'     => 'Service / Package',
        'customer_name'    => 'Customer Name',
        'reservation_date' => 'Reservation Date',
        'court'            => 'Court',
        'coach'            => 'Coach',
        'reservation_time' => 'Time',
        'blocked_slots'    => 'Blocked Slot Details',
        'expected_amount'  => 'Expected Amount',
        'quoted_amount'    => 'Quoted Amount',
        'price_breakdown'  => 'Price Breakdown',
        'transfer_amount'  => 'Transfer Amount',
        'transfer_date'    => 'Transfer Date',
        'notes'            => 'Notes',
        'created_at'       => 'Created At',
    ],

    'form' => [
        'sections' => [
            'reservation' => [
                'title' => 'Reservation Details',
            ],
            'payment' => [
                'title' => 'Payment & Status',
            ],
            'metadata' => [
                'title' => 'Metadata',
            ],
        ],

        'placeholders' => [
            'customer_name'    => 'Enter customer name',
            'catalog_item'     => 'Select a service or package',
            'reservation_date' => 'Select reservation date',
            'court'            => 'Select court',
            'coach'            => 'Select coach',
            'reservation_time' => 'Select start time - end time',
            'expected_amount'  => 'Filled automatically from the pricing master',
            'transfer_amount'  => 'Enter transfer amount',
            'transfer_date'    => 'Select transfer date',
            'notes'            => 'Add notes if needed',
        ],
    ],

    'table' => [
        'columns' => [
            'id_reff'          => 'Reference ID',

            'transaction_type' => 'Transaction Type',
            'catalog_item'     => 'Service / Package',
            'customer_name'    => 'Customer Name',
            'reservation_date' => 'Date',
            'reservation_time' => 'Time',
            'blocked_slots'    => 'Blocked Slot Details',
            'court'            => 'Court',
            'coach'            => 'Coach',
            'expected_amount'  => 'Expected Amount',
            'price_breakdown'  => 'Price Breakdown',
            'transfer_amount'  => 'Transfer Amount',
            'transfer_date'    => 'Transfer Date',
            'notes'            => 'Notes',
            'created_at'       => 'Created At',
        ],
    ],

    'filters' => [
        'reservation_from'        => 'Date From',
        'reservation_until'       => 'Date Until',
        'reservation_time'        => 'Time',
        'reservation_range'       => 'Reservation: :from - :until',
        'reservation_from_value'  => 'Reservation from: :date',
        'reservation_until_value' => 'Reservation until: :date',
        'court'                   => 'Court',

        'transaction_type'        => 'Transaction Type',
        'catalog_item'            => 'Service / Package',
        'coach'                   => 'Coach',
    ],

    'actions' => [
        'copy_id_reff' => 'Reference ID copied.',
    ],

    'price_breakdown' => [
        'commissionable' => '(commission)',
        'missing_rule'   => 'pricing rule is not configured',
    ],

    'validation' => [
        'active_slot_unique'              => 'This slot is already reserved for the selected court and date.',
        'court_required'                  => 'A court is required for the selected service or package.',
        'coach_required'                  => 'A coach is required for the selected service or package.',
        'reservation_time_required'       => 'A time is required for the selected service or package.',
        'reservation_time_invalid'        => 'Select a valid reservation time for the selected service or package.',
        'catalog_item_required'           => 'A service or package is required for this transaction.',
        'catalog_item_transaction_type'   => 'The selected service or package does not match the transaction type.',
        'pricing_rules_missing'           => 'Pricing rules are incomplete for these component(s): :components.',
    ],

    'transaction_types' => [
        'regular'  => 'Regular',
        'coaching' => 'Coaching',
        'academy'  => 'Academy',
    ],

    'exports' => [
        'notifications' => [
            'completed_body' => 'The reservation export finished with :success exported row(s) and :failed failed row(s).',
        ],
    ],

    'pages' => [
        'list' => [
            'header_actions' => [
                'create' => [
                    'label' => 'Create Reservation',
                ],
                'export' => [
                    'label' => 'Export Reservations',
                ],
            ],
        ],
    ],
];
