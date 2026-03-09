<?php

use Cesa\Presensi\Filament\Resources\AttendanceResource;
use Cesa\Presensi\Filament\Resources\LeaveResource;
use Cesa\Presensi\Filament\Resources\OfficeResource;
use Cesa\Presensi\Filament\Resources\OvertimeResource;
use Cesa\Presensi\Filament\Resources\ScheduleResource;
use Cesa\Presensi\Filament\Resources\ShiftResource;

return [
    'resources' => [
        'manage' => [
            AttendanceResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            LeaveResource::class      => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            OfficeResource::class     => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            OvertimeResource::class   => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            ScheduleResource::class   => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            ShiftResource::class      => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [],
    ],
];
