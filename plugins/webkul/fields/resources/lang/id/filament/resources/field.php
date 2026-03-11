<?php

return [
    'navigation' => [
        'title' => 'Field Kustom',
        'group' => 'Pengaturan',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'fields' => [
                    'name'             => 'Nama',
                    'code'             => 'kode',
                    'code-helper-text' => 'Kode harus diawali huruf atau underscore, dan hanya boleh berisi huruf, angka, dan underscore.',
                ],
            ],

            'options' => [
                'title' => 'Opsi',

                'fields' => [
                    'add-option' => 'Tambah Opsi',
                ],
            ],

            'form-settings' => [
                'title' => 'Pengaturan Form',

                'field-sets' => [
                    'validations' => [
                        'title' => 'Validasi',

                        'fields' => [
                            'validation'     => 'Validasi',
                            'field'          => 'Field',
                            'value'          => 'Nilai',
                            'add-validation' => 'Tambah Validasi',
                        ],
                    ],

                    'additional-settings' => [
                        'title' => 'Pengaturan Tambahan',

                        'fields' => [
                            'setting'     => 'Pengaturan',
                            'value'       => 'Nilai',
                            'color'       => 'Warna',
                            'add-setting' => 'Tambah Pengaturan',

                            'color-options' => [
                                'danger'    => 'Bahaya',
                                'info'      => 'Info',
                                'primary'   => 'Primer',
                                'secondary' => 'Sekunder',
                                'warning'   => 'Peringatan',
                                'success'   => 'Sukses',
                            ],

                            'grid-options' => [
                                'row'    => 'Baris',
                                'column' => 'Kolom',
                            ],

                            'input-modes' => [
                                'text'     => 'Teks',
                                'email'    => 'Email',
                                'numeric'  => 'Numerik',
                                'integer'  => 'Integer',
                                'password' => 'Kata Sandi',
                                'tel'      => 'Telepon',
                                'url'      => 'URL',
                                'color'    => 'Warna',
                                'none'     => 'Tidak Ada',
                                'decimal'  => 'Desimal',
                                'search'   => 'Pencarian',
                                'url'      => 'URL',
                            ],
                        ],
                    ],
                ],

                'validations' => [
                    'common' => [
                        'gt'                   => 'Lebih Besar Dari',
                        'gte'                  => 'Lebih Besar Dari atau Sama Dengan',
                        'lt'                   => 'Lebih Kecil Dari',
                        'lte'                  => 'Lebih Kecil Dari atau Sama Dengan',
                        'max-size'             => 'Ukuran Maks',
                        'min-size'             => 'Ukuran Min',
                        'multiple-of'          => 'Kelipatan Dari',
                        'nullable'             => 'Boleh Kosong',
                        'prohibited'           => 'Dilarang',
                        'prohibited-if'        => 'Dilarang Jika',
                        'prohibited-unless'    => 'Dilarang Kecuali',
                        'prohibits'            => 'Melarang',
                        'required'             => 'Wajib',
                        'required-if'          => 'Wajib Jika',
                        'required-if-accepted' => 'Wajib Jika Diterima',
                        'required-unless'      => 'Wajib Kecuali',
                        'required-with'        => 'Wajib Dengan',
                        'required-with-all'    => 'Wajib Dengan Semua',
                        'required-without'     => 'Wajib Tanpa',
                        'required-without-all' => 'Wajib Tanpa Semua',
                        'rules'                => 'Aturan Kustom',
                        'unique'               => 'Unik',
                    ],

                    'text' => [
                        'alpha-dash'        => 'Alfa Dash',
                        'alpha-num'         => 'Alfanumerik',
                        'ascii'             => 'ASCII',
                        'doesnt-end-with'   => 'Tidak Diakhiri Dengan',
                        'doesnt-start-with' => 'Tidak Diawali Dengan',
                        'ends-with'         => 'Diakhiri Dengan',
                        'filled'            => 'Terisi',
                        'ip'                => 'IP',
                        'ipv4'              => 'IPv4',
                        'ipv6'              => 'IPv6',
                        'length'            => 'Panjang',
                        'mac-address'       => 'Alamat MAC',
                        'max-length'        => 'Panjang Maks',
                        'min-length'        => 'Panjang Min',
                        'regex'             => 'Regex',
                        'starts-with'       => 'Diawali Dengan',
                        'ulid'              => 'ULID',
                        'uuid'              => 'UUID',
                    ],

                    'textarea' => [
                        'filled'     => 'Terisi',
                        'max-length' => 'Panjang Maks',
                        'min-length' => 'Panjang Min',
                    ],

                    'select' => [
                        'different' => 'Berbeda',
                        'exists'    => 'Ada',
                        'in'        => 'Di Dalam',
                        'not-in'    => 'Tidak Di Dalam',
                        'same'      => 'Sama',
                    ],

                    'radio' => [],

                    'checkbox' => [
                        'accepted' => 'Diterima',
                        'declined' => 'Ditolak',
                    ],

                    'toggle' => [
                        'accepted' => 'Diterima',
                        'declined' => 'Ditolak',
                    ],

                    'checkbox-list' => [
                        'in'        => 'Di Dalam',
                        'max-items' => 'Item Maks',
                        'min-items' => 'Item Min',
                    ],

                    'datetime' => [
                        'after'           => 'Setelah',
                        'after-or-equal'  => 'Setelah atau Sama Dengan',
                        'before'          => 'Sebelum',
                        'before-or-equal' => 'Sebelum atau Sama Dengan',
                    ],

                    'editor' => [
                        'filled'     => 'Terisi',
                        'max-length' => 'Panjang Maks',
                        'min-length' => 'Panjang Min',
                    ],

                    'markdown' => [
                        'filled'     => 'Terisi',
                        'max-length' => 'Panjang Maks',
                        'min-length' => 'Panjang Min',
                    ],

                    'color' => [
                        'hex-color' => 'Warna Hex',
                    ],
                ],

                'settings' => [
                    'text' => [
                        'autocapitalize'    => 'Kapitalisasi Otomatis',
                        'autocomplete'      => 'Pelengkapan Otomatis',
                        'autofocus'         => 'Fokus Otomatis',
                        'default'           => 'Nilai Default',
                        'disabled'          => 'Dinonaktifkan',
                        'helper-text'       => 'Teks Bantuan',
                        'hint'              => 'Petunjuk',
                        'hint-color'        => 'Warna Petunjuk',
                        'hint-icon'         => 'Ikon Petunjuk',
                        'id'                => 'ID',
                        'input-mode'        => 'Mode Input',
                        'mask'              => 'Mask',
                        'placeholder'       => 'Placeholder',
                        'prefix'            => 'Awalan',
                        'prefix-icon'       => 'Ikon Awalan',
                        'prefix-icon-color' => 'Warna Ikon Awalan',
                        'read-only'         => 'Hanya Baca',
                        'step'              => 'Langkah',
                        'suffix'            => 'Akhiran',
                        'suffix-icon'       => 'Ikon Akhiran',
                        'suffix-icon-color' => 'Warna Ikon Akhiran',
                    ],

                    'textarea' => [
                        'autofocus'   => 'Fokus Otomatis',
                        'autosize'    => 'Ukuran Otomatis',
                        'cols'        => 'Kolom',
                        'default'     => 'Nilai Default',
                        'disabled'    => 'Dinonaktifkan',
                        'helperText'  => 'Teks Bantuan',
                        'hint'        => 'Petunjuk',
                        'hintColor'   => 'Warna Petunjuk',
                        'hintIcon'    => 'Ikon Petunjuk',
                        'id'          => 'ID',
                        'placeholder' => 'Placeholder',
                        'read-only'   => 'Hanya Baca',
                        'rows'        => 'Baris',
                    ],

                    'select' => [
                        'default'                   => 'Nilai Default',
                        'disabled'                  => 'Dinonaktifkan',
                        'helper-text'               => 'Teks Bantuan',
                        'hint'                      => 'Petunjuk',
                        'hint-color'                => 'Warna Petunjuk',
                        'hint-icon'                 => 'Ikon Petunjuk',
                        'id'                        => 'ID',
                        'loading-message'           => 'Pesan Memuat',
                        'no-search-results-message' => 'Pesan Tidak Ada Hasil Pencarian',
                        'options-limit'             => 'Batas Opsi',
                        'preload'                   => 'Pramuat',
                        'searchable'                => 'Dapat Dicari',
                        'search-debounce'           => 'Debounce Pencarian',
                        'searching-message'         => 'Pesan Sedang Mencari',
                        'search-prompt'             => 'Prompt Pencarian',
                    ],

                    'radio' => [
                        'default'     => 'Nilai Default',
                        'disabled'    => 'Dinonaktifkan',
                        'helper-text' => 'Teks Bantuan',
                        'hint'        => 'Petunjuk',
                        'hint-color'  => 'Warna Petunjuk',
                        'hint-icon'   => 'Ikon Petunjuk',
                        'id'          => 'ID',
                    ],

                    'checkbox' => [
                        'default'     => 'Nilai Default',
                        'disabled'    => 'Dinonaktifkan',
                        'helper-text' => 'Teks Bantuan',
                        'hint'        => 'Petunjuk',
                        'hint-color'  => 'Warna Petunjuk',
                        'hint-icon'   => 'Ikon Petunjuk',
                        'id'          => 'ID',
                        'inline'      => 'Inline',
                    ],

                    'toggle' => [
                        'default'     => 'Nilai Default',
                        'disabled'    => 'Dinonaktifkan',
                        'helper-text' => 'Teks Bantuan',
                        'hint'        => 'Petunjuk',
                        'hint-color'  => 'Warna Petunjuk',
                        'hint-icon'   => 'Ikon Petunjuk',
                        'id'          => 'ID',
                        'off-color'   => 'Warna Nonaktif',
                        'off-icon'    => 'Ikon Nonaktif',
                        'on-color'    => 'Warna Aktif',
                        'on-icon'     => 'Ikon Aktif',
                    ],

                    'checkbox-list' => [
                        'bulk-toggleable'           => 'Dapat Dipilih Massal',
                        'columns'                   => 'Kolom',
                        'default'                   => 'Nilai Default',
                        'disabled'                  => 'Dinonaktifkan',
                        'grid-direction'            => 'Arah Grid',
                        'helper-text'               => 'Teks Bantuan',
                        'hint'                      => 'Petunjuk',
                        'hint-color'                => 'Warna Petunjuk',
                        'hint-icon'                 => 'Ikon Petunjuk',
                        'id'                        => 'ID',
                        'max-items'                 => 'Item Maks',
                        'min-items'                 => 'Item Min',
                        'no-search-results-message' => 'Pesan Tidak Ada Hasil Pencarian',
                        'searchable'                => 'Dapat Dicari',
                    ],

                    'datetime' => [
                        'close-on-date-selection' => 'Tutup Saat Tanggal Dipilih',
                        'default'                 => 'Nilai Default',
                        'disabled'                => 'Dinonaktifkan',
                        'disabled-dates'          => 'Tanggal Dinonaktifkan',
                        'display-format'          => 'Format Tampilan',
                        'first-fay-of-week'       => 'Hari Pertama Dalam Minggu',
                        'format'                  => 'Format',
                        'helper-text'             => 'Teks Bantuan',
                        'hint'                    => 'Petunjuk',
                        'hint-color'              => 'Warna Petunjuk',
                        'hint-icon'               => 'Ikon Petunjuk',
                        'hours-step'              => 'Langkah Jam',
                        'id'                      => 'ID',
                        'locale'                  => 'Locale',
                        'minutes-step'            => 'Langkah Menit',
                        'seconds'                 => 'Detik',
                        'seconds-step'            => 'Langkah Detik',
                        'timezone'                => 'Zona Waktu',
                        'week-starts-on-monday'   => 'Minggu Dimulai pada Senin',
                        'week-starts-on-sunday'   => 'Minggu Dimulai pada Minggu',
                    ],

                    'editor' => [
                        'default'     => 'Nilai Default',
                        'disabled'    => 'Dinonaktifkan',
                        'helper-text' => 'Teks Bantuan',
                        'hint'        => 'Petunjuk',
                        'hint-color'  => 'Warna Petunjuk',
                        'hint-icon'   => 'Ikon Petunjuk',
                        'id'          => 'ID',
                        'placeholder' => 'Placeholder',
                        'read-only'   => 'Hanya Baca',
                    ],

                    'markdown' => [
                        'default'     => 'Nilai Default',
                        'disabled'    => 'Dinonaktifkan',
                        'helper-text' => 'Teks Bantuan',
                        'hint'        => 'Petunjuk',
                        'hint-color'  => 'Warna Petunjuk',
                        'hint-icon'   => 'Ikon Petunjuk',
                        'id'          => 'ID',
                        'placeholder' => 'Placeholder',
                        'read-only'   => 'Hanya Baca',
                    ],

                    'color' => [
                        'default'     => 'Nilai Default',
                        'disabled'    => 'Dinonaktifkan',
                        'helper-text' => 'Teks Bantuan',
                        'hint'        => 'Petunjuk',
                        'hint-color'  => 'Warna Petunjuk',
                        'hint-icon'   => 'Ikon Petunjuk',
                        'hsl'         => 'HSL',
                        'id'          => 'ID',
                        'rgb'         => 'RGB',
                        'rgba'        => 'RGBA',
                    ],

                    'file' => [
                        'accepted-file-types'                  => 'Tipe File yang Diterima',
                        'append-files'                         => 'Tambahkan File',
                        'deletable'                            => 'Dapat Dihapus',
                        'directory'                            => 'Direktori',
                        'downloadable'                         => 'Dapat Diunduh',
                        'fetch-file-information'               => 'Ambil Informasi File',
                        'file-attachments-directory'           => 'Direktori Lampiran File',
                        'file-attachments-visibility'          => 'Visibilitas Lampiran File',
                        'image'                                => 'Gambar',
                        'image-crop-aspect-ratio'              => 'Rasio Aspek Crop Gambar',
                        'image-editor'                         => 'Editor Gambar',
                        'image-editor-aspect-ratios'           => 'Rasio Aspek Editor Gambar',
                        'image-editor-empty-fill-color'        => 'Warna Isi Kosong Editor Gambar',
                        'image-editor-mode'                    => 'Mode Editor Gambar',
                        'image-preview-height'                 => 'Tinggi Pratinjau Gambar',
                        'image-resize-mode'                    => 'Mode Ubah Ukuran Gambar',
                        'image-resize-target-height'           => 'Target Tinggi Ubah Ukuran Gambar',
                        'image-resize-target-width'            => 'Target Lebar Ubah Ukuran Gambar',
                        'loading-indicator-position'           => 'Posisi Indikator Memuat',
                        'move-files'                           => 'Pindahkan File',
                        'openable'                             => 'Dapat Dibuka',
                        'orient-images-from-exif'              => 'Sesuaikan Orientasi Gambar dari EXIF',
                        'panel-aspect-ratio'                   => 'Rasio Aspek Panel',
                        'panel-layout'                         => 'Layout Panel',
                        'previewable'                          => 'Dapat Dipratinjau',
                        'remove-uploaded-file-button-position' => 'Posisi Tombol Hapus File Terunggah',
                        'reorderable'                          => 'Dapat Diurutkan Ulang',
                        'store-files'                          => 'Simpan File',
                        'upload-button-position'               => 'Posisi Tombol Unggah',
                        'uploading-message'                    => 'Pesan Sedang Mengunggah',
                        'upload-progress-indicator-position'   => 'Posisi Indikator Progres Unggah',
                        'visibility'                           => 'Visibilitas',
                    ],
                ],
            ],

            'table-settings' => [
                'title' => 'Pengaturan Tabel',

                'fields' => [
                    'use-in-table'  => 'Gunakan di Tabel',
                    'setting'       => 'Pengaturan',
                    'value'         => 'Nilai',
                    'color'         => 'Warna',
                    'alignment'     => 'Perataan',
                    'font-weight'   => 'Ketebalan Font',
                    'icon-position' => 'Posisi Ikon',
                    'size'          => 'Ukuran',
                    'add-setting'   => 'Tambah Pengaturan',

                    'color-options' => [
                        'danger'    => 'Bahaya',
                        'info'      => 'Info',
                        'primary'   => 'Primer',
                        'secondary' => 'Sekunder',
                        'warning'   => 'Peringatan',
                        'success'   => 'Sukses',
                    ],

                    'alignment-options' => [
                        'start'   => 'Awal',
                        'left'    => 'Kiri',
                        'center'  => 'Tengah',
                        'end'     => 'Akhir',
                        'right'   => 'Kanan',
                        'justify' => 'Rata',
                        'between' => 'Di Antara',
                    ],

                    'font-weight-options' => [
                        'extra-light' => 'Sangat Tipis',
                        'light'       => 'Tipis',
                        'normal'      => 'Normal',
                        'medium'      => 'Sedang',
                        'semi-bold'   => 'Semi Tebal',
                        'bold'        => 'Tebal',
                        'extra-bold'  => 'Sangat Tebal',
                    ],

                    'icon-position-options' => [
                        'before' => 'Sebelum',
                        'after'  => 'Sesudah',
                    ],

                    'size-options' => [
                        'extra-small' => 'Sangat Kecil',
                        'small'       => 'Kecil',
                        'medium'      => 'Sedang',
                        'large'       => 'Besar',
                    ],
                ],

                'settings' => [
                    'common' => [
                        'align-end'              => 'Rata Akhir',
                        'alignment'              => 'Perataan',
                        'align-start'            => 'Rata Awal',
                        'badge'                  => 'Badge',
                        'boolean'                => 'Boolean',
                        'color'                  => 'Warna',
                        'copyable'               => 'Dapat Disalin',
                        'copy-message'           => 'Pesan Salin',
                        'copy-message-duration'  => 'Durasi Pesan Salin',
                        'default'                => 'Default',
                        'filterable'             => 'Dapat Difilter',
                        'groupable'              => 'Dapat Dikelompokkan',
                        'grow'                   => 'Tumbuh',
                        'icon'                   => 'Ikon',
                        'icon-color'             => 'Warna Ikon',
                        'icon-position'          => 'Posisi Ikon',
                        'label'                  => 'Label',
                        'limit'                  => 'Batas',
                        'line-clamp'             => 'Batas Baris',
                        'money'                  => 'Mata Uang',
                        'placeholder'            => 'Placeholder',
                        'prefix'                 => 'Awalan',
                        'searchable'             => 'Dapat Dicari',
                        'size'                   => 'Ukuran',
                        'sortable'               => 'Dapat Diurutkan',
                        'suffix'                 => 'Akhiran',
                        'toggleable'             => 'Dapat Ditoggle',
                        'tooltip'                => 'Tooltip',
                        'vertical-alignment'     => 'Perataan Vertikal',
                        'vertically-align-start' => 'Rata Vertikal ke Awal',
                        'weight'                 => 'Ketebalan',
                        'width'                  => 'Lebar',
                        'words'                  => 'Kata',
                        'wrap-header'            => 'Header Bungkus',
                        'column-span'            => 'Rentang Kolom',
                        'helper-text'            => 'Teks Bantuan',
                        'hint'                   => 'Petunjuk',
                        'hint-color'             => 'Warna Petunjuk',
                        'hint-icon'              => 'Ikon Petunjuk',
                    ],

                    'datetime' => [
                        'date'              => 'Tanggal',
                        'date-time'         => 'Tanggal Waktu',
                        'date-time-tooltip' => 'Tooltip Tanggal Waktu',
                        'since'             => 'Sejak',
                    ],
                ],
            ],

            'infolist-settings' => [
                'title' => 'Pengaturan Infolist',

                'fields' => [
                    'setting'       => 'Pengaturan',
                    'value'         => 'Nilai',
                    'color'         => 'Warna',
                    'font-weight'   => 'Ketebalan Font',
                    'icon-position' => 'Posisi Ikon',
                    'size'          => 'Ukuran',
                    'add-setting'   => 'Tambah Pengaturan',

                    'color-options' => [
                        'danger'    => 'Bahaya',
                        'info'      => 'Info',
                        'primary'   => 'Primer',
                        'secondary' => 'Sekunder',
                        'warning'   => 'Peringatan',
                        'success'   => 'Sukses',
                    ],

                    'font-weight-options' => [
                        'extra-light' => 'Sangat Tipis',
                        'light'       => 'Tipis',
                        'normal'      => 'Normal',
                        'medium'      => 'Sedang',
                        'semi-bold'   => 'Semi Tebal',
                        'bold'        => 'Tebal',
                        'extra-bold'  => 'Sangat Tebal',
                    ],

                    'icon-position-options' => [
                        'before' => 'Sebelum',
                        'after'  => 'Sesudah',
                    ],

                    'size-options' => [
                        'extra-small' => 'Sangat Kecil',
                        'small'       => 'Kecil',
                        'medium'      => 'Sedang',
                        'large'       => 'Besar',
                    ],
                ],

                'settings' => [
                    'common' => [
                        'align-end'              => 'Rata Akhir',
                        'alignment'              => 'Perataan',
                        'align-start'            => 'Rata Awal',
                        'badge'                  => 'Badge',
                        'boolean'                => 'Boolean',
                        'color'                  => 'Warna',
                        'copyable'               => 'Dapat Disalin',
                        'copy-message'           => 'Pesan Salin',
                        'copy-message-duration'  => 'Durasi Pesan Salin',
                        'default'                => 'Default',
                        'filterable'             => 'Dapat Difilter',
                        'groupable'              => 'Dapat Dikelompokkan',
                        'grow'                   => 'Tumbuh',
                        'icon'                   => 'Ikon',
                        'icon-color'             => 'Warna Ikon',
                        'icon-position'          => 'Posisi Ikon',
                        'label'                  => 'Label',
                        'limit'                  => 'Batas',
                        'line-clamp'             => 'Batas Baris',
                        'money'                  => 'Mata Uang',
                        'placeholder'            => 'Placeholder',
                        'prefix'                 => 'Awalan',
                        'searchable'             => 'Dapat Dicari',
                        'size'                   => 'Ukuran',
                        'sortable'               => 'Dapat Diurutkan',
                        'suffix'                 => 'Akhiran',
                        'toggleable'             => 'Dapat Ditoggle',
                        'tooltip'                => 'Tooltip',
                        'vertical-alignment'     => 'Perataan Vertikal',
                        'vertically-align-start' => 'Rata Vertikal ke Awal',
                        'weight'                 => 'Ketebalan',
                        'width'                  => 'Lebar',
                        'words'                  => 'Kata',
                        'wrap-header'            => 'Header Bungkus',
                        'column-span'            => 'Rentang Kolom',
                        'helper-text'            => 'Teks Bantuan',
                        'hint'                   => 'Petunjuk',
                        'hint-color'             => 'Warna Petunjuk',
                        'hint-icon'              => 'Ikon Petunjuk',
                    ],

                    'datetime' => [
                        'date'              => 'Tanggal',
                        'date-time'         => 'Tanggal Waktu',
                        'date-time-tooltip' => 'Tooltip Tanggal Waktu',
                        'since'             => 'Sejak',
                    ],

                    'checkbox-list' => [
                        'separator'               => 'Pemisah',
                        'list-with-line-breaks'   => 'Daftar dengan Pemisah Baris',
                        'bulleted'                => 'Berpoin',
                        'limit-list'              => 'Batasi Daftar',
                        'expandable-limited-list' => 'Daftar Terbatas yang Dapat Diperluas',
                    ],

                    'select' => [
                        'separator'               => 'Pemisah',
                        'list-with-line-breaks'   => 'Daftar dengan Pemisah Baris',
                        'bulleted'                => 'Berpoin',
                        'limit-list'              => 'Batasi Daftar',
                        'expandable-limited-list' => 'Daftar Terbatas yang Dapat Diperluas',
                    ],

                    'checkbox' => [
                        'boolean'     => 'Boolean',
                        'false-icon'  => 'Ikon Salah',
                        'true-icon'   => 'Ikon Benar',
                        'true-color'  => 'Warna Benar',
                        'false-color' => 'Warna Salah',
                    ],

                    'toggle' => [
                        'boolean'     => 'Boolean',
                        'false-icon'  => 'Ikon Salah',
                        'true-icon'   => 'Ikon Benar',
                        'true-color'  => 'Warna Benar',
                        'false-color' => 'Warna Salah',
                    ],
                ],
            ],

            'settings' => [
                'title' => 'Pengaturan',

                'fields' => [
                    'type'           => 'Tipe',
                    'input-type'     => 'Tipe Input',
                    'is-multiselect' => 'Multi-select',
                    'sort-order'     => 'Urutan',

                    'type-options' => [
                        'text'          => 'Input Teks',
                        'textarea'      => 'Textarea',
                        'select'        => 'Select',
                        'checkbox'      => 'Checkbox',
                        'radio'         => 'Radio',
                        'toggle'        => 'Toggle',
                        'checkbox-list' => 'Daftar Checkbox',
                        'datetime'      => 'Pemilih Tanggal Waktu',
                        'editor'        => 'Editor Teks Kaya',
                        'markdown'      => 'Editor Markdown',
                        'color'         => 'Pemilih Warna',
                    ],

                    'input-type-options' => [
                        'text'     => 'Teks',
                        'email'    => 'Email',
                        'numeric'  => 'Numerik',
                        'integer'  => 'Integer',
                        'password' => 'Kata Sandi',
                        'tel'      => 'Telepon',
                        'url'      => 'URL',
                        'color'    => 'Warna',
                    ],
                ],
            ],

            'resource' => [
                'title' => 'Resource',

                'fields' => [
                    'resource' => 'Resource',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'code'       => 'Kode',
            'name'       => 'Nama',
            'type'       => 'Tipe',
            'resource'   => 'Resource',
            'created-at' => 'Dibuat Pada',
        ],

        'groups' => [],

        'filters' => [
            'type'     => 'Tipe',
            'resource' => 'Resource',

            'type-options' => [
                'text'          => 'Input Teks',
                'textarea'      => 'Textarea',
                'select'        => 'Select',
                'checkbox'      => 'Checkbox',
                'radio'         => 'Radio',
                'toggle'        => 'Toggle',
                'checkbox-list' => 'Daftar Checkbox',
                'datetime'      => 'Pemilih Tanggal Waktu',
                'editor'        => 'Editor Teks Kaya',
                'markdown'      => 'Editor Markdown',
                'color'         => 'Pemilih Warna',
            ],
        ],

        'actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Field dipulihkan',
                    'body'  => 'Field berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Field dihapus',
                    'body'  => 'Field berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Field dihapus permanen',
                    'body'  => 'Field berhasil dihapus permanen.',
                ],
            ],
        ],

        'bulk-actions' => [
            'restore' => [
                'notification' => [
                    'title' => 'Field dipulihkan',
                    'body'  => 'Field berhasil dipulihkan.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'title' => 'Field dihapus',
                    'body'  => 'Field berhasil dihapus.',
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'title' => 'Field dihapus permanen',
                    'body'  => 'Field berhasil dihapus permanen.',
                ],
            ],
        ],
    ],
];
