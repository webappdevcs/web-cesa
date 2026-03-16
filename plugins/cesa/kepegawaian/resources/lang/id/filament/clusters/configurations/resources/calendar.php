<?php

return [
    'title' => 'Jadwal Kerja',

    'navigation' => [
        'title' => 'Jadwal Kerja',
        'group' => 'Karyawan',
    ],

    'groups' => [
        'status'     => 'Status',
        'created-by' => 'Dibuat Oleh',
        'created-at' => 'Dibuat Pada',
        'updated-at' => 'Diperbarui Pada',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Informasi Umum',
                'fields' => [
                    'name'                  => 'Nama',
                    'schedule-name'         => 'Nama Jadwal',
                    'schedule-name-tooltip' => 'Masukkan nama jadwal kerja yang deskriptif.',
                    'timezone'              => 'Zona Waktu',
                    'timezone-tooltip'      => 'Pilih zona waktu untuk jadwal kerja.',
                    'company'               => 'Perusahaan',
                ],
            ],

            'configuration' => [
                'title'  => 'Konfigurasi Jam Kerja',
                'fields' => [
                    'hours-per-day'                   => 'Jam Per Hari',
                    'hours-per-day-suffix'            => 'Jam',
                    'full-time-required-hours'        => 'Jam Kerja Penuh Wajib',
                    'full-time-required-hours-suffix' => 'Jam Per Minggu',
                ],
            ],

            'flexibility' => [
                'title'  => 'Fleksibilitas',
                'fields' => [
                    'status'                     => 'Status',
                    'two-weeks-calendar'         => 'Kalender Dua Minggu',
                    'two-weeks-calendar-tooltip' => 'Aktifkan pola kerja dua minggu bergantian.',
                    'flexible-hours'             => 'Jam Fleksibel',
                    'flexible-hours-tooltip'     => 'Izinkan karyawan memiliki jam kerja yang fleksibel.',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'id'             => 'ID',
            'name'           => 'Nama Jadwal',
            'timezone'       => 'Zona Waktu',
            'company'        => 'Perusahaan',
            'flexible-hours' => 'Jam Fleksibel',
            'status'         => 'Status',
            'daily-hours'    => 'Jam Harian',
            'created-by'     => 'Dibuat Oleh',
            'created-at'     => 'Dibuat Pada',
            'updated-at'     => 'Diperbarui Pada',
        ],

        'filters' => [
            'company'                  => 'Perusahaan',
            'is-active'                => 'Status',
            'two-week-calendar'        => 'Kalender Dua Minggu',
            'flexible-hours'           => 'Jam Fleksibel',
            'timezone'                 => 'Zona Waktu',
            'name'                     => 'Nama Jadwal',
            'attendance'               => 'Presensi',
            'created-by'               => 'Dibuat Oleh',
            'daily-hours'              => 'Jam Harian',
            'full-time-required-hours' => 'Jam Kerja Penuh Wajib',
            'updated-at'               => 'Diperbarui Pada',
            'created-at'               => 'Dibuat Pada',
        ],

        'groups' => [
            'name'           => 'Nama Jadwal',
            'status'         => 'Status',
            'timezone'       => 'Zona Waktu',
            'flexible-hours' => 'Jam Fleksibel',
            'daily-hours'    => 'Jam Harian',
            'created-by'     => 'Dibuat Oleh',
            'created-at'     => 'Dibuat Pada',
            'updated-at'     => 'Diperbarui Pada',
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Rencana kalender dipulihkan',
                    'body'  => 'Rencana kalender berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rencana kalender dihapus',
                    'body'  => 'Rencana kalender berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rencana kalender dihapus permanen',
                    'body'  => 'Rencana kalender berhasil dihapus permanen.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Rencana kalender dipulihkan',
                    'body'  => 'Rencana kalender berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rencana kalender dihapus',
                    'body'  => 'Rencana kalender berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rencana kalender dihapus permanen',
                    'body'  => 'Rencana kalender berhasil dihapus permanen.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'Informasi Umum',
                'entries' => [
                    'name'                  => 'Nama',
                    'schedule-name'         => 'Nama Jadwal',
                    'schedule-name-tooltip' => 'Masukkan nama jadwal kerja yang deskriptif.',
                    'timezone'              => 'Zona Waktu',
                    'timezone-tooltip'      => 'Pilih zona waktu untuk jadwal kerja.',
                    'company'               => 'Perusahaan',
                ],
            ],

            'configuration' => [
                'title'   => 'Konfigurasi Jam Kerja',
                'entries' => [
                    'hours-per-day'                   => 'Jam Per Hari',
                    'hours-per-day-suffix'            => ' Jam',
                    'full-time-required-hours'        => 'Jam Kerja Penuh Wajib',
                    'full-time-required-hours-suffix' => ' Jam Per Minggu',
                ],
            ],

            'flexibility' => [
                'title'   => 'Fleksibilitas',
                'entries' => [
                    'status'                     => 'Status',
                    'two-weeks-calendar'         => 'Kalender Dua Minggu',
                    'two-weeks-calendar-tooltip' => 'Aktifkan pola kerja dua minggu bergantian.',
                    'flexible-hours'             => 'Jam Fleksibel',
                    'flexible-hours-tooltip'     => 'Izinkan karyawan memiliki jam kerja yang fleksibel.',
                ],
            ],
        ],
    ],
];
