<?php

return [
    'title'        => 'template alur kerja HR',
    'plural_title' => 'template alur kerja HR',
    'navigation'   => 'Template alur kerja',
    'sections'     => [
        'identity'   => 'Informasi template',
        'steps'      => 'Tahapan operasional',
        'steps_hint' => 'Atur urutan aktivitas, penanggung jawab, tenggat, dan bukti wajib.',
    ],
    'fields' => [
        'name'               => 'Nama',
        'code'               => 'Kode',
        'type'               => 'Jenis alur kerja',
        'description'        => 'Keterangan',
        'is_active'          => 'Aktif',
        'step_name'          => 'Aktivitas',
        'department'         => 'Departemen penanggung jawab',
        'due_days'           => 'Tenggat (hari)',
        'default_assignee'   => 'PIC bawaan',
        'is_required'        => 'Aktivitas wajib',
        'requires_evidence'  => 'Wajib bukti',
        'step_count'         => 'Aktivitas',
    ],
    'actions' => ['add_step' => 'Tambah aktivitas'],
    'types'   => [
        'onboarding'       => 'Karyawan masuk',
        'offboarding'      => 'Karyawan keluar',
        'contract_renewal' => 'Perpanjangan kontrak',
        'disciplinary'     => 'Surat peringatan',
        'training'         => 'Pelatihan',
        'offering'         => 'Penawaran kerja',
        'custom'           => 'Kustom',
    ],
];
