<?php

return [
    'title' => 'Departemen',

    'navigation' => [
        'title' => 'Departemen',
        'group' => 'Karyawan',
    ],

    'form' => [
        'sections' => [
            'activity-type-details' => [
                'title' => 'Informasi Umum',

                'fields' => [
                    'name'         => 'Tipe Aktivitas',
                    'name-tooltip' => 'Masukkan nama resmi tipe aktivitas',
                    'action'       => 'Aksi',
                    'default-user' => 'Pengguna Default',
                    'summary'      => 'Ringkasan',
                    'note'         => 'Catatan',
                ],
            ],

            'delay-information' => [
                'title' => 'Informasi Penundaan',

                'fields' => [
                    'delay-count'            => 'Jumlah Penundaan',
                    'delay-unit'             => 'Satuan Penundaan',
                    'delay-form'             => 'Sumber Penundaan',
                    'delay-form-helper-text' => 'Sumber perhitungan penundaan',
                ],
            ],

            'advanced-information' => [
                'title' => 'Informasi Lanjutan',

                'fields' => [
                    'icon'            => 'Ikon',
                    'decoration-type' => 'Tipe Dekorasi',
                    'chaining-type'   => 'Tipe Rantai',
                    'suggest'         => 'Sarankan',
                    'trigger'         => 'Picu',
                ],
            ],

            'status-and-configuration-information' => [
                'title' => 'Status & Konfigurasi',

                'fields' => [
                    'status'               => 'Status',
                    'keep-done-activities' => 'Pertahankan Aktivitas Selesai',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'       => 'Tipe Aktivitas',
            'summary'    => 'Ringkasan',
            'planned-in' => 'Direncanakan Dalam',
            'type'       => 'Tipe',
            'action'     => 'Aksi',
            'status'     => 'Status',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'groups' => [
            'name'             => 'Nama',
            'action-category'  => 'Kategori Aksi',
            'status'           => 'Status',
            'delay-count'      => 'Jumlah Penundaan',
            'delay-unit'       => 'Satuan Penundaan',
            'delay-source'     => 'Sumber Penundaan',
            'associated-model' => 'Model Terkait',
            'chaining-type'    => 'Tipe Rantai',
            'decoration-type'  => 'Tipe Dekorasi',
            'default-user'     => 'Pengguna Default',
            'creation-date'    => 'Tanggal Pembuatan',
            'last-update'      => 'Pembaruan Terakhir',
        ],

        'filters' => [
            'action'    => 'Aksi',
            'status'    => 'Status',
            'has-delay' => 'Memiliki Penundaan',
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Tipe aktivitas dipulihkan',
                    'body'  => 'Tipe aktivitas berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tipe aktivitas dihapus',
                    'body'  => 'Tipe aktivitas berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Tipe aktivitas dihapus permanen',
                        'body'  => 'Tipe aktivitas berhasil dihapus permanen.',
                    ],
                    'error' => [
                        'title' => 'Tipe aktivitas tidak dapat dihapus',
                        'body'  => 'Tipe aktivitas tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Tipe aktivitas dipulihkan',
                    'body'  => 'Tipe aktivitas berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tipe aktivitas dihapus',
                    'body'  => 'Tipe aktivitas berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Tipe aktivitas dihapus permanen',
                    'body'  => 'Tipe aktivitas berhasil dihapus permanen.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'activity-type-details' => [
                'title' => 'Informasi Umum',

                'entries' => [
                    'name'         => 'Tipe Aktivitas',
                    'name-tooltip' => 'Masukkan nama resmi tipe aktivitas',
                    'action'       => 'Aksi',
                    'default-user' => 'Pengguna Default',
                    'plugin'       => 'Plugin',
                    'summary'      => 'Ringkasan',
                    'note'         => 'Catatan',
                ],
            ],

            'delay-information' => [
                'title' => 'Informasi Penundaan',

                'entries' => [
                    'delay-count'            => 'Jumlah Penundaan',
                    'delay-unit'             => 'Satuan Penundaan',
                    'delay-form'             => 'Sumber Penundaan',
                    'delay-form-helper-text' => 'Sumber perhitungan penundaan',
                ],
            ],

            'advanced-information' => [
                'title' => 'Informasi Lanjutan',

                'entries' => [
                    'icon'            => 'Ikon',
                    'decoration-type' => 'Tipe Dekorasi',
                    'chaining-type'   => 'Tipe Rantai',
                    'suggest'         => 'Sarankan',
                    'trigger'         => 'Picu',
                ],
            ],

            'status-and-configuration-information' => [
                'title' => 'Status & Konfigurasi',

                'entries' => [
                    'status'               => 'Status',
                    'keep-done-activities' => 'Pertahankan Aktivitas Selesai',
                ],
            ],
        ],
    ],
];
