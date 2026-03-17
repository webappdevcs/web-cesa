<?php

namespace Cesa\Shelf;

use Cesa\Shelf\Livewire\PublicAssetRequestApprovalPage;
use Cesa\Shelf\Livewire\PublicAssetRequestForm;
use Cesa\Shelf\Livewire\PublicAssetRequestIndex;
use Cesa\Shelf\Livewire\PublicAssetRequestProgressPage;
use Cesa\Shelf\Models\ApprovalLevel;
use Cesa\Shelf\Models\Asset;
use Cesa\Shelf\Models\AssetLocation;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\AssetTransfer;
use Cesa\Shelf\Models\Brand;
use Cesa\Shelf\Models\Category;
use Cesa\Shelf\Models\CompanyDocumentSetting;
use Cesa\Shelf\Models\CustomAssetAttribute;
use Cesa\Shelf\Models\Task;
use Cesa\Shelf\Models\VehicleChecksheet;
use Cesa\Shelf\Models\Vendor;
use Cesa\Shelf\Policies\ApprovalLevelPolicy;
use Cesa\Shelf\Policies\AssetLocationPolicy;
use Cesa\Shelf\Policies\AssetPolicy;
use Cesa\Shelf\Policies\AssetRequestPolicy;
use Cesa\Shelf\Policies\AssetTransferPolicy;
use Cesa\Shelf\Policies\BrandPolicy;
use Cesa\Shelf\Policies\CategoryPolicy;
use Cesa\Shelf\Policies\CompanyDocumentSettingPolicy;
use Cesa\Shelf\Policies\CustomAssetAttributePolicy;
use Cesa\Shelf\Policies\TaskPolicy;
use Cesa\Shelf\Policies\VehicleChecksheetPolicy;
use Cesa\Shelf\Policies\VendorPolicy;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class ShelfServiceProvider extends PackageServiceProvider
{
    public static string $name = 'shelf';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasDependencies(['kepegawaian'])
            ->hasMigrations([
                '2024_07_08_133816_create_categories_table',
                '2024_07_10_141644_create_brands_table',
                '2024_07_10_142706_create_asset_locations_table',
                '2024_07_10_143140_create_assets_table',
                '2024_07_10_155351_create_asset_transfers_table',
                '2024_07_11_104706_create_asset_transfer_details_table',
                '2024_10_21_135802_create_vendors_table',
                '2024_10_21_135803_create_tasks_table',
                '2024_11_12_111750_create_vehicle_checksheets_table',
                '2024_11_14_101451_create_custom_asset_attributes_table',
                '2024_11_14_101453_create_asset_attributes_table',
                '2024_12_10_092658_add_company_foreign_keys_to_shelf_tables',
                '2026_03_09_000001_create_asset_requests_table',
                '2026_03_09_234746_create_approval_levels_table',
                '2026_03_09_234746_create_request_approvals_table',
                '2026_03_17_010000_create_company_document_settings_table',
                '2026_03_17_020000_add_performance_indexes_to_shelf_tables',
                '2026_03_17_065802_add_resource_permission_support_to_shelf_tables',
                '2026_03_17_070000_add_creator_indexes_to_permission_scoped_shelf_tables',
                '2026_03_17_150000_add_soft_deletes_to_shelf_tables',
            ])
            ->runsMigrations()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('shelf');
    }

    public function packageBooted(): void
    {
        if (! ($this->package->isCore || $this->package->isInstalled())) {
            return;
        }

        Livewire::component('cesa.shelf.livewire.public-asset-request-index', PublicAssetRequestIndex::class);
        Livewire::component('cesa.shelf.livewire.public-asset-request-form', PublicAssetRequestForm::class);
        Livewire::component('cesa.shelf.livewire.public-asset-request-progress', PublicAssetRequestProgressPage::class);
        Livewire::component('cesa.shelf.livewire.public-asset-request-approval', PublicAssetRequestApprovalPage::class);

        Gate::policy(ApprovalLevel::class, ApprovalLevelPolicy::class);
        Gate::policy(AssetLocation::class, AssetLocationPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(AssetTransfer::class, AssetTransferPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(CompanyDocumentSetting::class, CompanyDocumentSettingPolicy::class);
        Gate::policy(CustomAssetAttribute::class, CustomAssetAttributePolicy::class);
        Gate::policy(AssetRequest::class, AssetRequestPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(VehicleChecksheet::class, VehicleChecksheetPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ShelfPlugin::make());
        });
    }
}
