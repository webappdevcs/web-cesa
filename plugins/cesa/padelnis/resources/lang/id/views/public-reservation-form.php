<?php

return [
    'title'       => 'TICKETING RESERVASI PADELNIS',
    'description' => 'Silakan lengkapi data reservasi di bawah ini. Kini Anda dapat memilih durasi bermain lebih dari 1 jam sekaligus — cukup tentukan Jam Mulai dan Jam Berakhir, tidak perlu booking per 1 jam.',
    'required'    => '* Pertanyaan wajib diisi',

    'placeholders' => [
        'customer_name'    => 'Masukkan nama customer',
        'catalog_item'     => 'Pilih layanan atau paket',
        'reservation_date' => 'Pilih tanggal reservasi',
        'court'            => 'Pilih lapangan',
        'coach'            => 'Pilih coach',
        'reservation_time' => 'Pilih jam mulai - jam berakhir',
        'expected_amount'  => 'Terisi otomatis dari master harga',
        'transfer_amount'  => 'Contoh: 150.000',
        'transfer_date'    => 'Pilih tanggal transfer',
        'notes'            => 'Contoh: Transfer dari BCA atas nama Budi',
    ],

    'actions' => [
        'submit'         => 'Kirim Reservasi',
        'submit_another' => 'Kirim reservasi lain',
    ],

    'pagination' => [
        'single_page' => 'Halaman :current dari :total',
    ],

    'summary' => [
        'title'       => 'Reservasi Berhasil Dikirim',
        'description' => 'Reservasi masih pending untuk dicek admin. Simpan ID Reff untuk follow-up.',
    ],

    'messages' => [
        'generic' => 'Terjadi kesalahan saat mengirim reservasi. Silakan coba lagi.',
    ],

    'notifications' => [
        'submitted' => [
            'title' => 'Reservasi terkirim',
            'body'  => 'Reservasi berhasil disimpan dengan ID Reff :id_reff.',
        ],
        'failed' => [
            'title' => 'Reservasi gagal dikirim',
            'body'  => 'Reservasi belum bisa disimpan. Silakan coba lagi.',
        ],
    ],
];
