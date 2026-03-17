<?php

return [
    'title' => 'Vehicle Checksheets',

    'navigation' => [
        'title' => 'Vehicle Checksheets',
        'group' => 'Shelf',
    ],

    'singular' => 'Vehicle Checksheet',
    'plural'   => 'Vehicle Checksheets',

    'fields' => [
        'reference_number'   => 'Reference Number',
        'license_plate'      => 'License Plate',
        'pic'                => 'PIC',
        'location'           => 'Location',
        'destination'        => 'Destination',
        'start_km'           => 'Start KM',
        'end_km'             => 'End KM',
        'departure_time'     => 'Departure Time',
        'arrival_time'       => 'Arrival Time',
        'fuel_consumption'   => 'Fuel Consumption',
        'notes'              => 'Notes',
        'photos'             => 'Photos',
        'status'             => 'Status',
    ],

    'sections' => [
        'vehicle_info'       => 'Vehicle Information',
        'departure_info'     => 'Departure Information',
        'arrival_info'       => 'Arrival Information',
        'additional_info'    => 'Additional Information',
    ],

    'table' => [
        'columns' => [
            'reference_number'   => 'Reference Number',
            'license_plate'      => 'License Plate',
            'pic'                => 'PIC',
            'location'           => 'Location',
            'destination'        => 'Destination',
            'departure_time'     => 'Departure Time',
            'status'             => 'Status',
            'created-at'         => 'Created At',
            'updated-at'         => 'Updated At',
        ],

        'groups' => [
            'license_plate'      => 'License Plate',
            'location'           => 'Location',
            'status'             => 'Status',
            'updated-at'         => 'Updated At',
            'created-at'         => 'Created At',
        ],

        'filters' => [
            'license_plate'      => 'License Plate',
            'location'           => 'Location',
            'status'             => 'Status',
            'updated-at'         => 'Updated At',
            'created-at'         => 'Created At',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Vehicle checksheet updated',
                    'body'  => 'The vehicle checksheet has been updated successfully.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Vehicle checksheet deleted',
                    'body'  => 'The vehicle checksheet has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Vehicle checksheets deleted',
                    'body'  => 'The vehicle checksheets have been deleted successfully.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Vehicle checksheet created',
                    'body'  => 'The vehicle checksheet has been created successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'vehicle' => [
                'title' => 'Vehicle Information',

                'entries' => [
                    'reference_number'   => 'Reference Number',
                    'license_plate'      => 'License Plate',
                    'pic'                => 'PIC',
                    'location'           => 'Location',
                    'destination'        => 'Destination',
                ],
            ],

            'departure' => [
                'title' => 'Departure Information',

                'entries' => [
                    'start_km'           => 'Start KM',
                    'departure_time'     => 'Departure Time',
                ],
            ],

            'arrival' => [
                'title' => 'Arrival Information',

                'entries' => [
                    'end_km'             => 'End KM',
                    'arrival_time'       => 'Arrival Time',
                    'fuel_consumption'   => 'Fuel Consumption',
                ],
            ],

            'additional' => [
                'title' => 'Additional Information',

                'entries' => [
                    'notes'              => 'Notes',
                    'photos'             => 'Photos',
                ],
            ],
        ],
    ],
];
