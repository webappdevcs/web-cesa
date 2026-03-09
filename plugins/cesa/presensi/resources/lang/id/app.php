<?php

return [
    'config' => [
        'navigation' => [
            'label' => 'Pengaturan',
        ],
    ],

    'resources' => [
        'attendance' => [
            'navigation' => [
                'label' => 'Presensi',
            ],
            'model' => [
                'singular' => 'Presensi',
                'plural'   => 'Presensi',
            ],
            'form' => [
                'sections' => [
                    'user'      => 'Pengguna',
                    'schedule'  => 'Jadwal',
                    'check_in'  => 'Absen Masuk',
                    'check_out' => 'Absen Pulang',
                ],
                'fields' => [
                    'user_id'              => 'Pegawai',
                    'is_leave'             => 'Izin/Cuti',
                    'schedule_latitude'    => 'Latitude Jadwal',
                    'schedule_longitude'   => 'Longitude Jadwal',
                    'schedule_start_time'  => 'Waktu Mulai Jadwal',
                    'schedule_end_time'    => 'Waktu Selesai Jadwal',
                    'start_time'           => 'Waktu Absen Masuk',
                    'start_latitude'       => 'Latitude Absen Masuk',
                    'start_longitude'      => 'Longitude Absen Masuk',
                    'start_photo_path'     => 'Foto Absen Masuk',
                    'end_time'             => 'Waktu Absen Pulang',
                    'end_latitude'         => 'Latitude Absen Pulang',
                    'end_longitude'        => 'Longitude Absen Pulang',
                    'end_photo_path'       => 'Foto Absen Pulang',
                ],
            ],
            'table' => [
                'columns' => [
                    'created_at' => 'Tanggal',
                    'user'       => 'Pegawai',
                    'status'     => 'Status',
                    'start_time' => 'Waktu Datang',
                    'end_time'   => 'Waktu Pulang',
                ],
                'placeholders' => [
                    'end_time' => '-',
                ],
                'statuses' => [
                    'on_time' => 'Tepat Waktu',
                    'late'    => 'Terlambat',
                ],
                'description' => [
                    'work_duration' => 'Durasi: :value',
                ],
            ],
        ],

        'leave' => [
            'navigation' => [
                'label' => 'Cuti dan Izin',
            ],
            'model' => [
                'singular' => 'Cuti dan Izin',
                'plural'   => 'Cuti dan Izin',
            ],
            'form' => [
                'sections' => [
                    'detail'   => 'Detail',
                    'approval' => 'Persetujuan',
                ],
                'fields' => [
                    'user_id'    => 'Pegawai',
                    'type'       => 'Jenis',
                    'start_date' => 'Tanggal Mulai',
                    'end_date'   => 'Tanggal Selesai',
                    'reason'     => 'Alasan',
                    'status'     => 'Status',
                    'note'       => 'Catatan',
                    'attachment' => 'Lampiran',
                ],
                'options' => [
                    'pending'   => 'Menunggu',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'izin'     => 'Izin',
                    'sakit'    => 'Sakit',
                    'cuti'     => 'Cuti',
                ],
            ],
            'table' => [
                'columns' => [
                    'user'       => 'Pegawai',
                    'type'       => 'Jenis',
                    'start_date' => 'Tanggal Mulai',
                    'end_date'   => 'Tanggal Selesai',
                    'reason'     => 'Alasan',
                    'status'     => 'Status',
                ],
            ],
        ],

        'office' => [
            'navigation' => [
                'label' => 'Kantor',
            ],
            'model' => [
                'singular' => 'Kantor',
                'plural'   => 'Kantor',
            ],
            'form' => [
                'fields' => [
                    'name'      => 'Nama',
                    'latitude'  => 'Latitude',
                    'longitude' => 'Longitude',
                    'radius'    => 'Radius',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'      => 'Nama',
                    'latitude'  => 'Latitude',
                    'longitude' => 'Longitude',
                    'radius'    => 'Radius',
                ],
            ],
        ],

        'overtime' => [
            'navigation' => [
                'label' => 'Lembur',
            ],
            'model' => [
                'singular' => 'Lembur',
                'plural'   => 'Lembur',
            ],
            'form' => [
                'sections' => [
                    'detail'   => 'Detail',
                    'approval' => 'Persetujuan',
                ],
                'fields' => [
                    'user_id'    => 'Pegawai',
                    'date'       => 'Tanggal',
                    'start_time' => 'Waktu Mulai',
                    'end_time'   => 'Waktu Selesai',
                    'reason'     => 'Alasan',
                    'status'     => 'Status',
                    'note'       => 'Catatan',
                    'attachment' => 'Lampiran',
                ],
                'options' => [
                    'pending'   => 'Menunggu',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                ],
            ],
            'table' => [
                'columns' => [
                    'user'       => 'Pegawai',
                    'date'       => 'Tanggal',
                    'start_time' => 'Waktu Mulai',
                    'end_time'   => 'Waktu Selesai',
                    'reason'     => 'Alasan',
                    'status'     => 'Status',
                ],
            ],
        ],

        'schedule' => [
            'navigation' => [
                'label' => 'Jadwal',
            ],
            'model' => [
                'singular' => 'Jadwal',
                'plural'   => 'Jadwal',
            ],
            'form' => [
                'sections' => [
                    'schedule_data' => 'Data Jadwal',
                    'settings'      => 'Pengaturan Presensi',
                ],
                'descriptions' => [
                    'schedule_data' => 'Pilih pegawai, shift, dan kantor untuk penjadwalan presensi.',
                ],
                'fields' => [
                    'user_id'   => 'Pegawai',
                    'shift_id'  => 'Shift',
                    'office_id' => 'Kantor',
                    'is_wfa'    => 'Izinkan WFA',
                    'is_banned' => 'Nonaktifkan Presensi',
                ],
            ],
            'table' => [
                'columns' => [
                    'user_name'  => 'Nama',
                    'user_email' => 'Email',
                    'is_wfa'     => 'WFA',
                    'shift'      => 'Shift',
                    'office'     => 'Kantor',
                ],
            ],
        ],

        'shift' => [
            'navigation' => [
                'label' => 'Shift',
            ],
            'model' => [
                'singular' => 'Shift',
                'plural'   => 'Shift',
            ],
            'form' => [
                'fields' => [
                    'name'       => 'Nama',
                    'start_time' => 'Waktu Mulai',
                    'end_time'   => 'Waktu Selesai',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'       => 'Nama',
                    'start_time' => 'Waktu Mulai',
                    'end_time'   => 'Waktu Selesai',
                ],
            ],
        ],
    ],

    'relation_managers' => [
        'schedules' => [
            'title' => 'Jadwal',
            'form'  => [
                'fields' => [
                    'office_id' => 'Kantor',
                    'shift_id'  => 'Shift',
                    'is_wfa'    => 'WFA',
                    'is_banned' => 'Diblokir',
                ],
            ],
            'table' => [
                'columns' => [
                    'office_name' => 'Nama Kantor',
                    'shift_name'  => 'Nama Shift',
                    'is_wfa'      => 'Status WFA',
                    'is_banned'   => 'Status Blokir',
                ],
            ],
        ],
    ],
];
