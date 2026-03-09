<?php

return [
    'config' => [
        'navigation' => [
            'label' => 'Settings',
        ],
    ],

    'resources' => [
        'attendance' => [
            'navigation' => [
                'label' => 'Attendances',
            ],
            'model' => [
                'singular' => 'Attendance',
                'plural'   => 'Attendances',
            ],
            'form' => [
                'sections' => [
                    'user'      => 'User',
                    'schedule'  => 'Schedule',
                    'check_in'  => 'Check In',
                    'check_out' => 'Check Out',
                ],
                'fields' => [
                    'user_id'              => 'Employee',
                    'is_leave'             => 'Is Leave',
                    'schedule_latitude'    => 'Schedule Latitude',
                    'schedule_longitude'   => 'Schedule Longitude',
                    'schedule_start_time'  => 'Schedule Start Time',
                    'schedule_end_time'    => 'Schedule End Time',
                    'start_time'           => 'Check-in Time',
                    'start_latitude'       => 'Check-in Latitude',
                    'start_longitude'      => 'Check-in Longitude',
                    'start_photo_path'     => 'Check-in Photo',
                    'end_time'             => 'Check-out Time',
                    'end_latitude'         => 'Check-out Latitude',
                    'end_longitude'        => 'Check-out Longitude',
                    'end_photo_path'       => 'Check-out Photo',
                ],
            ],
            'table' => [
                'columns' => [
                    'created_at' => 'Date',
                    'user'       => 'Employee',
                    'status'     => 'Status',
                    'start_time' => 'Check-in Time',
                    'end_time'   => 'Check-out Time',
                ],
                'placeholders' => [
                    'end_time' => '-',
                ],
                'statuses' => [
                    'on_time' => 'On Time',
                    'late'    => 'Late',
                ],
                'description' => [
                    'work_duration' => 'Duration: :value',
                ],
            ],
        ],

        'leave' => [
            'navigation' => [
                'label' => 'Leaves',
            ],
            'model' => [
                'singular' => 'Leave',
                'plural'   => 'Leaves',
            ],
            'form' => [
                'sections' => [
                    'detail'   => 'Detail',
                    'approval' => 'Approval',
                ],
                'fields' => [
                    'user_id'    => 'Employee',
                    'type'       => 'Type',
                    'start_date' => 'Start Date',
                    'end_date'   => 'End Date',
                    'reason'     => 'Reason',
                    'status'     => 'Status',
                    'note'       => 'Note',
                    'attachment' => 'Attachment',
                ],
                'options' => [
                    'pending'   => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'izin'     => 'Permit',
                    'sakit'    => 'Sick',
                    'cuti'     => 'Annual Leave',
                ],
            ],
            'table' => [
                'columns' => [
                    'user'       => 'Employee',
                    'type'       => 'Type',
                    'start_date' => 'Start Date',
                    'end_date'   => 'End Date',
                    'reason'     => 'Reason',
                    'status'     => 'Status',
                ],
            ],
        ],

        'office' => [
            'navigation' => [
                'label' => 'Offices',
            ],
            'model' => [
                'singular' => 'Office',
                'plural'   => 'Offices',
            ],
            'form' => [
                'fields' => [
                    'name'      => 'Name',
                    'latitude'  => 'Latitude',
                    'longitude' => 'Longitude',
                    'radius'    => 'Radius',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'      => 'Name',
                    'latitude'  => 'Latitude',
                    'longitude' => 'Longitude',
                    'radius'    => 'Radius',
                ],
            ],
        ],

        'overtime' => [
            'navigation' => [
                'label' => 'Overtimes',
            ],
            'model' => [
                'singular' => 'Overtime',
                'plural'   => 'Overtimes',
            ],
            'form' => [
                'sections' => [
                    'detail'   => 'Detail',
                    'approval' => 'Approval',
                ],
                'fields' => [
                    'user_id'    => 'Employee',
                    'date'       => 'Date',
                    'start_time' => 'Start Time',
                    'end_time'   => 'End Time',
                    'reason'     => 'Reason',
                    'status'     => 'Status',
                    'note'       => 'Note',
                    'attachment' => 'Attachment',
                ],
                'options' => [
                    'pending'   => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ],
            ],
            'table' => [
                'columns' => [
                    'user'       => 'Employee',
                    'date'       => 'Date',
                    'start_time' => 'Start Time',
                    'end_time'   => 'End Time',
                    'reason'     => 'Reason',
                    'status'     => 'Status',
                ],
            ],
        ],

        'schedule' => [
            'navigation' => [
                'label' => 'Schedules',
            ],
            'model' => [
                'singular' => 'Schedule',
                'plural'   => 'Schedules',
            ],
            'form' => [
                'sections' => [
                    'schedule_data' => 'Schedule Data',
                    'settings'      => 'Attendance Settings',
                ],
                'descriptions' => [
                    'schedule_data' => 'Select employee, shift, and office for attendance scheduling.',
                ],
                'fields' => [
                    'user_id'   => 'Employee',
                    'shift_id'  => 'Shift',
                    'office_id' => 'Office',
                    'is_wfa'    => 'Allow WFA',
                    'is_banned' => 'Disable Attendance',
                ],
            ],
            'table' => [
                'columns' => [
                    'user_name'  => 'Name',
                    'user_email' => 'Email',
                    'is_wfa'     => 'WFA',
                    'shift'      => 'Shift',
                    'office'     => 'Office',
                ],
            ],
        ],

        'shift' => [
            'navigation' => [
                'label' => 'Shifts',
            ],
            'model' => [
                'singular' => 'Shift',
                'plural'   => 'Shifts',
            ],
            'form' => [
                'fields' => [
                    'name'       => 'Name',
                    'start_time' => 'Start Time',
                    'end_time'   => 'End Time',
                ],
            ],
            'table' => [
                'columns' => [
                    'name'       => 'Name',
                    'start_time' => 'Start Time',
                    'end_time'   => 'End Time',
                ],
            ],
        ],
    ],

    'relation_managers' => [
        'schedules' => [
            'title' => 'Schedules',
            'form'  => [
                'fields' => [
                    'office_id' => 'Office',
                    'shift_id'  => 'Shift',
                    'is_wfa'    => 'Is WFA',
                    'is_banned' => 'Is Banned',
                ],
            ],
            'table' => [
                'columns' => [
                    'office_name' => 'Office Name',
                    'shift_name'  => 'Shift Name',
                    'is_wfa'      => 'WFA Status',
                    'is_banned'   => 'Banned Status',
                ],
            ],
        ],
    ],
];
