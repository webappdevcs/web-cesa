<?php

return [
    'title' => 'Mata Uang',

    'navigation' => [
        'title' => 'Mata Uang',
        'group' => 'Pengaturan',
    ],

    'form' => [
        'sections' => [
            'currency-details' => [
                'title' => 'Informasi Mata Uang',

                'fields' => [
                    'name'         => 'Nama Mata Uang',
                    'name-tooltip' => 'Masukkan nama resmi mata uang',
                    'symbol'       => 'Simbol Mata Uang',
                    'full-name'    => 'Nama Lengkap',
                    'iso-numeric'  => 'Kode ISO Numerik',
                ],
            ],

            'format-information' => [
                'title' => 'Konfigurasi Format',

                'fields' => [
                    'decimal-places'       => 'Jumlah Desimal',
                    'rounding'             => 'Presisi Pembulatan',
                    'rounding-helper-text' => 'Atur presisi pembulatan untuk perhitungan mata uang',
                ],
            ],

            'status-and-configuration-information' => [
                'title' => 'Status & Konfigurasi',

                'fields' => [
                    'status' => 'Status',
                ],
            ],

            'rates' => [
                'title'       => 'Kurs Mata Uang',
                'description' => 'Kelola kurs historis untuk mata uang ini relatif terhadap mata uang dasar (:currency).',

                'fields' => [
                    'name'              => 'Tanggal',
                    'unit-per-currency' => 'Unit Per :currency',
                    'currency-per-unit' => ':currency Per Unit',
                ],

                'add-rate'   => 'Tambah Kurs',
                'item-label' => 'Kurs',
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'           => 'Nama Mata Uang',
            'symbol'         => 'Simbol',
            'full-name'      => 'Nama Lengkap',
            'iso-numeric'    => 'Kode ISO',
            'decimal-places' => 'Jumlah Desimal',
            'rounding'       => 'Pembulatan',
            'status'         => 'Status',
            'created-at'     => 'Dibuat Pada',
            'updated-at'     => 'Diperbarui Pada',
        ],

        'groups' => [
            'name'           => 'Nama',
            'status'         => 'Status',
            'decimal-places' => 'Jumlah Desimal',
            'creation-date'  => 'Tanggal Pembuatan',
            'last-update'    => 'Pembaruan Terakhir',
        ],

        'filters' => [
            'status' => 'Status',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Mata uang dihapus',
                    'body'  => 'Mata uang berhasil dihapus.',

                    'success' => [
                        'title' => 'Mata uang dihapus',
                        'body'  => 'Mata uang berhasil dihapus.',
                    ],

                    'error' => [
                        'title' => 'Mata uang tidak dapat dihapus',
                        'body'  => 'Mata uang tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Mata uang dihapus',
                    'body'  => 'Mata uang berhasil dihapus.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'currency-details' => [
                'title' => 'Informasi Mata Uang',

                'entries' => [
                    'name'        => 'Nama Mata Uang',
                    'symbol'      => 'Simbol Mata Uang',
                    'full-name'   => 'Nama Lengkap',
                    'iso-numeric' => 'Kode ISO Numerik',
                ],
            ],

            'format-information' => [
                'title' => 'Konfigurasi Format',

                'entries' => [
                    'decimal-places' => 'Jumlah Desimal',
                    'rounding'       => 'Presisi Pembulatan',
                ],
            ],

            'status-and-configuration-information' => [
                'title' => 'Status & Konfigurasi',

                'entries' => [
                    'status' => 'Status',
                ],
            ],

            'rates' => [
                'title' => 'Kurs Mata Uang',

                'entries' => [
                    'name'              => 'Tanggal',
                    'unit-per-currency' => 'Unit Per :currency',
                    'currency-per-unit' => ':currency Per Unit',
                ],
            ],
        ],
    ],
];
