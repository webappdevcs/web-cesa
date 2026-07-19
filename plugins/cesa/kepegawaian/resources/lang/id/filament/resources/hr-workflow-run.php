<?php

return [
    'title'        => 'alur kerja karyawan',
    'plural_title' => 'alur kerja karyawan',
    'navigation'   => 'Aktivitas HR karyawan',
    'sections'     => ['summary' => 'Ringkasan alur kerja'],
    'fields'       => [
        'employee'            => 'Karyawan',
        'workflow'            => 'Alur kerja',
        'type'                => 'Jenis',
        'progress'            => 'Progres',
        'status'              => 'Status',
        'due_at'              => 'Tenggat',
        'started_at'          => 'Dimulai',
        'started_by'          => 'Dimulai oleh',
        'completed_at'        => 'Selesai',
        'cancellation_reason' => 'Alasan pembatalan',
        'reference_number'    => 'Nomor referensi',
        'effective_date'      => 'Tanggal efektif',
        'expiry_date'         => 'Tanggal berakhir',
        'notes'               => 'Catatan',
    ],
    'statuses' => [
        'in_progress' => 'Sedang berjalan',
        'completed'   => 'Selesai',
        'cancelled'   => 'Dibatalkan',
    ],
    'actions'       => ['cancel' => 'Batalkan alur kerja'],
    'notifications' => ['cancelled' => 'Alur kerja dibatalkan'],
];
