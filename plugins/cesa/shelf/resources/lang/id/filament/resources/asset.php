<?php

return [
    'title' => 'Aset',

    'navigation' => [
        'title' => 'Aset',
        'group' => 'Shelf',
    ],

    'singular' => 'Aset',
    'plural'   => 'Aset',

    'fields' => [
        'category'                       => 'Kategori',
        'brand'                          => 'Merek',
        'name'                           => 'Nama',
        'attribute'                      => 'Atribut',
        'attribute_value'                => 'Nilai Atribut',
        'recipient_business_entity'      => 'Badan Usaha Penerima',
        'recipient'                      => 'Penerima',
        'purchase_date'                  => 'Tanggal Pembelian',
        'business_entity'                => 'Badan Usaha',
        'item_price'                     => 'Harga Barang',
        'qty'                            => 'Kuantitas',
        'asset_location'                 => 'Lokasi Aset',
        'image'                          => 'Gambar Aset',
    ],

    'labels' => [
        'category'          => 'Kategori',
        'brand'             => 'Merek',
        'type'              => 'Tipe',
        'asset_image'       => 'Gambar Aset',
        'purchase_date'     => 'Tanggal Pembelian',
        'item_price'        => 'Harga',
        'qty'               => 'Kuantitas',
        'business_entity'   => 'Badan Usaha',
        'asset_location'    => 'Lokasi Aset',
    ],

    'lifecycle' => [
        'guide_title'           => 'Panduan',
        'guide_content'         => 'Kelola kondisi fisik aset serta dokumen NBH bila terjadi kehilangan atau kerusakan.',
        'condition_status'      => 'Status Kondisi',
        'condition_helper'      => 'Ubah ke "Hilang" atau "Rusak" ketika ditemukan insiden.',
        'nbh_status'            => 'Status NBH',
        'nbh_helper'            => 'Perbarui saat proses penggantian selesai.',
        'incident_date'         => 'Tanggal Insiden',
        'incident_helper'       => 'Tanggal ditemukannya aset hilang atau rusak.',
        'responsible_person'    => 'Penanggung Jawab',
        'responsible_helper'    => 'Pihak yang bertanggung jawab atas NBH.',
        'audit_document'        => 'Dokumen Audit',
        'audit_helper'          => 'Unggah berita acara atau bukti audit (PDF/JPG, maks 4 MB). Wajib saat NBH selesai.',
        'nbh_document'          => 'Nota Barang Hilang (NBH)',
        'nbh_helper_text'       => 'Unggah bukti penggantian atau nota NBH selesai.',
        'nbh_notes'             => 'Catatan NBH',
        'nbh_notes_placeholder' => 'Masukkan kronologi singkat, hasil audit, atau tindak lanjut.',
    ],

    'recipient' => [
        'guide_title'               => 'Pengaturan Penerima',
        'guide_content'             => 'Opsional: sesuaikan penerima aset secara manual untuk kasus khusus.',
        'recipient_helper'          => 'Kosongkan jika tetap mengikuti data transfer terakhir.',
        'recipient_select_helper'   => 'Pilih pemegang aset saat ini.',
    ],

    'filters' => [
        'label'                       => 'Filter',
        'serial_number'               => 'Nomor Seri',
        'serial_number_placeholder'   => 'Cari nomor seri...',
        'imei'                        => 'IMEI',
        'imei_placeholder'            => 'Cari IMEI 1 / IMEI 2...',
        'min_price'                   => 'Harga Minimum',
        'max_price'                   => 'Harga Maksimum',
        'filter_audit'                => 'Filter',
        'category'                    => 'Kategori',
        'updated-at'                  => 'Diperbarui Pada',
        'created-at'                  => 'Dibuat Pada',
    ],

    'actions' => [
        'move_to_attributes' => 'Sinkronkan Atribut Aset',
    ],

    'info_section'        => 'Informasi Aset',
    'attributes_section'  => 'Atribut Khusus',
    'purchase_section'    => 'Detail Pembelian',
    'status_section'      => 'Status & NBH',
    'documents_section'   => 'Dokumen Pendukung',

    'validation_status'   => 'Status Validasi',
    'valid'               => 'Valid',
    'invalid'             => 'Tidak Valid',
    'asset_holder'        => 'Pemegang Aset',

    'table' => [
        'columns' => [
            'purchase_date'      => 'Tanggal Pembelian',
            'business_entity'    => 'Badan Usaha',
            'name'               => 'Nama',
            'category'           => 'Kategori',
            'brand'              => 'Merek',
            'type'               => 'Tipe',
            'serial_number'      => 'Serial Number',
            'imei1'              => 'IMEI 1',
            'imei2'              => 'IMEI 2',
            'item_price'         => 'Harga Barang',
            'item_age'           => 'Usia Barang',
            'qty'                => 'Kuantitas',
            'asset_location'     => 'Lokasi Aset',
            'condition_status'   => 'Status Kondisi',
            'nbh_status'         => 'Status NBH',
            'created-at'         => 'Dibuat Pada',
            'updated-at'         => 'Diperbarui Pada',
        ],

        'groups' => [
            'category'         => 'Kategori',
            'brand'            => 'Merek',
            'business_entity'  => 'Badan Usaha',
            'asset_location'   => 'Lokasi Aset',
            'condition_status' => 'Status Kondisi',
            'nbh_status'       => 'Status NBH',
            'updated-at'       => 'Diperbarui Pada',
            'created-at'       => 'Dibuat Pada',
        ],

        'filters' => [
            'label'                       => 'Filter',
            'serial_number'               => 'Nomor Seri',
            'serial_number_placeholder'   => 'Cari nomor seri...',
            'imei'                        => 'IMEI',
            'imei_placeholder'            => 'Cari IMEI 1 / IMEI 2...',
            'min_price'                   => 'Harga Minimum',
            'max_price'                   => 'Harga Maksimum',
            'filter_audit'                => 'Filter',
            'category'                    => 'Kategori',
            'updated-at'                  => 'Diperbarui Pada',
            'created-at'                  => 'Dibuat Pada',
        ],

        'actions' => [
            'move_to_attributes' => 'Sinkronkan Atribut Aset',

            'edit' => [
                'notification' => [
                    'title' => 'Aset diperbarui',
                    'body'  => 'Aset berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Aset dihapus',
                    'body'  => 'Aset berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Aset dihapus',
                    'body'  => 'Aset terpilih berhasil dihapus.',
                ],
            ],

            'move_to_attributes' => [
                'notification' => [
                    'title' => 'Atribut aset disinkronkan',
                    'body'  => 'Atribut aset berhasil disinkronkan untuk aset yang dipilih.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Aset dibuat',
                    'body'  => 'Aset berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'info' => [
                'title' => 'Informasi Aset',

                'entries' => [
                    'name'           => 'Nama Aset',
                    'category'       => 'Kategori',
                    'brand'          => 'Merek',
                    'type'           => 'Tipe',
                    'image'          => 'Gambar Aset',
                ],
            ],

            'attributes' => [
                'title' => 'Atribut Khusus',
            ],

            'purchase' => [
                'title' => 'Detail Pembelian',

                'entries' => [
                    'purchase_date'   => 'Tanggal Pembelian',
                    'item_price'      => 'Harga',
                    'qty'             => 'Kuantitas',
                    'business_entity' => 'Badan Usaha',
                ],
            ],

            'status' => [
                'title' => 'Status & NBH',

                'entries' => [
                    'condition_status'    => 'Status Kondisi',
                    'nbh_status'          => 'Status NBH',
                    'validation_status'   => 'Status Validasi',
                    'valid'               => 'Valid',
                    'invalid'             => 'Tidak Valid',
                    'asset_location'      => 'Lokasi Aset',
                    'asset_holder'        => 'Pemegang Aset',
                    'incident_date'       => 'Tanggal Insiden',
                    'responsible_person'  => 'Penanggung Jawab',
                    'nbh_notes'           => 'Catatan NBH',
                ],
            ],

            'documents' => [
                'title' => 'Dokumen Pendukung',

                'entries' => [
                    'audit_document' => 'Dokumen Audit',
                    'nbh_document'   => 'Nota Barang Hilang (NBH)',
                ],
            ],
        ],
    ],

    'notifications' => [
        'success'           => 'Sukses',
        'attributes_moved'  => 'Atribut aset berhasil disinkronkan untuk aset yang dipilih.',
    ],
];
