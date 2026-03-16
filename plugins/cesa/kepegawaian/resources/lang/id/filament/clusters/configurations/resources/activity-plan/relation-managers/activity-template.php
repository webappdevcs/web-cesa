<?php

return [
    'form' => [
        'sections' => [
            'activity-details' => [
                'title' => 'Detail Aktivitas',

                'fields' => [
                    'activity-type' => 'Jenis Aktivitas',
                    'summary'       => 'Ringkasan',
                    'note'          => 'Catatan',
                ],
            ],

            'assignment' => [
                'title' => 'Penugasan',

                'fields' => [
                    'assignment' => 'Penugasan',
                    'assignee'   => 'Penerima Tugas',
                ],
            ],

            'delay-information' => [
                'title' => 'Informasi Penundaan',

                'fields' => [
                    'delay-count'            => 'Jumlah Penundaan',
                    'delay-unit'             => 'Satuan Penundaan',
                    'delay-from'             => 'Sumber Penundaan',
                    'delay-from-helper-text' => 'Sumber perhitungan penundaan',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'activity-type' => 'Jenis Aktivitas',
            'summary'       => 'Ringkasan',
            'assignment'    => 'Penugasan',
            'assigned-to'   => 'Ditugaskan Kepada',
            'interval'      => 'Interval',
            'delay-unit'    => 'Satuan Penundaan',
            'delay-from'    => 'Sumber Penundaan',
            'created-by'    => 'Dibuat Oleh',
            'created-at'    => 'Dibuat Pada',
            'updated-at'    => 'Diperbarui Pada',
        ],

        'groups' => [
            'activity-type' => 'Jenis Aktivitas',
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
            'activity-type'   => 'Jenis Aktivitas',
            'activity-status' => 'Status Aktivitas',
            'has-delay'       => 'Memiliki Penundaan',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Template aktivitas diperbarui',
                    'body'  => 'Template aktivitas berhasil diperbarui.',
                ],
            ],

            'create' => [
                'notification' => [
                    'title' => 'Template aktivitas dibuat',
                    'body'  => 'Template aktivitas berhasil dibuat.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Template aktivitas dihapus',
                    'body'  => 'Template aktivitas berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Template aktivitas dihapus',
                    'body'  => 'Template aktivitas terpilih berhasil dihapus.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'activity-details' => [
                'title' => 'Detail Aktivitas',

                'entries' => [
                    'activity-type' => 'Jenis Aktivitas',
                    'summary'       => 'Ringkasan',
                ],
            ],

            'assignment' => [
                'title' => 'Penugasan',

                'entries' => [
                    'assignment' => 'Penugasan',
                    'assignee'   => 'Penerima Tugas',
                ],
            ],

            'delay-information' => [
                'title' => 'Informasi Penundaan',

                'entries' => [
                    'delay-count'            => 'Jumlah Penundaan',
                    'delay-unit'             => 'Satuan Penundaan',
                    'delay-from'             => 'Sumber Penundaan',
                    'delay-from-helper-text' => 'Sumber perhitungan penundaan',
                ],
            ],
        ],

        'note' => 'Catatan',
    ],
];
