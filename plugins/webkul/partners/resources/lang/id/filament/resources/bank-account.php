<?php

return [
    'navigation' => [
        'group' => 'Bank',
        'title' => 'Rekening Bank',
    ],

    'form' => [
        'account-number' => 'Nomor Rekening',
        'bank'           => 'Bank',
        'account-holder' => 'Pemilik Rekening',
        'can-send-money' => 'Dapat Mengirim Uang',
    ],

    'table' => [
        'columns' => [
            'account-number' => 'Nomor Rekening',
            'bank'           => 'Bank',
            'account-holder' => 'Pemilik Rekening',
            'send-money'     => 'Dapat Mengirim Uang',
            'created-at'     => 'Dibuat Pada',
            'updated-at'     => 'Diperbarui Pada',
            'deleted-at'     => 'Dihapus Pada',
        ],

        'filters' => [
            'bank'           => 'Bank',
            'account-holder' => 'Pemilik Rekening',
            'creator'        => 'Pembuat',
            'can-send-money' => 'Dapat Mengirim Uang',
        ],

        'groups' => [
            'bank'           => 'Bank',
            'can-send-money' => 'Dapat Mengirim Uang',
            'created-at'     => 'Dibuat Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Rekening bank diperbarui',
                    'body'  => 'Rekening bank berhasil diperbarui.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Rekening bank dipulihkan',
                    'body'  => 'Rekening bank berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus',
                    'body'  => 'Rekening bank berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus permanen',
                    'body'  => 'Rekening bank berhasil dihapus permanen.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Rekening bank dipulihkan',
                    'body'  => 'Rekening bank berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus',
                    'body'  => 'Rekening bank berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Rekening bank dihapus permanen',
                    'body'  => 'Rekening bank berhasil dihapus permanen.',
                ],
            ],
        ],
    ],
];
