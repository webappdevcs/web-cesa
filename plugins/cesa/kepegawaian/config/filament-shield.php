<?php

use Cesa\Kepegawaian\Filament\Clusters\Configurations;
use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\ActivityPlanResource;
use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\CalendarResource;
use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\DepartureReasonResource;
use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\EmployeeCategoryResource;
use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\EmploymentTypeResource;
use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\JobPositionResource;
use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\WorkLocationResource;
use Cesa\Kepegawaian\Filament\Resources\DepartmentResource;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource;

$permissions = [
    'BASIC'       => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
    'REORDER'     => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'reorder'],
    'SOFT_DELETE' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'restore', 'force_delete', 'force_delete_any', 'restore_any'],
    'FULL'        => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'restore', 'force_delete', 'force_delete_any', 'restore_any', 'reorder'],
];

return [
    'resources' => [
        'manage' => [
            EmployeeResource::class         => $permissions['SOFT_DELETE'],
            DepartmentResource::class       => $permissions['SOFT_DELETE'],
            ActivityPlanResource::class     => $permissions['SOFT_DELETE'],
            CalendarResource::class         => $permissions['SOFT_DELETE'],
            DepartureReasonResource::class  => $permissions['REORDER'],
            EmployeeCategoryResource::class => $permissions['BASIC'],
            WorkLocationResource::class     => $permissions['SOFT_DELETE'],
            EmploymentTypeResource::class   => $permissions['REORDER'],
            JobPositionResource::class      => $permissions['FULL'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],

];
