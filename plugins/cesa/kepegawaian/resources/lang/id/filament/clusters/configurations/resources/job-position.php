<?php

return [
    'title' => 'Posisi Jabatan',

    'navigation' => [
        'title' => 'Posisi Jabatan',
        'group' => 'Rekrutmen',
    ],

    'form' => [
        'sections' => [
            'employment-information' => [
                'title' => 'Informasi Kepegawaian',

                'fields' => [
                    'job-position-title'         => 'Judul Posisi Jabatan',
                    'job-position-title-tooltip' => 'Masukkan judul resmi posisi jabatan',
                    'department'                 => 'Departemen',
                    'department-modal-title'     => 'Buat Departemen',
                    'company-modal-title'        => 'Buat Perusahaan',
                    'job-location'               => 'Lokasi Kerja',
                    'industry'                   => 'Industri',
                    'company'                    => 'Perusahaan',
                    'employment-type'            => 'Tipe Kepegawaian',
                    'recruiter'                  => 'Rekruter',
                    'interviewer'                => 'Pewawancara',
                ],
            ],

            'job-description' => [
                'title' => 'Deskripsi Pekerjaan',

                'fields' => [
                    'job-description'  => 'Deskripsi Pekerjaan',
                    'job-requirements' => 'Persyaratan Pekerjaan',
                ],
            ],

            'workforce-planning' => [
                'title' => 'Perencanaan Tenaga Kerja',

                'fields' => [
                    'recruitment-target'         => 'Target Rekrutmen',
                    'date-from'                  => 'Tanggal Mulai',
                    'date-to'                    => 'Tanggal Selesai',
                    'employment-type'            => 'Tipe Kepegawaian',
                    'status'                     => 'Status',
                ],
            ],

            'position-status' => [
                'title' => 'Status Posisi',

                'fields' => [
                    'status' => 'Status',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'id'                 => 'ID',
            'name'               => 'Posisi Jabatan',
            'department'         => 'Departemen',
            'job-position'       => 'Posisi Jabatan',
            'company'            => 'Perusahaan',
            'expected-employees' => 'Jumlah Karyawan Diharapkan',
            'current-employees'  => 'Jumlah Karyawan Saat Ini',
            'status'             => 'Status',
            'created-by'         => 'Dibuat Oleh',
            'created-at'         => 'Dibuat Pada',
            'updated-at'         => 'Diperbarui Pada',
        ],

        'filters' => [
            'department'      => 'Departemen',
            'employment-type' => 'Tipe Kepegawaian',
            'job-position'    => 'Posisi Jabatan',
            'company'         => 'Perusahaan',
            'status'          => 'Status',
            'created-by'      => 'Dibuat Oleh',
            'updated-at'      => 'Diperbarui Pada',
            'created-at'      => 'Dibuat Pada',
        ],

        'groups' => [
            'job-position'    => 'Posisi Jabatan',
            'company'         => 'Perusahaan',
            'department'      => 'Departemen',
            'employment-type' => 'Tipe Kepegawaian',
            'created-by'      => 'Dibuat Oleh',
            'created-at'      => 'Dibuat Pada',
            'updated-at'      => 'Diperbarui Pada',
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Posisi jabatan dipulihkan',
                    'body'  => 'Posisi jabatan berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Posisi jabatan dihapus',
                    'body'  => 'Posisi jabatan berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Posisi jabatan dipulihkan',
                    'body'  => 'Posisi jabatan berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Posisi jabatan dihapus',
                    'body'  => 'Posisi jabatan berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Posisi jabatan dihapus permanen',
                    'body'  => 'Posisi jabatan berhasil dihapus permanen.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Posisi jabatan dibuat',
                    'body'  => 'Posisi jabatan berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'employment-information' => [
                'title' => 'Informasi Kepegawaian',

                'entries' => [
                    'job-position-title' => 'Judul Posisi Jabatan',
                    'department'         => 'Departemen',
                    'company'            => 'Perusahaan',
                    'employment-type'    => 'Tipe Kepegawaian',
                    'job-location'       => 'Lokasi Kerja',
                    'industry'           => 'Industri',
                ],
            ],
            'job-description' => [
                'title' => 'Deskripsi Pekerjaan',

                'entries' => [
                    'job-description'  => 'Deskripsi Pekerjaan',
                    'job-requirements' => 'Persyaratan Pekerjaan',
                ],
            ],
            'work-planning' => [
                'title' => 'Perencanaan Tenaga Kerja',

                'entries' => [
                    'expected-employees' => 'Jumlah Karyawan Diharapkan',
                    'current-employees'  => 'Jumlah Karyawan Saat Ini',
                    'date-from'          => 'Tanggal Mulai',
                    'date-to'            => 'Tanggal Selesai',
                    'recruitment-target' => 'Target Rekrutmen',
                ],
            ],
            'position-status' => [
                'title' => 'Status Posisi',

                'entries' => [
                    'status' => 'Status',
                ],
            ],
        ],
    ],
];
