<?php

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\ApprovalLevelResource;
use Cesa\Shelf\Filament\Resources\AssetLocationResource;
use Cesa\Shelf\Filament\Resources\AssetRequestResource;
use Cesa\Shelf\Filament\Resources\AssetResource;
use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Cesa\Shelf\Filament\Resources\BrandResource;
use Cesa\Shelf\Filament\Resources\CategoryResource;
use Cesa\Shelf\Filament\Resources\CompanyDocumentSettingResource;
use Cesa\Shelf\Filament\Resources\CustomAssetAttributeResource;
use Cesa\Shelf\Filament\Resources\TaskResource;
use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource;
use Cesa\Shelf\Filament\Resources\VendorResource;

return [
    'resources' => [
        'manage' => [
            ApprovalLevelResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            AssetLocationResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            AssetResource::class                  => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            AssetTransferResource::class          => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            BrandResource::class                  => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            CategoryResource::class               => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            CompanyDocumentSettingResource::class => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            CustomAssetAttributeResource::class   => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            AssetRequestResource::class           => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            TaskResource::class                   => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            VehicleChecksheetResource::class      => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
            VendorResource::class                 => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'reorder'],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],

    'widgets' => [
        'exclude' => [],
    ],
];
