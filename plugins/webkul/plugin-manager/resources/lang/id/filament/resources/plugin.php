<?php

return [

    'navigation' => [
        'group' => 'Plugin',
    ],

    'title' => 'Plugin',

    'table' => [
        'version'             => 'Versi',
        'dependencies'        => 'Dependensi',
        'dependencies_suffix' => ' Dependensi',
    ],

    'status' => [
        'installed'     => 'Terpasang',
        'not_installed' => 'Belum Terpasang',
    ],

    'filters' => [
        'installation_status' => 'Status Instalasi',
        'all_plugins'         => 'Semua Plugin',
        'installed'           => 'Terpasang',
        'not_installed'       => 'Belum Terpasang',
        'active_status'       => 'Status Aktif',
        'author'              => 'Penulis',
        'webkul'              => 'Webkul',
        'third_party'         => 'Pihak Ketiga',
    ],

    'actions' => [
        'install' => [
            'title'       => 'Pasang',
            'heading'     => 'Pasang Plugin :name',
            'description' => "Apakah Anda yakin ingin memasang plugin ':name'? Tindakan ini akan menjalankan migrasi dan seeder.",
            'submit'      => 'Pasang Plugin',
        ],
        'uninstall' => [
            'title'   => 'Copot',
            'heading' => 'Copot Plugin',
            'submit'  => 'Copot Plugin',
        ],
    ],

    'notifications' => [
        'installed' => [
            'title' => 'Plugin Berhasil Dipasang',
            'body'  => "Plugin ':name' telah dipasang.",
        ],
        'installed-failed' => [
            'title' => 'Pemasangan Gagal',
        ],
        'uninstalled' => [
            'title' => 'Plugin Berhasil Dicopot',
            'body'  => "Plugin ':name' telah dicopot.",
        ],
        'uninstalled-failed' => [
            'title' => 'Pencopotan Gagal',
        ],
    ],

    'infolist' => [
        'section' => [
            'plugin'       => 'Informasi Plugin',
            'dependencies' => 'Dependensi',
        ],
        'name'         => 'Nama Plugin',
        'version'      => 'Versi',
        'dependencies' => 'Plugin yang Diperlukan',
        'dependents'   => 'Plugin yang Bergantung Pada Ini',
        'is_installed' => 'Status Instalasi',
        'license'      => 'Lisensi',
        'summary'      => 'Deskripsi',

        'dependencies-repeater' => [
            'title'        => 'Plugin yang Diperlukan',
            'name'         => 'Nama Plugin',
            'is_installed' => 'Terpasang',
            'placeholder'  => 'Tidak ada dependensi yang diperlukan',
        ],

        'dependents-repeater' => [
            'title'        => 'Plugin yang Bergantung Pada Ini',
            'name'         => 'Nama Plugin',
            'is_installed' => 'Terpasang',
            'placeholder'  => 'Tidak ada dependent',
        ],

    ],

];
