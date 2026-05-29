<?php

return [
    'navigation' => [
        'title' => 'Pricing Rules',
    ],

    'singular' => 'Pricing Rule',
    'plural'   => 'Pricing Rules',

    'fields' => [
        'catalog_item'     => 'Service / Package',
        'component'        => 'Component',
        'court_type'       => 'Court Type',
        'court'            => 'Court',
        'coach'            => 'Coach',
        'time_slot'        => 'Time Slot',
        'day_type'         => 'Day Type',
        'name'             => 'Name',
        'calculation_type' => 'Calculation',
        'amount'           => 'Price',
        'percentage'       => 'Percentage',
        'basis'            => 'Percentage Basis',
        'priority'         => 'Priority',
        'starts_at'        => 'Starts At',
        'ends_at'          => 'Ends At',
        'is_active'        => 'Active',
    ],

    'table' => [
        'columns' => [
            'catalog_item'     => 'Service / Package',
            'component'        => 'Component',
            'court_type'       => 'Court Type',
            'court'            => 'Court',
            'coach'            => 'Coach',
            'time_slot'        => 'Time Slot',
            'day_type'         => 'Day Type',
            'name'             => 'Name',
            'calculation_type' => 'Calculation',
            'amount'           => 'Price',
            'percentage'       => 'Percentage',
            'priority'         => 'Priority',
            'starts_at'        => 'Starts At',
            'ends_at'          => 'Ends At',
            'is_active'        => 'Status',
        ],
    ],

    'filters' => [
        'catalog_item' => 'Service / Package',
        'component'    => 'Component',
    ],

    'placeholders' => [
        'all_court_types' => 'All court types',
        'all_courts'      => 'All courts',
        'all_coaches'     => 'All coaches',
        'all_slots'       => 'All time slots',
        'all_days'        => 'All days',
        'total_price'     => 'Service total',
    ],

    'calculation_types' => [
        'fixed'      => 'Fixed Amount',
        'percentage' => 'Percentage',
    ],

    'day_types' => [
        'weekday' => 'Weekday',
        'weekend' => 'Weekend',
    ],

    'status' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ],

    'validation' => [
        'component_not_allowed'         => 'This component is not enabled for the selected service or package.',
        'percentage_required'           => 'Enter the percentage for this component pricing rule.',
        'percentage_requires_component' => 'Percentage pricing can only be used for court or coach components.',
        'court_type_mismatch'           => 'The selected court does not match the selected court type.',
    ],
];
