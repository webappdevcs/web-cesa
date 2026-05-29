<?php

return [
    'navigation' => [
        'title' => 'Reservasi',
        'group' => 'Padelnis',
    ],

    'singular' => 'Reservasi',
    'plural'   => 'Reservasi',

    'fields' => [
        'id_reff'          => 'ID Reff',

        'transaction_type' => 'Jenis Transaksi',
        'catalog_item'     => 'Layanan / Paket',
        'customer_name'    => 'Nama Customer',
        'reservation_date' => 'Tanggal Reservasi',
        'court'            => 'Lapangan',
        'coach'            => 'Coach',
        'reservation_time' => 'Jam',
        'blocked_slots'    => 'Detail Blok',
        'expected_amount'  => 'Total Pembayaran',
        'quoted_amount'    => 'Harga Quote',
        'price_breakdown'  => 'Breakdown Harga',
        'transfer_amount'  => 'Nominal Transfer',
        'transfer_date'    => 'Tanggal Transfer',
        'notes'            => 'Keterangan',
        'created_at'       => 'Dibuat Pada',
    ],

    'form' => [
        'sections' => [
            'reservation' => [
                'title' => 'Detail Reservasi',
            ],
            'payment' => [
                'title' => 'Pembayaran & Status',
            ],
            'metadata' => [
                'title' => 'Metadata',
            ],
        ],

        'placeholders' => [
            'customer_name'    => 'Masukkan nama customer',
            'catalog_item'     => 'Pilih layanan atau paket',
            'reservation_date' => 'Pilih tanggal reservasi',
            'court'            => 'Pilih lapangan',
            'coach'            => 'Pilih coach',
            'reservation_time' => 'Pilih jam mulai - jam berakhir',
            'expected_amount'  => 'Terisi otomatis dari master harga',
            'transfer_amount'  => 'Masukkan nominal transfer',
            'transfer_date'    => 'Pilih tanggal transfer',
            'notes'            => 'Tambahkan keterangan jika diperlukan',
        ],
    ],

    'table' => [
        'columns' => [
            'id_reff'          => 'ID Reff',

            'transaction_type' => 'Jenis Transaksi',
            'catalog_item'     => 'Layanan / Paket',
            'customer_name'    => 'Nama Customer',
            'reservation_date' => 'Tanggal',
            'reservation_time' => 'Jam',
            'blocked_slots'    => 'Detail Blok',
            'court'            => 'Lapangan',
            'coach'            => 'Coach',
            'expected_amount'  => 'Total Pembayaran',
            'price_breakdown'  => 'Breakdown Harga',
            'transfer_amount'  => 'Nominal Transfer',
            'transfer_date'    => 'Tanggal Transfer',
            'notes'            => 'Keterangan',
            'created_at'       => 'Dibuat Pada',
        ],
    ],

    'filters' => [
        'reservation_from'        => 'Tanggal Dari',
        'reservation_until'       => 'Tanggal Sampai',
        'reservation_time'        => 'Jam',
        'reservation_range'       => 'Reservasi: :from - :until',
        'reservation_from_value'  => 'Reservasi dari: :date',
        'reservation_until_value' => 'Reservasi sampai: :date',
        'court'                   => 'Lapangan',

        'transaction_type'        => 'Jenis Transaksi',
        'catalog_item'            => 'Layanan / Paket',
        'coach'                   => 'Coach',
    ],

    'actions' => [
        'copy_id_reff' => 'ID Reff berhasil disalin.',
    ],

    'price_breakdown' => [
        'commissionable' => '(komisi)',
        'missing_rule'   => 'aturan harga belum dikonfigurasi',
    ],

    'validation' => [
        'active_slot_unique'              => 'Slot ini sudah dipesan untuk lapangan dan tanggal tersebut.',
        'court_required'                  => 'Lapangan wajib dipilih untuk layanan atau paket ini.',
        'coach_required'                  => 'Coach wajib dipilih untuk layanan atau paket ini.',
        'reservation_time_required'       => 'Jam wajib dipilih untuk layanan atau paket ini.',
        'reservation_time_invalid'        => 'Pilih jam reservasi yang valid untuk layanan atau paket ini.',
        'catalog_item_required'           => 'Layanan atau paket wajib dipilih untuk transaksi ini.',
        'catalog_item_transaction_type'   => 'Layanan atau paket tidak sesuai dengan jenis transaksi.',
        'pricing_rules_missing'           => 'Aturan harga belum lengkap untuk komponen: :components.',
    ],

    'transaction_types' => [
        'regular'  => 'Reguler',
        'coaching' => 'Coaching',
        'academy'  => 'Academy',
    ],

    'exports' => [
        'notifications' => [
            'completed_body' => 'Ekspor reservasi selesai dengan :success baris berhasil diekspor dan :failed baris gagal diekspor.',
        ],
    ],

    'pages' => [
        'list' => [
            'header_actions' => [
                'create' => [
                    'label' => 'Buat Reservasi',
                ],
                'export' => [
                    'label' => 'Ekspor Reservasi',
                ],
            ],
        ],
    ],
];
