<?php

use Cesa\Payroll\Filament\Resources\PayrollPeriodResource;
use Cesa\Payroll\Filament\Resources\PayrollRecordResource;

return [
    'resources' => [
        'manage' => [
            PayrollPeriodResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            PayrollRecordResource::class => ['view_any', 'view', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [],
    ],
];
