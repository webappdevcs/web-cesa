<?php

return [
    'navigation' => [
        'title' => 'Aturan Harga',
    ],

    'singular' => 'Aturan Harga',
    'plural'   => 'Aturan Harga',

    'fields' => [
        'catalog_item'     => 'Layanan / Paket',
        'component'        => 'Komponen',
        'court_type'       => 'Tipe Lapangan',
        'court'            => 'Lapangan',
        'coach'            => 'Coach',
        'time_slot'        => 'Jam',
        'day_type'         => 'Tipe Hari',
        'name'             => 'Nama',
        'calculation_type' => 'Perhitungan',
        'amount'           => 'Harga',
        'percentage'       => 'Persentase',
        'basis'            => 'Dasar Persentase',
        'priority'         => 'Prioritas',
        'starts_at'        => 'Mulai',
        'ends_at'          => 'Berakhir',
        'is_active'        => 'Aktif',
    ],

    'table' => [
        'columns' => [
            'catalog_item'     => 'Layanan / Paket',
            'component'        => 'Komponen',
            'court_type'       => 'Tipe Lapangan',
            'court'            => 'Lapangan',
            'coach'            => 'Coach',
            'time_slot'        => 'Jam',
            'day_type'         => 'Tipe Hari',
            'name'             => 'Nama',
            'calculation_type' => 'Perhitungan',
            'amount'           => 'Harga',
            'percentage'       => 'Persentase',
            'priority'         => 'Prioritas',
            'starts_at'        => 'Mulai',
            'ends_at'          => 'Berakhir',
            'is_active'        => 'Status',
        ],
    ],

    'filters' => [
        'catalog_item' => 'Layanan / Paket',
        'component'    => 'Komponen',
    ],

    'placeholders' => [
        'all_court_types' => 'Semua tipe lapangan',
        'all_courts'      => 'Semua lapangan',
        'all_coaches'     => 'Semua coach',
        'all_slots'       => 'Semua jam',
        'all_days'        => 'Semua hari',
        'total_price'     => 'Total layanan',
    ],

    'calculation_types' => [
        'fixed'      => 'Nominal Tetap',
        'percentage' => 'Persentase',
    ],

    'day_types' => [
        'weekday' => 'Weekday',
        'weekend' => 'Weekend',
    ],

    'status' => [
        'active'   => 'Aktif',
        'inactive' => 'Nonaktif',
    ],

    'validation' => [
        'component_not_allowed'         => 'Komponen ini tidak aktif untuk layanan atau paket yang dipilih.',
        'percentage_required'           => 'Masukkan persentase untuk aturan harga komponen ini.',
        'percentage_requires_component' => 'Harga persentase hanya bisa dipakai untuk komponen lapangan atau coach.',
        'court_type_mismatch'           => 'Lapangan yang dipilih tidak sesuai dengan tipe lapangan.',
    ],
];
