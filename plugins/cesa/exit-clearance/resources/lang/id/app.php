<?php

return [
    // Navigation (admin.php content merged)
    'navigation' => [
        'exit-clearance'   => 'Exit Clearance',
        'request'          => 'Pengajuan Exit Clearance|Pengajuan Exit Clearance',
    ],

    // Resource labels (admin.php content merged)
    'resources' => [
        'Request'      => 'Pengajuan Exit Clearance',
        'department'   => 'Divisi|Divisi',
        'approver'     => 'Pemberi Persetujuan|Pemberi Persetujuan',
    ],

    'config' => [
        'navigation' => [
            'label' => 'Pengaturan',
        ],
    ],

    'public' => [
        'form' => [
            'success_message'     => 'Jawaban telah dicatat.',
            'success_title'       => 'Pengajuan Berhasil Terkirim',
            'success_description' => 'Simpan informasi berikut untuk memantau proses pengajuan Anda.',
            'form_label'          => 'Form',
            'uid_label'           => 'UID:',
            'response_id_label'   => 'ID Respons:',
            'submit_another'      => 'Kirim pengajuan lain',
            'page_title'          => 'FORM EXIT CLEARANCE',
            'page_description'    => 'Mohon lengkapi data berikut untuk mengajukan proses exit clearance. Pastikan seluruh informasi yang Anda masukkan valid untuk memperlancar proses administrasi.',
            'required_note'       => '* Wajib diisi',
            'next'                => 'Berikutnya',
            'submit'              => 'Kirim',
            'back'                => 'Kembali',
            'page_of'             => 'Halaman :current dari :total',
            'validation_title'    => 'Validasi gagal.',
            'validation_body'     => 'Silakan periksa kembali data yang diisi.',
            'recaptcha_required'  => 'Verifikasi reCAPTCHA wajib diisi.',
            'recaptcha_failed'    => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
            'placeholders'        => [
                'answer' => 'Jawaban Anda',
                'choose' => 'Pilih',
                'date'   => 'YYYY-MM-DD',
            ],
        ],

        'progress' => [
            'heading'             => 'Progres Exit Clearance',
            'subheading'          => 'Pantau status pengajuan Anda.',
            'page_title'          => 'PROGRES EXIT CLEARANCE',
            'submitted_by'        => 'Pengajuan atas nama',
            'current_status'      => 'Status saat ini',
            'submission_summary'  => 'RINGKASAN PENGAJUAN',
            'personal_data'       => 'Data Diri',
            'questionnaire'       => 'Kuesioner',
            'clearance'           => 'Kliring',
            'approval_flow'       => 'ALUR PENYETUJUAN',
            'view_attachment'     => 'Lihat lampiran',
            'notes'               => 'Catatan',
            'process_time'        => 'Waktu proses:',
        ],

        'approval' => [
            'heading'              => 'Persetujuan Exit Clearance',
            'subheading'           => 'Pengajuan atas nama :name',
            'subheading_default'   => 'Pengajuan exit clearance.',
            'notes_label'          => 'Catatan (opsional)',
            'cannot_process'       => 'Aksi tidak dapat diproses.',
            'approved_success'     => 'Pengajuan berhasil disetujui.',
            'rejected_success'     => 'Pengajuan berhasil ditolak.',
            'page_title'           => 'Permintaan Persetujuan',
            'please_review'        => 'Mohon tinjau pengajuan atas nama',
            'submission_status'    => 'Status pengajuan',
            'your_approval_status' => 'Status persetujuan Anda',
            'action'               => 'TINDAKAN',
            'reject'               => 'Tolak',
            'approve'              => 'Setujui',
            'information'          => 'INFORMASI',
            'already_processed'    => 'Tahapan ini sudah diproses. Anda dapat menutup halaman ini.',
        ],
    ],

    'form' => [
        'step' => [
            'resignation_letter' => 'Surat Pengunduran Diri',
            'personal_data'      => 'Data Diri',
            'exit_interview'     => 'Wawancara Keluar',
            'exit_clearance'     => 'Exit Clearance',
        ],

        'resignation_letter' => [
            'info'         => 'Surat Pengunduran Diri',
            'not_required' => 'Tidak diperlukan jika karyawan yang bersangkutan habis kontrak',
        ],

        'fields' => [
            'name'           => 'Nama Lengkap',
            'email'          => 'Alamat Email',
            'phone'          => 'Nomor HP',
            'position'       => 'Posisi / Jabatan',
            'placement'      => 'Lokasi Penempatan',
            'department'     => 'Divisi',
            'join_date'      => 'Tanggal Mulai Kerja',
            'departure_date' => 'Tanggal Selesai Kerja',
        ],

        'file_upload' => [
            'label'        => 'Unggah File',
            'helper_text'  => 'Format yang diperbolehkan: PDF, JPG, PNG. Maksimal 10MB.',
        ],

        'exit_interview' => [
            'q1' => '1. Alasan Anda mengajukan permohonan pengunduran diri?',
            'q2' => '2. Jelaskan bagaimana perasaan Anda terhadap beban pekerjaan yang telah diberikan sejak awal masuk kerja hingga saat ini.',
            'q3' => '3. Jelaskan bagaimana jenjang karir Anda selama bekerja di perusahaan ini.',
            'q4' => '4. Bagaimana penilaian Anda terhadap perhatian, kesejahteraan, dan fasilitas yang diberikan perusahaan kepada Anda.',
            'q5' => '5. Bagaimana hubungan kerja Anda di lingkungan perusahaan ini.',
            'q6' => '6. Bagaimana penilaian Anda terhadap kompensasi yang Anda terima dari perusahaan saat ini.',
            'q7' => '7. Berikan pendapat Anda mengenai departemen tempat Anda ditempatkan sebagai bahan masukan bagi kami.',
            'q8' => '8. Berikan pendapat Anda mengenai perusahaan ini sebagai bahan masukan bagi kami.',
        ],

        'clearance' => [
            'section_title' => 'Exit Clearance',
            'item_1'        => '1. Kartu Halo dan tagihan',
            'item_2'        => '2. Hutang karyawan',
            'item_3'        => '3. Pengembalian seragam',
            'item_4'        => '4. Pengembalian kendaraan',
            'item_5'        => '5. Pengembalian inventaris',
            'item_6'        => '6. Penonaktifan akun',
            'item_7'        => '7. Data tagihan atau piutang',
            'item_8'        => '8. Promotor internal',
            'item_9'        => '9. Nota tertunda',
            'item_10'       => '10. Stok opname',
        ],

        'approvals' => [
            'section_title' => 'Daftar Penyetuju',
        ],

        'metadata' => [
            'section_title' => 'Metadata',
            'form_uid'      => 'UID Formulir',
            'form_status'   => 'Status Formulir',
            'form_response' => 'ID Respons Formulir',
        ],

        'infolist' => [
            'employee_info'  => 'Informasi Karyawan',
            'request_status' => 'Ringkasan Permintaan',
            'approval_chain' => 'Rantai Persetujuan',
        ],

        'infolist_fields' => [
            'name'           => 'Nama',
            'email'          => 'Email',
            'phone'          => 'Telepon',
            'department'     => 'Divisi',
            'position'       => 'Posisi',
            'placement'      => 'Penempatan',
            'joined'         => 'Masuk',
            'departing'      => 'Keluar',
            'surat_resign'   => 'Surat Pengunduran Diri',
            'no_file'        => 'Tidak ada file',
            'uid'            => 'UID',
            'status'         => 'Status',
            'request_date'   => 'Tanggal Pengajuan',
            'title'          => 'Jabatan',
        ],

        'table' => [
            'uid'                  => 'UID',
            'employee_name'        => 'Nama Karyawan',
            'email'                => 'Email',
            'position'             => 'Posisi',
            'placement'            => 'Penempatan',
            'status'               => 'Status',
            'join_date'            => 'Tanggal Masuk',
            'request_date'         => 'Tanggal Pengajuan',
            'departure_date'       => 'Tanggal Keluar',
            'reason'               => 'Alasan',
            'resignation_letter'   => 'Surat Pengunduran Diri',
            'department'           => 'Divisi',
            'approvers'            => 'Penyetuju',
        ],

        'filters' => [
            'department'   => 'Divisi',
            'request_date' => 'Tanggal Pengajuan',
        ],

        // Department Resource
        'department' => [
            'code'        => 'Kode',
            'name'        => 'Nama',
            'description' => 'Deskripsi',
            'approvers'   => 'Pemberi Persetujuan',
        ],

        // Approver Resource
        'approver' => [
            'name'        => 'Nama',
            'email'       => 'Email',
            'phone'       => 'Telepon',
            'title'       => 'Jabatan',
            'departments' => 'Divisi',
        ],
    ],

];
