<?php

return [
    'name' => 'Rekrutmen',

    'navigation' => [
        'group' => 'Rekrutmen',
    ],

    'resources' => [
        'rekrutmen_pipeline' => [
            'navigation' => [
                'label' => 'Pipeline Rekrutmen',
            ],
            'model' => [
                'singular' => 'Pipeline Rekrutmen',
                'plural'   => 'Pipeline Rekrutmen',
            ],
            'form' => [
                'sections' => [
                    'pipeline_details' => 'Detail Pipeline',
                    'stages'           => 'Tahapan Rekrutmen',
                ],
                'descriptions' => [
                    'stages' => 'Tentukan tahapan untuk pipeline ini secara berurutan.',
                ],
                'fields' => [
                    'name'        => 'Nama',
                    'description' => 'Deskripsi',
                ],
                'actions' => [
                    'add_stage' => 'Tambah Tahap',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'         => 'Nama',
                    'stages_count' => 'Total Tahap',
                ],
            ],
        ],

        'request_man_power' => [
            'navigation' => [
                'label' => 'Permintaan Tenaga Kerja',
            ],
            'model' => [
                'singular' => 'Permintaan Tenaga Kerja',
                'plural'   => 'Permintaan Tenaga Kerja',
            ],
            'form' => [
                'sections' => [
                    'applicant_information' => 'Informasi Pengaju',
                    'requirement_details'   => 'Detail Kebutuhan',
                    'qualifications'        => 'Kualifikasi & Deskripsi Pekerjaan',
                    'approval_status'       => 'Status Persetujuan',
                ],
                'fields' => [
                    'nama_pengaju'               => 'Nama Pengaju',
                    'posisi_pengaju'             => 'Posisi Pengaju',
                    'email_address'              => 'Email Pengaju',
                    'tanggal_pengajuan'          => 'Tanggal Pengajuan',
                    'divisi'                     => 'Divisi',
                    'badan_usaha'                => 'Badan Usaha',
                    'posisi_dibutuhkan'          => 'Posisi yang Dibutuhkan',
                    'lokasi_penempatan'          => 'Lokasi Penempatan',
                    'status_kebutuhan'           => 'Status Kebutuhan',
                    'level_pekerjaan'            => 'Level Pekerjaan',
                    'jumlah_karyawan_dibutuhkan' => 'Jumlah Karyawan Dibutuhkan',
                    'estimasi_tanggal_join'      => 'Estimasi Tanggal Join',
                    'nama_karyawan_replacement'  => 'Nama Karyawan yang Akan Digantikan',
                    'requirements_kualifikasi'   => 'Kualifikasi yang Dibutuhkan',
                    'job_description'            => 'Deskripsi Pekerjaan',
                    'keterangan'                 => 'Keterangan Tambahan',
                    'status'                     => 'Status Persetujuan',
                    'approved_by'                => 'Disetujui Oleh',
                ],
                'helper_texts' => [
                    'nama_karyawan_replacement' => 'Untuk kebutuhan replacement, isi nama karyawan yang akan digantikan.',
                ],
            ],
            'table' => [
                'columns' => [
                    'nama_pengaju'               => 'Nama Pengaju',
                    'posisi_dibutuhkan'          => 'Posisi Dibutuhkan',
                    'divisi'                     => 'Divisi',
                    'status_kebutuhan'           => 'Status Kebutuhan',
                    'nama_karyawan_replacement'  => 'Karyawan Pengganti',
                    'jumlah_karyawan_dibutuhkan' => 'Jumlah',
                    'tanggal_pengajuan'          => 'Tanggal Pengajuan',
                    'status'                     => 'Status Persetujuan',
                ],
                'placeholders' => [
                    'nama_karyawan_replacement' => '-',
                ],
                'filters' => [
                    'status'           => 'Status Persetujuan',
                    'status_kebutuhan' => 'Status Kebutuhan',
                    'divisi'           => 'Divisi',
                ],
                'actions' => [
                    'approve'     => 'Setujui',
                    'reject'      => 'Tolak',
                    'set_pending' => 'Set Pending',
                ],
            ],
        ],

        'job_posting' => [
            'navigation' => [
                'label' => 'Lowongan Kerja',
            ],
            'model' => [
                'singular' => 'Lowongan Kerja',
                'plural'   => 'Lowongan Kerja',
            ],
            'generated_title' => 'Lowongan Kerja #:id',
            'form'            => [
                'sections' => [
                    'job_information' => 'Informasi Lowongan',
                    'details'         => 'Detail',
                ],
                'fields' => [
                    'request_man_power_id' => 'Permintaan Tenaga Kerja Terkait (Opsional)',
                    'rekrutmen_pipeline_id'=> 'Pipeline Rekrutmen',
                    'title'                => 'Judul',
                    'slug'                 => 'Slug',
                    'location'             => 'Lokasi',
                    'closing_date'         => 'Tanggal Penutupan',
                    'is_published'         => 'Dipublikasikan untuk Rekrutmen',
                    'description'          => 'Deskripsi',
                    'requirements'         => 'Persyaratan',
                ],
            ],
            'table' => [
                'columns' => [
                    'title'        => 'Judul',
                    'location'     => 'Lokasi',
                    'is_published' => 'Dipublikasikan',
                    'closing_date' => 'Tanggal Penutupan',
                ],
                'filters' => [
                    'is_published' => 'Dipublikasikan',
                ],
            ],
        ],

        'job_application' => [
            'navigation' => [
                'label' => 'Lamaran Kerja',
            ],
            'model' => [
                'singular' => 'Lamaran Kerja',
                'plural'   => 'Lamaran Kerja',
            ],
            'generated' => [
                'unknown_position' => 'posisi-tidak-diketahui',
            ],
            'form' => [
                'sections' => [
                    'candidate_information' => 'Informasi Kandidat',
                    'application_details'   => 'Detail Lamaran',
                ],
                'fields' => [
                    'job_posting_id'   => 'Lowongan Kerja',
                    'full_name'        => 'Nama Lengkap',
                    'email'            => 'Email',
                    'phone'            => 'Nomor Telepon',
                    'portfolio_url'    => 'URL Portofolio',
                    'current_stage_id' => 'Tahap Saat Ini',
                    'status'           => 'Status',
                    'resume_path'      => 'CV',
                    'cover_letter'     => 'Surat Lamaran',
                ],
            ],
            'table' => [
                'columns' => [
                    'full_name'     => 'Nama Lengkap',
                    'job_posting'   => 'Melamar Untuk',
                    'email'         => 'Email',
                    'phone'         => 'Nomor Telepon',
                    'current_stage' => 'Tahap',
                    'status'        => 'Status',
                ],
                'filters' => [
                    'job_posting_id' => 'Lowongan Kerja',
                    'status'         => 'Status',
                ],
                'actions' => [
                    'change_stage'    => 'Pindah Tahap',
                    'to_stage_id'     => 'Pindah ke Tahap',
                    'notes'           => 'Catatan',
                    'download_resume' => 'CV',
                ],
            ],
        ],
    ],

    'relation_managers' => [
        'histories' => [
            'title'   => 'Riwayat',
            'columns' => [
                'from_stage'   => 'Dari Tahap',
                'to_stage'     => 'Ke Tahap',
                'status'       => 'Status',
                'notes'        => 'Catatan',
                'performed_by' => 'Dilakukan Oleh',
                'created_at'   => 'Tanggal',
            ],
            'placeholders' => [
                'from_stage' => 'Awal',
                'to_stage'   => 'N/A',
            ],
        ],
    ],

    'public_request_form' => [
        'layout' => [
            'title' => 'Form Request Man Power',
        ],
        'summary' => [
            'title'       => 'Permintaan Berhasil Dikirim',
            'description' => 'Ringkasan berikut dapat digunakan untuk memastikan data yang Anda kirim sudah sesuai.',
            'fields'      => [
                'status_response_id' => 'ID Tracking',
                'posisi_dibutuhkan'  => 'Posisi Dibutuhkan',
                'nama_pengaju'       => 'Nama Pengaju',
                'status_kebutuhan'   => 'Status Kebutuhan',
                'nama_replacement'   => 'Nama Karyawan Pengganti',
                'progress_url'       => 'Link Progress',
            ],
            'actions' => [
                'submit_another' => 'Kirim Pengajuan Lain',
            ],
        ],
        'header' => [
            'title'       => 'FORM REQUEST MAN POWER',
            'description' => 'Isi formulir berikut untuk mengajukan kebutuhan tenaga kerja.',
            'required'    => '* Wajib diisi',
        ],
        'fields' => [
            'nama_pengaju'               => 'Nama Pengaju',
            'posisi_pengaju'             => 'Posisi / Jabatan Pengaju',
            'email_address'              => 'Email Pengaju',
            'tanggal_pengajuan'          => 'Tanggal Pengajuan',
            'divisi'                     => 'Divisi',
            'badan_usaha'                => 'Badan Usaha',
            'posisi_dibutuhkan'          => 'Posisi yang Dibutuhkan',
            'lokasi_penempatan'          => 'Lokasi Penempatan',
            'status_kebutuhan'           => 'Status Kebutuhan',
            'nama_karyawan_replacement'  => 'Nama Karyawan yang Akan Digantikan',
            'level_pekerjaan'            => 'Level Pekerjaan',
            'jumlah_karyawan_dibutuhkan' => 'Jumlah Karyawan Dibutuhkan',
            'estimasi_tanggal_join'      => 'Estimasi Tanggal Join',
            'requirements_kualifikasi'   => 'Kualifikasi yang Dibutuhkan',
            'job_description'            => 'Deskripsi Pekerjaan',
            'keterangan'                 => 'Keterangan Tambahan',
        ],
        'placeholders' => [
            'nama_pengaju'               => 'Nama lengkap pengaju',
            'posisi_pengaju'             => 'Contoh: Manager HRD',
            'email_address'              => 'email@perusahaan.com',
            'divisi'                     => 'Contoh: Finance, Operations',
            'badan_usaha'                => 'Nama perusahaan / entitas bisnis',
            'posisi_dibutuhkan'          => 'Contoh: Staff Akuntansi',
            'lokasi_penempatan'          => 'Contoh: Jakarta Pusat',
            'nama_karyawan_replacement'  => 'Contoh: Budi Santoso',
            'requirements_kualifikasi'   => 'Syarat pendidikan, pengalaman, dan keterampilan yang dibutuhkan...',
            'job_description'            => 'Uraian tugas dan tanggung jawab posisi yang dibutuhkan...',
            'keterangan'                 => 'Informasi tambahan yang relevan (opsional)',
        ],
        'helper_texts' => [
            'nama_karyawan_replacement' => 'Untuk kebutuhan replacement, isi nama karyawan yang akan digantikan.',
        ],
        'actions' => [
            'submit' => 'Kirim Pengajuan',
        ],
        'pagination' => [
            'single_page' => 'Halaman :current dari :total',
        ],
        'notifications' => [
            'success' => [
                'title' => 'Berhasil',
                'body'  => 'Pengajuan Request Man Power berhasil dikirim!',
            ],
            'validation' => [
                'title' => 'Validasi gagal',
                'body'  => 'Silakan periksa kembali data yang diisi.',
            ],
        ],
        'errors' => [
            'nama_karyawan_replacement_required' => 'Nama karyawan pengganti wajib diisi.',
            'system'                             => 'Terjadi kesalahan sistem, silakan coba lagi.',
            'recaptcha_required'                 => 'Verifikasi reCAPTCHA wajib diisi.',
            'recaptcha_failed'                   => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.',
        ],
    ],

    'public_progress' => [
        'heading'            => 'Progress Permintaan Tenaga Kerja',
        'subheading'         => 'Pantau status terbaru permintaan tenaga kerja Anda di halaman ini.',
        'page_title'         => 'PROGRESS REQUEST MAN POWER',
        'submitted_by'       => 'Diajukan oleh',
        'current_status'     => 'Status saat ini',
        'submission_summary' => 'Ringkasan Pengajuan',
        'fields'             => [
            'status_response_id'         => 'ID Tracking',
            'tanggal_pengajuan'          => 'Tanggal Pengajuan',
            'posisi_dibutuhkan'          => 'Posisi yang Dibutuhkan',
            'status_kebutuhan'           => 'Status Kebutuhan',
            'level_pekerjaan'            => 'Level Pekerjaan',
            'jumlah_karyawan_dibutuhkan' => 'Jumlah Karyawan Dibutuhkan',
            'lokasi_penempatan'          => 'Lokasi Penempatan',
            'estimasi_tanggal_join'      => 'Estimasi Tanggal Join',
            'nama_karyawan_replacement'  => 'Nama Karyawan yang Akan Digantikan',
            'requirements_kualifikasi'   => 'Kualifikasi yang Dibutuhkan',
            'job_description'            => 'Deskripsi Pekerjaan',
            'keterangan'                 => 'Keterangan Tambahan',
        ],
    ],

    'application_form' => [
        'fields' => [
            'full_name'       => 'Nama Lengkap',
            'email'           => 'Email',
            'phone'           => 'Nomor Telepon',
            'portfolio_url'   => 'URL Portofolio',
            'cover_letter'    => 'Surat Lamaran',
            'resume'          => 'CV',
            'github_url'      => 'URL GitHub',
            'expected_salary' => 'Ekspektasi Gaji',
        ],
    ],

    'api' => [
        'messages' => [
            'job_listed'            => 'Daftar lowongan berhasil diambil.',
            'job_not_found'         => 'Lowongan kerja tidak ditemukan.',
            'job_detail_retrieved'  => 'Detail lowongan berhasil diambil.',
            'job_not_open'          => 'Lowongan kerja tidak ditemukan atau sudah tidak dibuka.',
            'application_submitted' => 'Lamaran berhasil dikirim.',
        ],
        'validation' => [
            'messages' => [
                'full_name.required'       => 'Nama lengkap wajib diisi.',
                'email.required'           => 'Email wajib diisi.',
                'email.email'              => 'Format email tidak valid.',
                'phone.required'           => 'Nomor telepon wajib diisi.',
                'portfolio_url.url'        => 'Format URL portofolio tidak valid.',
                'resume.mimes'             => 'File CV harus berformat pdf, doc, atau docx.',
                'resume.max'               => 'Ukuran file CV maksimal 5 MB.',
                'additional_answers.array' => 'Jawaban tambahan harus berupa array.',
                'required'                 => 'Kolom :attribute wajib diisi.',
            ],
            'attributes' => [
                'full_name'      => 'nama lengkap',
                'email'          => 'email',
                'phone'          => 'nomor telepon',
                'portfolio_url'  => 'URL portofolio',
                'resume'         => 'CV',
                'cover_letter'   => 'surat lamaran',
            ],
        ],
        'application' => [
            'additional_answers_prefix' => 'Jawaban Tambahan:',
            'submitted_via_public_api'  => 'Lamaran dikirim melalui API publik.',
        ],
    ],

    'enums' => [
        'status_kebutuhan' => [
            'new_hiring'  => 'Karyawan Baru',
            'replacement' => 'Penggantian',
        ],
        'request_man_power_status' => [
            'pending'  => 'Pending',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ],
        'job_application_status' => [
            'in_progress' => 'Dalam Proses',
            'hired'       => 'Diterima',
            'rejected'    => 'Ditolak',
            'withdrawn'   => 'Dibatalkan',
        ],
        'level_pekerjaan' => [
            'staff'       => 'Staf',
            'leader'      => 'Leader',
            'coordinator' => 'Koordinator',
            'manager'     => 'Manajer',
        ],
    ],

    'mail' => [
        'request_man_power_submitted' => [
            'subject'            => 'Request Man Power berhasil dikirim',
            'greeting'           => 'Halo :name,',
            'body'               => 'Pengajuan Request Man Power Anda berhasil diterima.',
            'position'           => 'Posisi: :value',
            'requirement_status' => 'Status kebutuhan: :value',
            'submission_id'      => 'ID Pengajuan: #:id',
            'view_progress'      => 'Lihat Progress Pengajuan',
        ],
        'request_man_power_status_changed' => [
            'subject'         => 'Pembaruan status Request Man Power',
            'greeting'        => 'Halo :name,',
            'body'            => 'Status Request Man Power Anda telah diperbarui.',
            'position'        => 'Posisi: :value',
            'latest_status'   => 'Status terbaru: :value',
            'previous_status' => 'Status sebelumnya: :value',
            'submission_id'   => 'ID Pengajuan: #:id',
            'view_progress'   => 'Lihat Progress Pengajuan',
        ],
    ],

    'common' => [
        'not_available' => '—',
    ],
];
