<?php

return [
    'name' => 'Penggajian',

    'navigation' => [
        'group' => 'Penggajian',
    ],

    'resources' => [
        'payroll_period' => [
            'navigation' => [
                'label' => 'Periode Penggajian',
            ],
            'model' => [
                'singular' => 'Periode Penggajian',
                'plural'   => 'Periode Penggajian',
            ],
            'form' => [
                'sections' => [
                    'period_details' => 'Detail Periode',
                ],
                'fields' => [
                    'name'                  => 'Nama',
                    'start_date'            => 'Tanggal Mulai',
                    'end_date'              => 'Tanggal Selesai',
                    'status'                => 'Status',
                    'auto_generate'         => 'Otomatis Buat Penggajian',
                    'auto_generate_helper'  => 'Buat penggajian otomatis setelah periode dibuat hanya untuk karyawan yang punya data presensi atau lembur disetujui. Hilangkan centang untuk membuat manual nanti.',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'       => 'Nama',
                    'start_date' => 'Tanggal Mulai',
                    'end_date'   => 'Tanggal Selesai',
                    'status'     => 'Status',
                    'created_at' => 'Dibuat Pada',
                ],
                'actions' => [
                    'generate_payroll'         => 'Buat Penggajian',
                    'mark_as_paid'             => 'Tandai Sudah Dibayar',
                    'mark_as_paid_description' => 'Apakah Anda yakin? Ini akan menandai periode penggajian sebagai sudah dibayar. Tindakan ini tidak dapat dibatalkan.',
                ],
            ],
        ],

        'payroll_record' => [
            'navigation' => [
                'label' => 'Data Penggajian',
            ],
            'model' => [
                'singular' => 'Data Penggajian',
                'plural'   => 'Data Penggajian',
            ],
            'form' => [
                'sections' => [
                    'record_details' => 'Detail Data',
                    'earnings'       => 'Pendapatan',
                    'deductions'     => 'Potongan',
                ],
                'fields' => [
                    'user_id'               => 'Karyawan',
                    'payroll_period_id'     => 'Periode',
                    'total_attendance_days' => 'Total Hari Hadir',
                    'total_overtime_hours'  => 'Total Jam Lembur',
                    'total_late_minutes'    => 'Total Menit Terlambat',
                    'gross_salary'          => 'Gaji Kotor',
                    'total_penalties'       => 'Total Denda',
                    'net_salary'            => 'Gaji Bersih',
                ],
            ],
            'table' => [
                'columns' => [
                    'employee'         => 'Karyawan',
                    'period'           => 'Periode',
                    'base_salary'      => 'Gaji Pokok',
                    'late_penalty'     => 'Denda Keterlambatan',
                    'gross_salary'     => 'Gaji Kotor',
                    'total_penalties'  => 'Total Denda',
                    'net_salary'       => 'Gaji Bersih',
                    'created_at'       => 'Dibuat Pada',
                ],
            ],
            'infolist' => [
                'sections' => [
                    'record_details'      => 'Detail Data',
                    'financials'          => 'Keuangan',
                    'calculation_details' => 'Detail Perhitungan',
                    'penalties_breakdown' => 'Rincian Denda',
                ],
                'entries' => [
                    'employee'              => 'Karyawan',
                    'period'                => 'Periode',
                    'attendance_days'       => 'Hari Hadir',
                    'overtime_hours'        => 'Jam Lembur',
                    'late_minutes'          => 'Menit Terlambat',
                    'gross_salary'          => 'Gaji Kotor',
                    'total_penalties'       => 'Total Denda',
                    'net_salary'            => 'Gaji Bersih',
                    'daily_wage'            => 'Gaji Harian',
                    'overtime_rate'         => 'Tarif Lembur',
                    'basic_salary'          => 'Gaji Dasar',
                    'overtime_salary'       => 'Gaji Lembur',
                    'date'                  => 'Tanggal',
                    'minutes_late'          => 'Menit Terlambat',
                    'penalty_amount'        => 'Jumlah Denda',
                    'late_penalties'        => 'Denda Keterlambatan',
                ],
            ],
        ],
    ],

    'pages' => [
        'manage_settings' => [
            'navigation' => [
                'label' => 'Denda & Gaji',
            ],
            'sections' => [
                'wage_settings'            => 'Pengaturan Gaji',
                'late_penalty_settings'    => 'Pengaturan Denda Keterlambatan',
                'late_penalty_description' => 'Konfigurasikan denda untuk keterlambatan.',
            ],
            'fields' => [
                'daily_wage'                   => 'Gaji Harian',
                'overtime_hourly_rate'         => 'Tarif Per Jam Lembur',
                'late_penalty_tier_1_min'      => 'Tier 1 Menit Minimum',
                'late_penalty_tier_1_amount'   => 'Tier 1 Nominal Denda',
                'late_penalty_tier_2_min'      => 'Tier 2 Menit Minimum',
                'late_penalty_tier_2_amount'   => 'Tier 2 Nominal Denda',
                'late_penalty_tier_3_percent'  => 'Tier 3 Persentase Denda (> 30 menit)',
            ],
        ],
    ],

    'enums' => [
        'status' => [
            'open'   => 'Terbuka',
            'locked' => 'Terkunci',
            'paid'   => 'Dibayar',
        ],
    ],

    'notifications' => [
        'payroll_generated' => [
            'title' => 'Berhasil',
            'body'  => 'Penggajian berhasil dibuat.',
        ],
        'marked_as_paid' => [
            'title' => 'Berhasil',
            'body'  => 'Periode penggajian telah ditandai sebagai sudah dibayar.',
        ],
    ],
];
