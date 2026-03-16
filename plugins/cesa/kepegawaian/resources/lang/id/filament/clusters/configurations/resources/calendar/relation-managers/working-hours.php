<?php

return [
    'modal' => [
        'title' => 'Jam Kerja',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Informasi Umum',
                'fields' => [
                    'attendance-name' => 'Nama Presensi',
                    'day-of-week'     => 'Hari',
                ],
            ],

            'timing-information' => [
                'title' => 'Informasi Waktu',

                'fields' => [
                    'day-period' => 'Periode Hari',
                    'week-type'  => 'Tipe Minggu',
                    'work-from'  => 'Jam Mulai',
                    'work-to'    => 'Jam Selesai',
                ],
            ],

            'date-information' => [
                'title' => 'Informasi Tanggal',

                'fields' => [
                    'starting-date' => 'Tanggal Mulai',
                    'ending-date'   => 'Tanggal Selesai',
                ],
            ],

            'additional-information' => [
                'title' => 'Informasi Tambahan',

                'fields' => [
                    'durations-days' => 'Durasi (Hari)',
                    'display-type'   => 'Tipe Tampilan',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'          => 'Nama Presensi',
            'day-of-week'   => 'Hari',
            'day-period'    => 'Periode Hari',
            'work-from'     => 'Jam Mulai',
            'work-to'       => 'Jam Selesai',
            'starting-date' => 'Tanggal Mulai',
            'ending-date'   => 'Tanggal Selesai',
            'display-type'  => 'Tipe Tampilan',
            'created-by'    => 'Dibuat Oleh',
            'created-at'    => 'Dibuat Pada',
            'updated-at'    => 'Diperbarui Pada',
        ],

        'groups' => [
            'activity-type' => 'Tipe Aktivitas',
            'assignment'    => 'Penugasan',
            'assigned-to'   => 'Ditugaskan Kepada',
            'interval'      => 'Interval',
            'delay-unit'    => 'Satuan Penundaan',
            'delay-from'    => 'Sumber Penundaan',
            'created-by'    => 'Dibuat Oleh',
            'created-at'    => 'Dibuat Pada',
            'updated-at'    => 'Diperbarui Pada',
        ],

        'filters' => [
            'display-type' => 'Tipe Tampilan',
            'day-of-week'  => 'Hari',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Jam kerja diperbarui',
                    'body'  => 'Jam kerja berhasil diperbarui.',
                ],
            ],

            'create' => [
                'notification' => [
                    'title' => 'Jam kerja dibuat',
                    'body'  => 'Jam kerja berhasil dibuat.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Jam kerja dihapus',
                    'body'  => 'Jam kerja berhasil dihapus.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Jam kerja dipulihkan',
                    'body'  => 'Jam kerja berhasil dipulihkan.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Jam kerja dihapus',
                    'body'  => 'Jam kerja terpilih berhasil dihapus.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Jam kerja dipulihkan',
                    'body'  => 'Jam kerja terpilih berhasil dipulihkan.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Jam kerja dihapus permanen',
                    'body'  => 'Jam kerja terpilih berhasil dihapus permanen.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'name'        => 'Nama Presensi',
                    'day-of-week' => 'Hari',
                ],
            ],

            'timing-information' => [
                'title' => 'Informasi Waktu',

                'entries' => [
                    'day-period' => 'Periode Hari',
                    'week-type'  => 'Tipe Minggu',
                    'work-from'  => 'Jam Mulai',
                    'work-to'    => 'Jam Selesai',
                ],
            ],

            'date-information' => [
                'title' => 'Informasi Tanggal',

                'entries' => [
                    'starting-date' => 'Tanggal Mulai',
                    'ending-date'   => 'Tanggal Selesai',
                ],
            ],

            'additional-information' => [
                'title' => 'Informasi Tambahan',

                'entries' => [
                    'durations-days' => 'Durasi (Hari)',
                    'display-type'   => 'Tipe Tampilan',
                ],
            ],
        ],

        'note' => 'Catatan',
    ],
];
