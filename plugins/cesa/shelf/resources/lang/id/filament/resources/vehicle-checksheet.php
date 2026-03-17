<?php

return [
    'title' => 'Checksheet Kendaraan',

    'navigation' => [
        'title' => 'Checksheet Kendaraan',
        'group' => 'Shelf',
    ],

    'singular' => 'Checksheet Kendaraan',
    'plural'   => 'Checksheet Kendaraan',

    'fields' => [
        'reference_number'   => 'Nomor Referensi',
        'license_plate'      => 'Plat Nomor',
        'pic'                => 'PIC',
        'location'           => 'Lokasi',
        'destination'        => 'Tujuan',
        'start_km'           => 'KM Awal',
        'end_km'             => 'KM Akhir',
        'departure_time'     => 'Waktu Keberangkatan',
        'arrival_time'       => 'Waktu Kedatangan',
        'fuel_consumption'   => 'Konsumsi BBM',
        'notes'              => 'Catatan',
        'photos'             => 'Foto',
        'status'             => 'Status',
    ],

    'sections' => [
        'vehicle_info'       => 'Informasi Kendaraan',
        'departure_info'     => 'Informasi Keberangkatan',
        'arrival_info'       => 'Informasi Kedatangan',
        'additional_info'    => 'Informasi Tambahan',
    ],

    'table' => [
        'columns' => [
            'reference_number'   => 'Nomor Referensi',
            'license_plate'      => 'Plat Nomor',
            'pic'                => 'PIC',
            'location'           => 'Lokasi',
            'destination'        => 'Tujuan',
            'departure_time'     => 'Waktu Keberangkatan',
            'status'             => 'Status',
            'created-at'         => 'Dibuat Pada',
            'updated-at'         => 'Diperbarui Pada',
        ],

        'groups' => [
            'license_plate'      => 'Plat Nomor',
            'location'           => 'Lokasi',
            'status'             => 'Status',
            'updated-at'         => 'Diperbarui Pada',
            'created-at'         => 'Dibuat Pada',
        ],

        'filters' => [
            'license_plate'      => 'Plat Nomor',
            'location'           => 'Lokasi',
            'status'             => 'Status',
            'updated-at'         => 'Diperbarui Pada',
            'created-at'         => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Checksheet kendaraan diperbarui',
                    'body'  => 'Checksheet kendaraan berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Checksheet kendaraan dihapus',
                    'body'  => 'Checksheet kendaraan berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Checksheet kendaraan dihapus',
                    'body'  => 'Checksheet kendaraan berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Checksheet kendaraan dibuat',
                    'body'  => 'Checksheet kendaraan berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'vehicle' => [
                'title' => 'Informasi Kendaraan',

                'entries' => [
                    'reference_number'   => 'Nomor Referensi',
                    'license_plate'      => 'Plat Nomor',
                    'pic'                => 'PIC',
                    'location'           => 'Lokasi',
                    'destination'        => 'Tujuan',
                ],
            ],

            'departure' => [
                'title' => 'Informasi Keberangkatan',

                'entries' => [
                    'start_km'           => 'KM Awal',
                    'departure_time'     => 'Waktu Keberangkatan',
                ],
            ],

            'arrival' => [
                'title' => 'Informasi Kedatangan',

                'entries' => [
                    'end_km'             => 'KM Akhir',
                    'arrival_time'       => 'Waktu Kedatangan',
                    'fuel_consumption'   => 'Konsumsi BBM',
                ],
            ],

            'additional' => [
                'title' => 'Informasi Tambahan',

                'entries' => [
                    'notes'              => 'Catatan',
                    'photos'             => 'Foto',
                ],
            ],
        ],
    ],
];
