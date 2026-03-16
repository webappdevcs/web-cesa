<?php

use Cesa\Helpdesk\Filament\Clusters\Configurations;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\PriorityResource;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\ProblemCategoryResource;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\TicketStatusResource;
use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\UnitResource;
use Cesa\Helpdesk\Filament\Resources\TicketResource;

return [
    'resources' => [
        'manage' => [
            TicketResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            PriorityResource::class        => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            TicketStatusResource::class    => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            UnitResource::class            => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            ProblemCategoryResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],
];
