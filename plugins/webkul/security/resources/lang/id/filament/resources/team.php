<?php

return [
    'title' => 'Tim',

    'navigation' => [
        'title' => 'Tim',
        'group' => 'Pengaturan',
    ],

    'form' => [
        'fields' => [
            'name' => 'Nama',
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'created-by' => 'Dibuat Oleh',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tim diperbarui',
                    'body'  => 'Tim berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tim dihapus',
                    'body'  => 'Tim berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Tim dibuat',
                    'body'  => 'Tim berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'entries' => [
                'name'         => 'Nama',
                'job-title'    => 'Jabatan',
                'work-email'   => 'Email Kerja',
                'work-mobile'  => 'Ponsel Kerja',
                'work-phone'   => 'Telepon Kerja',
                'manager'      => 'Manajer',
                'department'   => 'Departemen',
                'job-position' => 'Posisi Jabatan',
                'team-tags'    => 'Tag Tim',
                'coach'        => 'Pembimbing',
            ],
        ],
    ],

    'infolist' => [
        'entries' => [
            'name' => 'Nama',
        ],
    ],
];
