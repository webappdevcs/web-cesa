<?php

return [
    'title'        => 'Audit sinkronisasi karyawan',
    'plural_title' => 'Audit sinkronisasi karyawan',
    'navigation'   => 'Audit sinkronisasi data',
    'sections'     => [
        'source' => 'Sumber dan eksekusi',
        'counts' => 'Ringkasan rekonsiliasi',
    ],
    'fields' => [
        'uuid'               => 'UUID proses',
        'source_system'      => 'Sistem sumber',
        'source_instance'    => 'Instans sumber',
        'mode'               => 'Mode',
        'status'             => 'Status',
        'file_name'          => 'Nama file sumber',
        'file_checksum'      => 'Checksum SHA-256',
        'initiator'          => 'Dijalankan oleh',
        'started_at'         => 'Mulai pada',
        'completed_at'       => 'Selesai pada',
        'total_records'      => 'Total record',
        'matched_count'      => 'Kecocokan pasti',
        'linked_count'       => 'Terhubung',
        'created_count'      => 'Dibuat nonaktif',
        'would_link_count'   => 'Kandidat penghubungan',
        'would_create_count' => 'Kandidat pembuatan',
        'conflict_count'     => 'Konflik',
        'invalid_count'      => 'Record tidak valid',
    ],
    'values' => [
        'dry_run'   => 'Dry run',
        'commit'    => 'Commit',
        'running'   => 'Berjalan',
        'completed' => 'Selesai',
        'failed'    => 'Gagal',
    ],
];
