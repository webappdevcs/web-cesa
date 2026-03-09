<?php

return [
    'index' => [
        'heading'     => 'DAFTAR FORM TRANSFER',
        'description' => 'Ajukan dan pantau permintaan transfer melalui form yang tersedia.',
    ],

    'form' => [
        'heading'       => 'FORM PENGAJUAN TRANSFER - :form',
        'description'   => 'Isi informasi berikut untuk mengajukan permintaan transfer :form.',
        'submit'        => 'Kirim Permintaan',
        'placeholders'  => [
            'email'           => 'Masukkan email',
            'requester_name'  => 'Masukkan nama pemohon',
            'account_number'  => 'Masukkan nomor rekening',
            'account_name'    => 'Masukkan nama pemilik rekening',
            'transfer_amount' => 'Contoh: 1000000',
            'purpose'         => 'Tuliskan keperluan transfer',
            'reference_note'  => 'Tuliskan catatan referensi',
        ],
        'account_validation' => [
            'action'       => 'Cek Rekening',
            'hint'         => 'Klik Cek Rekening untuk memvalidasi nomor rekening.',
            'hint_manual'  => 'Klik Cek Rekening untuk memvalidasi nomor rekening. Nama penerima tetap diisi manual.',
            'success'      => 'Rekening terverifikasi.',
            'not_found'    => 'Rekening tidak ditemukan. Periksa bank dan nomor rekening.',
            'failed'       => 'Validasi rekening gagal. Silakan coba lagi.',
            'rate_limited' => 'Terlalu banyak percobaan validasi. Coba lagi nanti.',
        ],
        'notifications' => [
            'success' => [
                'title' => 'Permintaan Berhasil Dikirim',
                'body'  => 'Permintaan Anda berhasil dikirim. Referensi: :uid.',
            ],
            'error' => [
                'title' => 'Pengajuan Gagal',
                'body'  => 'Permintaan tidak dapat dikirim. Silakan coba kembali.',
            ],
            'validation' => [
                'title' => 'Validasi Gagal',
                'body'  => 'Periksa kembali kolom yang ditandai dan coba lagi.',
            ],
            'rate_limit' => [
                'title' => 'Terlalu Banyak Permintaan',
                'body'  => 'Kami mendeteksi terlalu banyak percobaan. Tunggu :seconds detik sebelum mencoba lagi.',
            ],
        ],
        'actions' => [
            'heading'               => 'PERSETUJUAN PENGAJUAN TRANSFER - :form',
            'subheading'            => 'Pemohon: :requester',
            'comments'              => 'Komentar',
            'comments_placeholder'  => 'Masukkan komentar untuk keputusan ini (opsional).',
            'approved'              => 'Persetujuan berhasil disimpan.',
            'rejected'              => 'Penolakan berhasil disimpan.',
            'invalid_state'         => 'Tugas persetujuan ini sudah tidak dapat diproses.',
            'already_processed_body' => 'Permintaan ini sudah diproses sebelumnya dan tidak dapat diubah lagi.',
            'rate_limit'            => [
                'title' => 'Terlalu Banyak Percobaan',
                'body'  => 'Tunggu :seconds detik sebelum mencoba lagi.',
            ],
        ],
    ],

    'progress' => [
        'heading'            => 'PANTAU PROGRES PERMINTAAN',
        'description'        => 'Lacak status permintaan transfer menggunakan referensi yang diberikan saat pengajuan.',
        'attn'               => 'a.n.',
        'current_status'     => 'Status saat ini',
        'submission_summary' => 'RINGKASAN PENGAJUAN',
        'approval_flow'      => 'ALUR PERSETUJUAN',
    ],

    'submission' => [
        'success_title'         => 'Pengajuan Berhasil Terkirim',
        'success_description'   => 'Simpan :reference_label berikut untuk memantau proses: :uid.',
        'form_label'            => 'Form Transfer',
        'reference_id_label'    => 'ID Referensi',
        'status_response_label' => 'ID Status Response',
        'submit_another'        => 'Kirim pengajuan lain',
        'required_hint'         => '* Wajib diisi',
        'page_of'               => 'Halaman :current dari :total',
    ],

    'approval' => [
        'submission_status_label' => 'Status pengajuan',
        'your_approval_status'    => 'Status persetujuan Anda',
        'submission_summary'      => 'RINGKASAN PENGAJUAN',
        'approval_flow'           => 'ALUR PERSETUJUAN',
        'actions'                 => 'TINDAKAN',
        'reject'                  => 'Tolak',
        'approve'                 => 'Setujui',
        'information'             => 'INFORMASI',
        'completed_info'          => 'Tahap ini sudah diproses. Anda dapat menutup halaman ini.',
    ],
];
