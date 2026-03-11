<?php

return [
    'breadcrumb' => 'Kelola Pengguna',
    'title'      => 'Kelola Pengguna',
    'group'      => 'Umum',

    'navigation' => [
        'label' => 'Kelola Pengguna',
    ],

    'form' => [
        'enable-user-invitation' => [
            'label'       => 'Aktifkan Undangan Pengguna',
            'helper-text' => 'Izinkan pengguna mengundang pengguna lain ke aplikasi.',
        ],

        'enable-reset-password' => [
            'label'       => 'Aktifkan Reset Kata Sandi',
            'helper-text' => 'Izinkan pengguna mengatur ulang kata sandinya.',
        ],

        'default-role' => [
            'label'       => 'Peran Default',
            'helper-text' => 'Peran default yang diberikan kepada pengguna baru.',
        ],

        'default-company' => [
            'label'       => 'Perusahaan Default',
            'helper-text' => 'Perusahaan default yang diberikan kepada pengguna baru.',
        ],
    ],
];
