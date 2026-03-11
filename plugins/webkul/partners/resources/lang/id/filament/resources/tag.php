<?php

return [
    'form' => [
        'name'  => 'Nama',
        'color' => 'Warna',
    ],

    'table' => [
        'columns' => [
            'name'       => 'Nama',
            'color'      => 'Warna',
            'created-at' => 'Dibuat Pada',
            'updated-at' => 'Diperbarui Pada',
        ],

        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Tag diperbarui',
                    'body'  => 'Tag berhasil diperbarui.',
                ],
            ],

            'restore' => [
                'notification' => [
                    'title' => 'Tag dipulihkan',
                    'body'  => 'Tag berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tag dihapus',
                    'body'  => 'Tag berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Tag dihapus permanen',
                    'body'  => 'Tag berhasil dihapus permanen.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Tag dipulihkan',
                    'body'  => 'Tag berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Tag dihapus',
                    'body'  => 'Tag berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Tag dihapus permanen',
                    'body'  => 'Tag berhasil dihapus permanen.',
                ],
            ],
        ],
    ],
];
