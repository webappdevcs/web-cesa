<?php

return [
    'title'  => 'Aktivitas HR',
    'fields' => [
        'workflow'         => 'Alur kerja',
        'template'         => 'Template alur kerja',
        'type'             => 'Jenis',
        'status'           => 'Status',
        'tasks'            => 'Aktivitas',
        'due_at'           => 'Tenggat',
        'reference_number' => 'Nomor referensi',
        'effective_date'   => 'Tanggal efektif',
        'expiry_date'      => 'Tanggal berakhir',
        'notes'            => 'Catatan',
    ],
    'actions'       => ['start' => 'Mulai aktivitas HR'],
    'notifications' => [
        'started' => 'Aktivitas HR untuk karyawan berhasil dimulai',
        'failed'  => 'Aktivitas HR tidak dapat dimulai',
    ],
];
