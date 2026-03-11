<?php

return [
    'title'                   => 'Profil',
    'heading'                 => 'Profil',
    'subheading'              => 'Kelola pengaturan akun dan preferensi Anda.',
    'information_section'     => 'Informasi Profil',
    'information_description' => 'Perbarui informasi profil akun dan alamat email Anda.',

    'notification' => [
        'success' => [
            'title' => 'Profil Diperbarui',
            'body'  => 'Profil Anda berhasil diperbarui.',
        ],

        'error' => [
            'title' => 'Pembaruan Profil Gagal',
            'body'  => 'Terjadi kesalahan saat memperbarui profil Anda.',
        ],

        'validation-error' => [
            'title' => 'Kesalahan Validasi',
        ],
    ],

    'actions' => [
        'save' => 'Simpan Perubahan',
    ],

    'fields' => [
        'avatar' => 'Foto Profil',
        'name'   => 'Nama',
        'email'  => 'Email',
    ],

    'password' => [
        'section'     => 'Perbarui Kata Sandi',
        'description' => 'Pastikan akun Anda menggunakan kata sandi yang panjang dan acak untuk tetap aman.',
        'current'     => 'Kata Sandi Saat Ini',
        'new'         => 'Kata Sandi Baru',
        'confirm'     => 'Konfirmasi Kata Sandi',
        'helper'      => 'Minimal 8 karakter.',

        'errors' => [
            'current-required'  => 'Kata sandi saat ini wajib diisi.',
            'current-incorrect' => 'Kata sandi saat ini salah. Silakan coba lagi.',
            'same-as-current'   => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ],

        'current-helper' => 'Masukkan kata sandi saat ini untuk memverifikasi identitas Anda.',

        'notification' => [
            'success' => [
                'title' => 'Kata Sandi Diperbarui',
                'body'  => 'Kata sandi Anda berhasil diperbarui.',
            ],

            'error' => [
                'title' => 'Pembaruan Kata Sandi Gagal',
                'body'  => 'Terjadi kesalahan saat memperbarui kata sandi Anda.',
            ],
        ],
    ],
];
