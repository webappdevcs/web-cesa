<?php

return [
    'title' => 'Tipe Kepegawaian',

    'navigation' => [
        'title' => 'Tipe Kepegawaian',
        'group' => 'Rekrutmen',
    ],

    'form' => [
        'fields' => [
            'name'    => 'Tipe Kepegawaian',
            'code'    => 'Kode',
            'country' => 'Negara',
        ],
    ],

    'table' => [
        'columns' => [
            'id'         => 'ID',
            'name'       => 'Tipe Kepegawaian',
            'code'       => 'Kode',
            'country'    => 'Negara',
            'created-by' => 'Dibuat Oleh',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'filters' => [
            'name'       => 'Tipe Kepegawaian',
            'country'    => 'Negara',
            'created-by' => 'Dibuat Oleh',
            'updated-at' => 'Diperbarui Pada',
            'created-at' => 'Dibuat Pada',
        ],

        'groups' => [
            'name'       => 'Tipe Kepegawaian',
            'country'    => 'Negara',
            'code'       => 'Kode',
            'created-by' => 'Dibuat Oleh',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tipe kepegawaian diperbarui',
                    'body'  => 'Tipe kepegawaian berhasil diperbarui.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tipe kepegawaian dihapus',
                    'body'  => 'Tipe kepegawaian berhasil dihapus.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Tipe kepegawaian dihapus',
                    'body'  => 'Tipe kepegawaian terpilih berhasil dihapus.',
                ],
            ],
        ],

        'empty-state-actions' => [
            'create' => [
                'notification' => [
                    'title' => 'Tipe kepegawaian dibuat',
                    'body'  => 'Tipe kepegawaian berhasil dibuat.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'entries' => [
            'name'    => 'Tipe Kepegawaian',
            'code'    => 'Kode',
            'country' => 'Negara',
        ],
    ],
];
