<?php

return [
    'navigation' => [
        'title' => 'Rencana Aktivitas',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Informasi Umum',
                'fields' => [
                    'name'       => 'Nama',
                    'status'     => 'Status',
                    'department' => 'Departemen',
                    'company'    => 'Perusahaan',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'status'     => 'Status',
            'department' => 'Departemen',
            'company'    => 'Perusahaan',
            'manager'    => 'Manajer',
            'created-by' => 'Dibuat Oleh',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'filters' => [
            'name'           => 'Nama',
            'plugin'         => 'Plugin',
            'activity-types' => 'Jenis Aktivitas',
            'company'        => 'Perusahaan',
            'department'     => 'Departemen',
            'is-active'      => 'Status',
            'updated-at'     => 'Diperbarui Pada',
            'created-at'     => 'Dibuat Pada',
        ],

        'groups' => [
            'status'     => 'Status',
            'name'       => 'Nama',
            'created-by' => 'Dibuat Oleh',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dipulihkan',
                    'body'  => 'Rencana aktivitas berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dihapus',
                    'body'  => 'Rencana aktivitas berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dihapus permanen',
                    'body'  => 'Rencana aktivitas berhasil dihapus permanen.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dipulihkan',
                    'body'  => 'Rencana aktivitas berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dihapus',
                    'body'  => 'Rencana aktivitas berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dihapus permanen',
                    'body'  => 'Rencana aktivitas berhasil dihapus permanen.',
                ],
            ],
        ],

        'activity-plan' => [
            'create' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dibuat',
                    'body'  => 'Rencana aktivitas berhasil dibuat.',
                ],
            ],
        ],

        'empty-state' => [
            'create' => [
                'notification' => [
                    'title' => 'Rencana aktivitas dibuat',
                    'body'  => 'Rencana aktivitas berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'Informasi Umum',
                'entries' => [
                    'name'       => 'Nama',
                    'status'     => 'Status',
                    'department' => 'Departemen',
                    'manager'    => 'Manajer',
                    'company'    => 'Perusahaan',
                ],
            ],
        ],
    ],
];
