<?php

return [
    'name' => 'Payroll',

    'navigation' => [
        'group' => 'Payroll',
    ],

    'resources' => [
        'payroll_period' => [
            'navigation' => [
                'label' => 'Payroll Periods',
            ],
            'model' => [
                'singular' => 'Payroll Period',
                'plural'   => 'Payroll Periods',
            ],
            'form' => [
                'sections' => [
                    'period_details' => 'Period Details',
                ],
                'fields' => [
                    'name'                  => 'Name',
                    'start_date'            => 'Start Date',
                    'end_date'              => 'End Date',
                    'status'                => 'Status',
                    'auto_generate'         => 'Automatically Generate Payroll',
                    'auto_generate_helper'  => 'Generate payroll immediately after creating the period for employees who have attendance or approved overtime data. Uncheck to generate manually later.',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'       => 'Name',
                    'start_date' => 'Start Date',
                    'end_date'   => 'End Date',
                    'status'     => 'Status',
                    'created_at' => 'Created At',
                ],
                'actions' => [
                    'generate_payroll'         => 'Generate Payroll',
                    'mark_as_paid'             => 'Mark as Paid',
                    'mark_as_paid_description' => 'Are you sure? This will mark this payroll period as paid. This action cannot be undone.',
                ],
            ],
        ],

        'payroll_record' => [
            'navigation' => [
                'label' => 'Payroll Records',
            ],
            'model' => [
                'singular' => 'Payroll Record',
                'plural'   => 'Payroll Records',
            ],
            'form' => [
                'sections' => [
                    'record_details' => 'Record Details',
                    'earnings'       => 'Earnings',
                    'deductions'     => 'Deductions',
                ],
                'fields' => [
                    'user_id'               => 'Employee',
                    'payroll_period_id'     => 'Period',
                    'total_attendance_days' => 'Total Attendance Days',
                    'total_overtime_hours'  => 'Total Overtime Hours',
                    'total_late_minutes'    => 'Total Late Minutes',
                    'gross_salary'          => 'Gross Salary',
                    'total_penalties'       => 'Total Penalties',
                    'net_salary'            => 'Net Salary',
                ],
            ],
            'table' => [
                'columns' => [
                    'employee'         => 'Employee',
                    'period'           => 'Period',
                    'base_salary'      => 'Base Salary',
                    'late_penalty'     => 'Late Penalty',
                    'gross_salary'     => 'Gross Salary',
                    'total_penalties'  => 'Total Penalties',
                    'net_salary'       => 'Net Salary',
                    'created_at'       => 'Created At',
                ],
            ],
            'infolist' => [
                'sections' => [
                    'record_details'      => 'Record Details',
                    'financials'          => 'Financials',
                    'calculation_details' => 'Calculation Details',
                    'penalties_breakdown' => 'Penalties Breakdown',
                ],
                'entries' => [
                    'employee'              => 'Employee',
                    'period'                => 'Period',
                    'attendance_days'       => 'Attendance Days',
                    'overtime_hours'        => 'Overtime Hours',
                    'late_minutes'          => 'Late Minutes',
                    'gross_salary'          => 'Gross Salary',
                    'total_penalties'       => 'Total Penalties',
                    'net_salary'            => 'Net Salary',
                    'daily_wage'            => 'Daily Wage',
                    'overtime_rate'         => 'Overtime Rate',
                    'basic_salary'          => 'Basic Salary',
                    'overtime_salary'       => 'Overtime Salary',
                    'date'                  => 'Date',
                    'minutes_late'          => 'Minutes Late',
                    'penalty_amount'        => 'Penalty Amount',
                    'late_penalties'        => 'Late Penalties',
                ],
            ],
        ],
    ],

    'pages' => [
        'manage_settings' => [
            'navigation' => [
                'label' => 'Penalty & Wage',
            ],
            'sections' => [
                'wage_settings'            => 'Wage Settings',
                'late_penalty_settings'    => 'Late Penalty Settings',
                'late_penalty_description' => 'Configure penalties for late attendance.',
            ],
            'fields' => [
                'daily_wage'                   => 'Daily Wage',
                'overtime_hourly_rate'         => 'Overtime Hourly Rate',
                'late_penalty_tier_1_min'      => 'Tier 1 Minimum Minutes',
                'late_penalty_tier_1_amount'   => 'Tier 1 Penalty Amount',
                'late_penalty_tier_2_min'      => 'Tier 2 Minimum Minutes',
                'late_penalty_tier_2_amount'   => 'Tier 2 Penalty Amount',
                'late_penalty_tier_3_percent'  => 'Tier 3 Penalty Percentage (> 30 mins)',
            ],
        ],
    ],

    'enums' => [
        'status' => [
            'open'   => 'Open',
            'locked' => 'Locked',
            'paid'   => 'Paid',
        ],
    ],

    'notifications' => [
        'payroll_generated' => [
            'title' => 'Success',
            'body'  => 'Payroll generated successfully.',
        ],
        'marked_as_paid' => [
            'title' => 'Success',
            'body'  => 'Payroll period has been marked as paid.',
        ],
    ],
];
