<?php

use Cesa\ExitClearance\Filament\Clusters\Configurations;
use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource;
use Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource;
use Cesa\ExitClearance\Filament\Resources\RequestResource;

return [
    'resources' => [
        'manage' => [
            RequestResource::class    => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            DepartmentResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            ApproverResource::class   => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],
];
