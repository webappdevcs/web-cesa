<?php

return [
    'title'       => 'Padelnis Reservation Form',
    'description' => 'Please complete the reservation details below.',
    'required'    => '* Required questions',

    'placeholders' => [
        'customer_name'    => 'Enter customer name',
        'catalog_item'     => 'Select a service or package',
        'reservation_date' => 'Select reservation date',
        'court'            => 'Select court',
        'coach'            => 'Select coach',
        'reservation_time' => 'Select start time - end time',
        'expected_amount'  => 'Filled automatically from the pricing master',
        'transfer_amount'  => 'Example: 150,000',
        'transfer_date'    => 'Select transfer date',
        'notes'            => 'Example: Transfer from BCA under Budi',
    ],

    'actions' => [
        'submit'         => 'Submit Reservation',
        'submit_another' => 'Submit another reservation',
    ],

    'pagination' => [
        'single_page' => 'Page :current of :total',
    ],

    'summary' => [
        'title'       => 'Reservation Submitted',
        'description' => 'The reservation is pending admin review. Please keep the reference ID for follow-up.',
    ],

    'messages' => [
        'generic' => 'Something went wrong while submitting the reservation. Please try again.',
    ],

    'notifications' => [
        'submitted' => [
            'title' => 'Reservation submitted',
            'body'  => 'Reservation was saved with Reference ID :id_reff.',
        ],
        'failed' => [
            'title' => 'Reservation submission failed',
            'body'  => 'The reservation could not be saved. Please try again.',
        ],
    ],
];
