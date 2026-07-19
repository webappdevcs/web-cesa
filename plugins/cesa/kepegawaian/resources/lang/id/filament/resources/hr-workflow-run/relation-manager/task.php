<?php

return [
    'title'  => 'Daftar aktivitas operasional',
    'fields' => [
        'name'       => 'Aktivitas',
        'department' => 'Departemen',
        'assignee'   => 'PIC',
        'due_at'     => 'Tenggat',
        'evidence'   => 'Bukti',
        'status'     => 'Status',
        'note'       => 'Catatan penyelesaian',
        'reason'     => 'Alasan',
    ],
    'actions' => [
        'assign'   => 'Tetapkan PIC',
        'begin'    => 'Mulai kerja',
        'complete' => 'Selesaikan',
        'skip'     => 'Lewati',
    ],
    'notifications' => [
        'assigned'  => 'PIC berhasil ditetapkan',
        'started'   => 'Aktivitas mulai dikerjakan',
        'completed' => 'Aktivitas selesai',
        'skipped'   => 'Aktivitas dilewati',
    ],
];
