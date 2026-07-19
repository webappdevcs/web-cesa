<?php

return [
    'title'        => 'Konflik sinkronisasi karyawan',
    'plural_title' => 'Konflik sinkronisasi karyawan',
    'navigation'   => 'Review konflik sinkronisasi',
    'sections'     => [
        'review'      => 'Review konflik',
        'review_help' => 'Payload mentah vendor dan detail error internal sengaja tidak ditampilkan di halaman ini.',
    ],
    'fields' => [
        'type'               => 'Jenis konflik',
        'status'             => 'Status',
        'source_system'      => 'Sistem sumber',
        'source_instance'    => 'Instans sumber',
        'run_uuid'           => 'UUID proses',
        'run_mode'           => 'Mode proses',
        'row_number'         => 'Baris sumber',
        'external_id'        => 'ID record eksternal',
        'employee_code'      => 'Kode karyawan sumber',
        'canonical_employee' => 'Karyawan kanonik',
        'created_at'         => 'Terdeteksi pada',
        'resolution'         => 'Resolusi',
        'resolver'           => 'Diselesaikan oleh',
        'resolved_at'        => 'Diselesaikan pada',
        'resolution_notes'   => 'Catatan resolusi',
        'rejection_reason'   => 'Alasan penolakan',
    ],
    'values' => [
        'open'     => 'Terbuka',
        'resolved' => 'Selesai',
    ],
    'types' => [
        'invalid_record'                    => 'Record tidak valid',
        'missing_external_id'               => 'ID eksternal tidak ada',
        'identifier_employee_code_mismatch' => 'Pemilik ID dan kode karyawan berbeda',
        'employee_code_change'              => 'Kode karyawan berubah',
        'retired_employee_match'            => 'Cocok dengan karyawan yang sudah diarsipkan',
    ],
    'actions' => [
        'recheck'       => 'Periksa ulang identitas kanonik',
        'reject_source' => 'Tolak record sumber',
    ],
    'rejection_reasons' => [
        'duplicate_source_record' => 'Record sumber duplikat',
        'invalid_source_record'   => 'Record sumber tidak valid',
        'out_of_scope'            => 'Di luar cakupan master karyawan',
    ],
    'notifications' => [
        'rechecked'       => 'Konflik sudah tidak ambigu dan telah diselesaikan.',
        'still_ambiguous' => 'Identitas kanonik masih ambigu. Tidak ada data yang diubah.',
        'rejected'        => 'Record sumber ditolak dan keputusannya tercatat dalam audit.',
        'failed'          => 'Konflik tidak dapat diselesaikan. Data kanonik tidak diubah.',
    ],
];
