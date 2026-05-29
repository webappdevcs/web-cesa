<?php

return [
    'navigation' => [
        'title' => 'Layanan & Harga Dasar',
    ],

    'singular' => 'Layanan & Harga Dasar',
    'plural'   => 'Layanan & Harga Dasar',

    'fields' => [
        'name'                   => 'Nama',
        'description'            => 'Deskripsi',
        'transaction_type'       => 'Jenis Transaksi',
        'requires_court'         => 'Butuh Lapangan',
        'requires_coach'         => 'Butuh Coach',
        'pricing_mode'           => 'Mode Harga',
        'time_slot'              => 'Jam',
        'price_amount'           => 'Harga Dasar',
        'uses_component_pricing' => 'Pakai Komponen Harga',
        'price_components'       => 'Komponen Harga',
        'duration_hours'         => 'Durasi Jam',
        'session_count'          => 'Jumlah Sesi',
        'allow_public_booking'   => 'Bisa Booking Publik',
        'is_active'              => 'Aktif',
        'sort'                   => 'Urutan',
    ],

    'table' => [
        'columns' => [
            'name'                 => 'Nama',
            'transaction_type'     => 'Jenis Transaksi',
            'requires_court'       => 'Butuh Lapangan',
            'requires_coach'       => 'Butuh Coach',
            'pricing_mode'         => 'Mode Harga',
            'time_slot'            => 'Jam',
            'price_amount'         => 'Harga Dasar',
            'price_components'     => 'Komponen Harga',
            'duration_hours'       => 'Durasi Jam',
            'session_count'        => 'Jumlah Sesi',
            'allow_public_booking' => 'Booking Publik',
            'is_active'            => 'Status',
        ],
    ],

    'filters' => [
        'transaction_type' => 'Jenis Transaksi',
    ],

    'placeholders' => [
        'all_slots' => 'Semua jam',
    ],

    'helpers' => [
        'uses_component_pricing' => 'Pecah layanan ini menjadi komponen harga lapangan dan coach. Matikan jika layanan cukup memakai harga dasar.',
    ],

    'status' => [
        'active'   => 'Aktif',
        'inactive' => 'Nonaktif',
        'yes'      => 'Ya',
        'no'       => 'Tidak',
    ],

    'pricing_modes' => [
        'per_slot' => 'Per Slot Jam',
        'fixed'    => 'Harga Paket Tetap',
    ],

    'price_components' => [
        'fields' => [
            'component'           => 'Komponen',
            'calculation_type'    => 'Perhitungan',
            'amount'              => 'Nominal',
            'percentage'          => 'Persentase',
            'basis'               => 'Dasar Persentase',
            'source_catalog_item' => 'Sumber Layanan',
            'is_commissionable'   => 'Masuk Komisi',
        ],
        'components' => [
            'court' => 'Lapangan',
            'coach' => 'Coach',
        ],
        'calculation_types' => [
            'fixed'        => 'Nominal Tetap',
            'percentage'   => 'Persentase',
            'catalog_item' => 'Pakai Harga Layanan',
        ],
        'bases' => [
            'quoted_amount'   => 'Harga Quote',
            'transfer_amount' => 'Nominal Transfer',
        ],
        'placeholders' => [
            'current_catalog_item' => 'Layanan ini',
        ],
        'actions' => [
            'add' => 'Tambah komponen',
        ],
        'summary' => [
            'fixed'           => 'tetap Rp:amount',
            'percentage'      => ':percentage%',
            'catalog_item'    => 'harga layanan',
            'priced_by_rules' => 'diatur di Aturan Harga',
        ],
    ],
];
