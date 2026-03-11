<?php

return [
    'heading' => 'Chatter',

    'placeholders' => [
        'no-record-found' => 'Tidak ada data ditemukan.',
        'loading'         => 'Memuat Chatter...',
    ],

    'activity-infolist' => [
        'title' => 'Aktivitas',
    ],

    'cancel-activity-plan-action' => [
        'title' => 'Batalkan Aktivitas',
    ],

    'delete-message-action' => [
        'title' => 'Hapus Pesan',
    ],

    'edit-activity' => [
        'title' => 'Edit Aktivitas',

        'form' => [
            'fields' => [
                'activity-plan' => 'Rencana Aktivitas',
                'plan-date'     => 'Tanggal Rencana',
                'plan-summary'  => 'Ringkasan Rencana',
                'activity-type' => 'Jenis Aktivitas',
                'due-date'      => 'Tanggal Jatuh Tempo',
                'summary'       => 'Ringkasan',
                'assigned-to'   => 'Ditugaskan Kepada',
            ],
        ],

        'action' => [
            'notification' => [
                'success' => [
                    'title' => 'Aktivitas diperbarui',
                    'body'  => 'Aktivitas berhasil diperbarui.',
                ],
            ],
        ],
    ],

    'process-message' => [
        'original-note' => '<br><div><span class="font-bold">Catatan Asli</span>: :body</div>',
        'original-note' => '<br><div><span class="font-bold">Catatan Asli</span>: :body</div>',
        'feedback'      => '<div><span class="font-bold">Umpan Balik</span>: <p>:feedback</p></div>',
    ],

    'mark-as-done' => [
        'title' => 'Tandai selesai',
        'form'  => [
            'fields' => [
                'feedback' => 'Umpan Balik',
            ],
        ],

        'footer-actions' => [
            'label' => 'Selesai & Jadwalkan Berikutnya',

            'actions' => [
                'notification' => [
                    'mark-as-done' => [
                        'title' => 'Aktivitas ditandai selesai',
                        'body'  => 'Aktivitas berhasil ditandai selesai.',
                    ],
                ],
            ],
        ],
    ],
];
