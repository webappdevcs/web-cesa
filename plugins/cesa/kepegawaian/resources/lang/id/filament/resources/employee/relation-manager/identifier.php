<?php

return [
    'title'          => 'Identitas Eksternal',
    'canonical_uuid' => 'UUID Kanonik',
    'form'           => [
        'source_system'      => 'Sistem Sumber',
        'source_system_help' => 'Contoh: talenta, hr_workbook, legacy_web_cesa.',
        'source_instance'    => 'Instance Sumber',
        'identifier_type'    => 'Jenis Identitas',
        'external_id'        => 'ID Eksternal',
    ],
    'table' => [
        'source_system'   => 'Sistem Sumber',
        'source_instance' => 'Instance Sumber',
        'identifier_type' => 'Jenis Identitas',
        'external_id'     => 'ID Eksternal',
        'verified_at'     => 'Diverifikasi Pada',
        'last_seen_at'    => 'Terakhir Terlihat',
        'retired_at'      => 'Dinonaktifkan Pada',
    ],
    'status' => [
        'current' => 'Aktif',
    ],
    'actions' => [
        'create'     => 'Tambah Identitas',
        'verify'     => 'Verifikasi',
        'retire'     => 'Nonaktifkan',
        'reactivate' => 'Aktifkan Kembali',
    ],
    'notifications' => [
        'verified'    => 'Identitas berhasil diverifikasi.',
        'retired'     => 'Identitas berhasil dinonaktifkan.',
        'reactivated' => 'Identitas berhasil diaktifkan kembali.',
    ],
];
