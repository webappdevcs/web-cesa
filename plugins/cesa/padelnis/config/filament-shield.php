<?php

use Cesa\Padelnis\Filament\Resources\CatalogItemResource;
use Cesa\Padelnis\Filament\Resources\CoachResource;
use Cesa\Padelnis\Filament\Resources\CourtResource;
use Cesa\Padelnis\Filament\Resources\ReservationResource;
use Cesa\Padelnis\Filament\Resources\SpecialPriceResource;
use Cesa\Padelnis\Filament\Resources\TransactionTypeResource;

return [
    'resources' => [
        'manage' => [
            CatalogItemResource::class     => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            CoachResource::class           => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            CourtResource::class           => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            ReservationResource::class     => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            SpecialPriceResource::class    => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
            TransactionTypeResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [],
    ],
];
