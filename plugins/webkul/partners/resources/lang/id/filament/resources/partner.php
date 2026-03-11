<?php

return [
    'global-search' => [
        'email' => 'Email',
        'phone' => 'Telepon',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Umum',

                'fields' => [
                    'company'    => 'Perusahaan',
                    'avatar'     => 'Avatar',
                    'tax-id'     => 'NPWP',
                    'job-title'  => 'Jabatan',
                    'phone'      => 'Telepon',
                    'mobile'     => 'Ponsel',
                    'email'      => 'Email',
                    'website'    => 'Situs Web',
                    'title'      => 'Gelar',
                    'name'       => 'Nama',
                    'short-name' => 'Nama Singkat',
                    'tags'       => 'Tag',
                    'color'      => 'Warna',
                ],

                'address' => [
                    'title' => 'Alamat',

                    'fields' => [
                        'street1' => 'Jalan 1',
                        'street2' => 'Jalan 2',
                        'city'    => 'Kota',
                        'zip'     => 'Kode Pos',
                        'state'   => 'Provinsi',
                        'country' => 'Negara',
                        'name'    => 'Nama',
                        'code'    => 'Kode',
                    ],
                ],
            ],
        ],

        'tabs' => [
            'sales-purchase' => [
                'title' => 'Penjualan dan Pembelian',

                'fields' => [
                    'responsible'           => 'Penanggung Jawab',
                    'responsible-hint-text' => 'Ini adalah tenaga penjual internal yang bertanggung jawab atas pelanggan ini',
                    'company-id'            => 'ID Perusahaan',
                    'company-id-hint-text'  => 'Nomor registrasi perusahaan, digunakan jika berbeda dari NPWP. Nilainya harus unik di antara semua partner dalam negara yang sama.',
                    'reference'             => 'Referensi',
                    'industry'              => 'Industri',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'parent' => 'Induk',
        ],

        'groups' => [
            'account-type' => 'Tipe Akun',
            'parent'       => 'Induk',
            'title'        => 'Gelar',
            'job-title'    => 'Jabatan',
            'industry'     => 'Industri',
        ],

        'filters' => [
            'account-type'     => 'Tipe Akun',
            'name'             => 'Nama',
            'email'            => 'Email',
            'parent'           => 'Induk',
            'title'            => 'Gelar',
            'tax-id'           => 'NPWP',
            'phone'            => 'Telepon',
            'mobile'           => 'Ponsel',
            'job-title'        => 'Jabatan',
            'website'          => 'Situs Web',
            'company-registry' => 'Registrasi Perusahaan',
            'responsible'      => 'Penanggung Jawab',
            'reference'        => 'Referensi',
            'parent'           => 'Induk',
            'creator'          => 'Pembuat',
            'company'          => 'Perusahaan',
            'industry'         => 'Industri',
            'industry'         => 'Industri',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Kontak diperbarui',
                    'body'  => 'Kontak berhasil diperbarui.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Kontak dipulihkan',
                    'body'  => 'Kontak berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Kontak dihapus',
                    'body'  => 'Kontak berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Kontak dihapus permanen',
                        'body'  => 'Kontak berhasil dihapus permanen.',
                    ],

                    'error' => [
                        'title' => 'Kontak tidak dapat dihapus',
                        'body'  => 'Kontak tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Kontak dipulihkan',
                    'body'  => 'Kontak berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Kontak dihapus',
                    'body'  => 'Kontak berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Kontak dihapus permanen',
                        'body'  => 'Kontak berhasil dihapus permanen.',
                    ],

                    'error' => [
                        'title' => 'Kontak tidak dapat dihapus',
                        'body'  => 'Kontak tidak dapat dihapus karena sedang digunakan.',
                    ],
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'Umum',

                'fields' => [
                    'company'    => 'Perusahaan',
                    'avatar'     => 'Avatar',
                    'tax-id'     => 'NPWP',
                    'job-title'  => 'Jabatan',
                    'phone'      => 'Telepon',
                    'mobile'     => 'Ponsel',
                    'email'      => 'Email',
                    'website'    => 'Situs Web',
                    'title'      => 'Gelar',
                    'name'       => 'Nama',
                    'short-name' => 'Nama Singkat',
                    'tags'       => 'Tag',
                ],

                'address' => [
                    'title' => 'Alamat',

                    'fields' => [
                        'street1' => 'Jalan 1',
                        'street2' => 'Jalan 2',
                        'city'    => 'Kota',
                        'zip'     => 'Kode Pos',
                        'state'   => 'Provinsi',
                        'country' => 'Negara',
                        'name'    => 'Nama',
                        'code'    => 'Kode',
                    ],
                ],
            ],
        ],

        'tabs' => [
            'sales-purchase' => [
                'title' => 'Penjualan dan Pembelian',

                'fields' => [
                    'responsible'           => 'Penanggung Jawab',
                    'responsible-hint-text' => 'Ini adalah tenaga penjual internal yang bertanggung jawab atas pelanggan ini',
                    'company-id'            => 'ID Perusahaan',
                    'company-id-hint-text'  => 'Nomor registrasi perusahaan. Gunakan jika berbeda dari NPWP. Nilainya harus unik di seluruh partner dalam negara yang sama',
                    'reference'             => 'Referensi',
                    'industry'              => 'Industri',
                ],
            ],
        ],
    ],
];
