<?php

return [
    'title' => 'Pengguna',

    'navigation' => [
        'title' => 'Pengguna',
        'group' => 'Pengaturan',
    ],

    'global-search' => [
        'email' => 'Email',
    ],

    'languages' => [
        'en' => 'Inggris',
        'id' => 'Bahasa Indonesia',
    ],

    'form' => [
        'sections' => [
            'general-information' => [
                'title'  => 'Informasi Umum',
                'fields' => [
                    'name'                  => 'Nama',
                    'email'                 => 'Email',
                    'password'              => 'Kata Sandi',
                    'password-confirmation' => 'Konfirmasi Kata Sandi',
                ],
            ],

            'permissions' => [
                'title'  => 'Izin Akses',
                'fields' => [
                    'roles'                                    => 'Peran',
                    'permissions'                              => 'Izin',
                    'resource-permission'                      => 'Izin Resource',
                    'resource-permission-self-change-disabled' => 'Anda tidak dapat mengubah izin resource Anda sendiri. Minta administrator lain untuk memperbaruinya.',
                    'teams'                                    => 'Tim',
                ],
            ],

            'avatar' => [
                'title' => 'Avatar',
            ],

            'lang-and-status' => [
                'title'  => 'Bahasa & Status',
                'fields' => [
                    'language' => 'Bahasa Pilihan',
                    'status'   => 'Status',
                ],
            ],

            'multi-company' => [
                'title'             => 'Multi Perusahaan',
                'allowed-companies' => 'Perusahaan yang Diizinkan',
                'default-company'   => 'Perusahaan Default',
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'avatar'              => 'Avatar',
            'name'                => 'Nama',
            'email'               => 'Email',
            'teams'               => 'Tim',
            'role'                => 'Peran',
            'resource-permission' => 'Izin Resource',
            'default-company'     => 'Perusahaan Default',
            'allowed-company'     => 'Perusahaan yang Diizinkan',
            'created-by'          => 'Dibuat Oleh',
            'created-at'          => 'Dibuat Pada',
            'updated-at'          => 'Diperbarui Pada',
        ],

        'filters' => [
            'resource-permission' => 'Izin Resource',
            'teams'               => 'Tim',
            'roles'               => 'Peran',
            'default-company'     => 'Perusahaan Default',
            'allowed-companies'   => 'Perusahaan yang Diizinkan',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Pengguna diubah',
                    'body'  => 'Pengguna berhasil diubah.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Pengguna dihapus',
                    'body'  => 'Pengguna berhasil dihapus.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Pengguna dipulihkan',
                    'body'  => 'Pengguna berhasil dipulihkan.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Pengguna dipulihkan',
                    'body'  => 'Pengguna berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Pengguna dihapus',
                    'body'  => 'Pengguna berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Pengguna dihapus permanen',
                    'body'  => 'Pengguna berhasil dihapus permanen.',
                    'error' => [
                        'title' => 'Pengguna tidak dapat dihapus',
                        'body'  => 'Pengguna tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Pengguna dibuat',
                    'body'  => 'Pengguna berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general-information' => [
                'title'   => 'Informasi Umum',
                'entries' => [
                    'name'                  => 'Nama',
                    'email'                 => 'Email',
                    'password'              => 'Kata Sandi',
                    'password-confirmation' => 'Konfirmasi Kata Sandi',
                ],
            ],

            'permissions' => [
                'title'   => 'Izin Akses',
                'entries' => [
                    'roles'               => 'Peran',
                    'permissions'         => 'Izin',
                    'resource-permission' => 'Izin Resource',
                    'teams'               => 'Tim',
                ],
            ],

            'avatar' => [
                'title' => 'Avatar',
            ],

            'lang-and-status' => [
                'title'   => 'Bahasa & Status',
                'entries' => [
                    'language' => 'Bahasa Pilihan',
                    'status'   => 'Status',
                ],
            ],

            'multi-company' => [
                'title'             => 'Multi Perusahaan',
                'allowed-companies' => 'Perusahaan yang Diizinkan',
                'default-company'   => 'Perusahaan Default',
            ],
        ],
    ],
];
