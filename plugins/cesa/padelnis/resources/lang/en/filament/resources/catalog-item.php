<?php

return [
    'navigation' => [
        'title' => 'Services & Base Prices',
    ],

    'singular' => 'Service & Base Price',
    'plural'   => 'Services & Base Prices',

    'fields' => [
        'name'                   => 'Name',
        'description'            => 'Description',
        'transaction_type'       => 'Transaction Type',
        'requires_court'         => 'Requires Court',
        'requires_coach'         => 'Requires Coach',
        'pricing_mode'           => 'Pricing Mode',
        'time_slot'              => 'Time Slot',
        'price_amount'           => 'Base Price',
        'uses_component_pricing' => 'Use Component Pricing',
        'price_components'       => 'Price Components',
        'duration_hours'         => 'Duration Hours',
        'session_count'          => 'Session Count',
        'allow_public_booking'   => 'Public Booking',
        'is_active'              => 'Active',
        'sort'                   => 'Sort',
    ],

    'table' => [
        'columns' => [
            'name'                 => 'Name',
            'transaction_type'     => 'Transaction Type',
            'requires_court'       => 'Requires Court',
            'requires_coach'       => 'Requires Coach',
            'pricing_mode'         => 'Pricing Mode',
            'time_slot'            => 'Time Slot',
            'price_amount'         => 'Base Price',
            'price_components'     => 'Price Components',
            'duration_hours'       => 'Duration Hours',
            'session_count'        => 'Session Count',
            'allow_public_booking' => 'Public Booking',
            'is_active'            => 'Status',
        ],
    ],

    'filters' => [
        'transaction_type' => 'Transaction Type',
    ],

    'placeholders' => [
        'all_slots' => 'All time slots',
    ],

    'helpers' => [
        'uses_component_pricing' => 'Split this service into court and coach pricing components. Leave off to use the base price directly.',
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
        'yes'      => 'Yes',
        'no'       => 'No',
    ],

    'pricing_modes' => [
        'per_slot' => 'Per Time Slot',
        'fixed'    => 'Fixed Package Price',
    ],

    'price_components' => [
        'fields' => [
            'component'           => 'Component',
            'calculation_type'    => 'Calculation',
            'amount'              => 'Amount',
            'percentage'          => 'Percentage',
            'basis'               => 'Percentage Basis',
            'source_catalog_item' => 'Source Service',
            'is_commissionable'   => 'Commissionable',
        ],
        'components' => [
            'court' => 'Court',
            'coach' => 'Coach',
        ],
        'calculation_types' => [
            'fixed'        => 'Fixed Amount',
            'percentage'   => 'Percentage',
            'catalog_item' => 'Use Service Price',
        ],
        'bases' => [
            'quoted_amount'   => 'Quoted Amount',
            'transfer_amount' => 'Transfer Amount',
        ],
        'placeholders' => [
            'current_catalog_item' => 'Current service',
        ],
        'actions' => [
            'add' => 'Add component',
        ],
        'summary' => [
            'fixed'           => 'fixed Rp:amount',
            'percentage'      => ':percentage%',
            'catalog_item'    => 'service price',
            'priced_by_rules' => 'set in Pricing Rules',
        ],
    ],
];
