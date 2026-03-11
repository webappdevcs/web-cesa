<?php

return [
    'tooltip' => 'Filter',

    'fields' => [
        'search'             => 'Cari',
        'search-placeholder' => 'Cari pesan...',
        'type'               => 'Tipe',
        'date'               => 'Tanggal',
        'sort-by'            => 'Urutkan berdasarkan',
        'pinned-only'        => 'Hanya yang disematkan',
    ],
    'type-options' => [
        'all'          => 'Semua tipe',
        'note'         => 'Catatan',
        'comment'      => 'Komentar',
        'notification' => 'Notifikasi',
        'activity'     => 'Aktivitas',
    ],
    'date-options' => [
        ''          => 'Kapan saja',
        'today'     => 'Hari ini',
        'yesterday' => 'Kemarin',
        'week'      => '7 hari terakhir',
        'month'     => '30 hari terakhir',
        'quarter'   => '3 bulan terakhir',
        'year'      => 'Tahun lalu',
    ],
    'sort-options' => [
        'created_at_desc' => 'Terbaru dulu',
        'created_at_asc'  => 'Terlama dulu',
        'updated_at_desc' => 'Baru diperbarui',
        'priority'        => 'Prioritas',
    ],
    'actions' => [
        'apply' => 'Terapkan filter',
    ],
];
