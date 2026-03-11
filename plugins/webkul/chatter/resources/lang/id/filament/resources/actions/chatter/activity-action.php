<?php

return [
    'setup' => [
        'title'               => 'Jadwalkan Aktivitas',
        'submit-action-title' => 'Jadwalkan',

        'form' => [
            'fields' => [
                'activity-plan' => 'Rencana Aktivitas',
                'plan-date'     => 'Tanggal Rencana',
                'plan-summary'  => 'Ringkasan Rencana',
                'activity-type' => 'Jenis Aktivitas',
                'due-date'      => 'Tanggal Jatuh Tempo',
                'summary'       => 'Ringkasan',
                'assigned-to'   => 'Ditugaskan Kepada',
                'log-note'      => 'Catatan Log',
            ],
        ],

        'actions' => [
            'notification' => [
                'success' => [
                    'title' => 'Aktivitas Dibuat',
                    'body'  => 'Aktivitas berhasil dibuat.',
                ],

                'warning' => [
                    'title' => 'Tidak ada file baru',
                    'body'  => 'Semua file sudah diunggah sebelumnya.',
                ],

                'error' => [
                    'title' => 'Gagal membuat aktivitas',
                    'body'  => 'Gagal membuat aktivitas ',
                ],
            ],
        ],
    ],
];
