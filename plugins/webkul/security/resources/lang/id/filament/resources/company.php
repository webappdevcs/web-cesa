<?php

return [
    'title' => 'Perusahaan',

    'navigation' => [
        'title' => 'Perusahaan',
        'group' => 'Pengaturan',
    ],

    'global-search' => [
        'email' => 'Email',
    ],

    'form' => [
        'sections' => [
            'company-information' => [
                'title'  => 'Informasi Perusahaan',
                'fields' => [
                    'name'                => 'Nama Perusahaan',
                    'registration-number' => 'Nomor Registrasi',
                    'company-id'          => 'ID Perusahaan',
                    'tax-id'              => 'NPWP',
                    'tax-id-tooltip'      => 'NPWP adalah pengenal unik untuk perusahaan Anda.',
                    'website'             => 'Situs Web',
                ],
            ],

            'address-information' => [
                'title'  => 'Informasi Alamat',

                'fields' => [
                    'street1'        => 'Jalan 1',
                    'street2'        => 'Jalan 2',
                    'city'           => 'Kota',
                    'zipcode'        => 'Kode Pos',
                    'country'        => 'Negara',
                    'currency-name'  => 'Nama Mata Uang',
                    'phone-code'     => 'Kode Telepon',
                    'code'           => 'Kode',
                    'country-name'   => 'Nama Negara',
                    'state-required' => 'Provinsi Wajib',
                    'zip-required'   => 'Kode Pos Wajib',
                    'create-country' => 'Buat Negara',
                    'state'          => 'Provinsi',
                    'state-name'     => 'Nama Provinsi',
                    'state-code'     => 'Kode Provinsi',
                    'create-state'   => 'Buat Provinsi',
                ],
            ],

            'additional-information' => [
                'title' => 'Informasi Tambahan',

                'fields' => [
                    'default-currency'        => 'Mata Uang Default',
                    'currency-name'           => 'Nama Mata Uang',
                    'currency-full-name'      => 'Nama Lengkap Mata Uang',
                    'currency-symbol'         => 'Simbol Mata Uang',
                    'currency-iso-numeric'    => 'ISO Numerik Mata Uang',
                    'currency-decimal-places' => 'Jumlah Desimal Mata Uang',
                    'currency-rounding'       => 'Pembulatan Mata Uang',
                    'currency-status'         => 'Status Mata Uang',
                    'company-foundation-date' => 'Tanggal Berdiri Perusahaan',
                    'currency-create'         => 'Buat Mata Uang',
                    'status'                  => 'Status',
                ],
            ],

            'branding' => [
                'title'  => 'Branding',
                'fields' => [
                    'company-logo' => 'Logo Perusahaan',
                    'color'        => 'Warna',
                ],
            ],

            'contact-information' => [
                'title'  => 'Informasi Kontak',
                'fields' => [
                    'email'  => 'Alamat Email',
                    'phone'  => 'Nomor Telepon',
                    'mobile' => 'Nomor Telepon',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'logo'         => 'Logo',
            'company-name' => 'Nama Perusahaan',
            'branches'     => 'Cabang',
            'email'        => 'Email',
            'city'         => 'Kota',
            'country'      => 'Negara',
            'currency'     => 'Mata Uang',
            'created-by'   => 'Dibuat Oleh',
            'status'       => 'Status',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],

        'groups' => [
            'company-name' => 'Nama Perusahaan',
            'city'         => 'Kota',
            'country'      => 'Negara',
            'state'        => 'Provinsi',
            'email'        => 'Email',
            'phone'        => 'Telepon',
            'currency'     => 'Mata Uang',
            'created-by'   => 'Dibuat Oleh',
            'created-at'   => 'Dibuat Pada',
            'updated-at'   => 'Diperbarui Pada',
        ],

        'filters' => [
            'status'  => 'Status',
            'country' => 'Negara',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Perusahaan diubah',
                    'body'  => 'Perusahaan berhasil diubah.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Perusahaan dihapus',
                    'body'  => 'Perusahaan berhasil dihapus.',

                    'default-company' => [
                        'title' => 'Perusahaan tidak dapat dihapus',
                        'body'  => 'Perusahaan ini ditetapkan sebagai perusahaan default di pengaturan Kelola Pengguna. Ubah perusahaan default terlebih dahulu sebelum menghapus.',
                    ],
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Perusahaan dipulihkan',
                    'body'  => 'Perusahaan berhasil dipulihkan.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Perusahaan dihapus permanen',
                        'body'  => 'Perusahaan berhasil dihapus permanen.',
                    ],
                    'error' => [
                        'title' => 'Tidak dapat menghapus permanen perusahaan',
                        'body'  => 'Perusahaan ini terkait dengan data yang sudah ada dan tidak dapat dihapus.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Perusahaan dipulihkan',
                    'body'  => 'Perusahaan berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Perusahaan dihapus',
                    'body'  => 'Perusahaan berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Perusahaan dihapus permanen',
                    'body'  => 'Perusahaan berhasil dihapus permanen.',
                    'error' => [
                        'title' => 'Tidak dapat menghapus permanen perusahaan',
                        'body'  => 'Satu atau lebih perusahaan terkait dengan data yang sudah ada dan tidak dapat dihapus.',
                    ],
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Perusahaan dibuat',
                    'body'  => 'Perusahaan berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'company-information' => [
                'title'   => 'Informasi Perusahaan',
                'entries' => [
                    'name'                => 'Nama Perusahaan',
                    'registration-number' => 'Nomor Registrasi',
                    'company-id'          => 'ID Perusahaan',
                    'tax-id'              => 'NPWP',
                    'tax-id-tooltip'      => 'NPWP adalah pengenal unik untuk perusahaan Anda.',
                    'website'             => 'Situs Web',
                ],
            ],

            'address-information' => [
                'title'  => 'Informasi Alamat',

                'entries' => [
                    'street1'        => 'Jalan 1',
                    'street2'        => 'Jalan 2',
                    'city'           => 'Kota',
                    'zipcode'        => 'Kode Pos',
                    'country'        => 'Negara',
                    'currency-name'  => 'Nama Mata Uang',
                    'phone-code'     => 'Kode Telepon',
                    'code'           => 'Kode',
                    'country-name'   => 'Nama Negara',
                    'state-required' => 'Provinsi Wajib',
                    'zip-required'   => 'Kode Pos Wajib',
                    'create-country' => 'Buat Negara',
                    'state'          => 'Provinsi',
                    'state-name'     => 'Nama Provinsi',
                    'state-code'     => 'Kode Provinsi',
                    'create-state'   => 'Buat Provinsi',
                ],
            ],

            'additional-information' => [
                'title' => 'Informasi Tambahan',

                'entries' => [
                    'default-currency'        => 'Mata Uang Default',
                    'currency-name'           => 'Nama Mata Uang',
                    'currency-full-name'      => 'Nama Lengkap Mata Uang',
                    'currency-symbol'         => 'Simbol Mata Uang',
                    'currency-iso-numeric'    => 'ISO Numerik Mata Uang',
                    'currency-decimal-places' => 'Jumlah Desimal Mata Uang',
                    'currency-rounding'       => 'Pembulatan Mata Uang',
                    'currency-status'         => 'Status Mata Uang',
                    'company-foundation-date' => 'Tanggal Berdiri Perusahaan',
                    'currency-create'         => 'Buat Mata Uang',
                    'status'                  => 'Status',
                ],
            ],

            'branding' => [
                'title'   => 'Branding',
                'entries' => [
                    'company-logo' => 'Logo Perusahaan',
                    'color'        => 'Warna',
                ],
            ],

            'contact-information' => [
                'title'   => 'Informasi Kontak',
                'entries' => [
                    'email'  => 'Alamat Email',
                    'phone'  => 'Nomor Telepon',
                    'mobile' => 'Nomor Telepon',
                ],
            ],
        ],
    ],
];
