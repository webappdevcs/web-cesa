<?php

namespace Cesa\Shelf\Tests\Feature;

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\ApprovalLevelResource;
use Cesa\Shelf\Filament\Resources\AssetLocationResource;
use Cesa\Shelf\Filament\Resources\AssetRequestResource;
use Cesa\Shelf\Filament\Resources\AssetRequestResource\Pages\ViewAssetRequest;
use Cesa\Shelf\Filament\Resources\AssetResource;
use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Cesa\Shelf\Filament\Resources\BrandResource;
use Cesa\Shelf\Filament\Resources\CategoryResource;
use Cesa\Shelf\Filament\Resources\CompanyDocumentSettingResource;
use Cesa\Shelf\Filament\Resources\CustomAssetAttributeResource;
use Cesa\Shelf\Filament\Resources\ShelfResource;
use Cesa\Shelf\Filament\Resources\TaskResource;
use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource;
use Cesa\Shelf\Filament\Resources\VendorResource;
use Cesa\Shelf\Http\Controllers\AssetRequestController;
use Cesa\Shelf\Http\Controllers\PdfController;
use Cesa\Shelf\Models\ApprovalLevel;
use Cesa\Shelf\Models\Asset;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\AssetTransfer;
use Cesa\Shelf\Models\Brand;
use Cesa\Shelf\Models\CompanyDocumentSetting;
use Cesa\Shelf\Models\Task;
use Cesa\Shelf\Models\VehicleChecksheet;
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
use Cesa\Shelf\ShelfPlugin;
use Cesa\Shelf\ShelfServiceProvider;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Webkul\PluginManager\Package;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\Team;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasResourcePermissionQuery;
use Webkul\Support\SupportServiceProvider;

class ShelfPluginSmokeTest extends TestCase
{
    public function test_it_uses_the_shelf_identity(): void
    {
        $this->assertSame('shelf', ShelfServiceProvider::$name);
        $this->assertSame('shelf', app(ShelfPlugin::class)->getId());
    }

    public function test_shelf_service_provider_registers_all_plugin_migrations(): void
    {
        $package = new Package;

        (new ShelfServiceProvider($this->app))->configureCustomPackage($package);

        $expectedMigrations = collect(glob(dirname(__DIR__, 2).'/database/migrations/*.php') ?: [])
            ->map(static fn (string $path): string => basename($path, '.php'))
            ->sort()
            ->values()
            ->all();

        $registeredMigrations = collect($package->migrationFileNames)
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedMigrations, $registeredMigrations);
    }

    public function test_company_foreign_keys_are_deferred_until_support_companies_table_exists(): void
    {
        $supportPackage = new Package;
        $shelfPackage = new Package;

        (new SupportServiceProvider($this->app))->configureCustomPackage($supportPackage);
        (new ShelfServiceProvider($this->app))->configureCustomPackage($shelfPackage);

        $orderedMigrations = collect([
            ...$supportPackage->migrationFileNames,
            ...$shelfPackage->migrationFileNames,
        ])->sort()->values();

        $companyMigrationIndex = $orderedMigrations->search('2024_12_10_092657_create_companies_table');
        $shelfForeignKeyMigrationIndex = $orderedMigrations->search('2024_12_10_092658_add_company_foreign_keys_to_shelf_tables');

        $this->assertIsInt($companyMigrationIndex);
        $this->assertIsInt($shelfForeignKeyMigrationIndex);
        $this->assertTrue($shelfForeignKeyMigrationIndex > $companyMigrationIndex);

        foreach ([
            base_path('plugins/cesa/shelf/database/migrations/2024_07_10_143140_create_assets_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_07_10_155351_create_asset_transfers_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_10_21_135803_create_tasks_table.php'),
        ] as $migrationPath) {
            $migrationContents = file_get_contents($migrationPath);

            $this->assertIsString($migrationContents);
            $this->assertStringNotContainsString("constrained('companies')", $migrationContents, basename($migrationPath));
        }

        $foreignKeyMigrationContents = file_get_contents(
            base_path('plugins/cesa/shelf/database/migrations/2024_12_10_092658_add_company_foreign_keys_to_shelf_tables.php')
        );

        $this->assertIsString($foreignKeyMigrationContents);
        $this->assertStringContainsString("'table'         => 'shelf_assets'", $foreignKeyMigrationContents);
        $this->assertStringContainsString("'column'        => 'company_id'", $foreignKeyMigrationContents);
        $this->assertStringContainsString("'column'        => 'recipient_company_id'", $foreignKeyMigrationContents);
        $this->assertStringContainsString("'table'         => 'shelf_asset_transfers'", $foreignKeyMigrationContents);
        $this->assertStringContainsString("'on_delete'     => 'cascade'", $foreignKeyMigrationContents);
        $this->assertStringContainsString("'table'         => 'shelf_tasks'", $foreignKeyMigrationContents);
    }

    public function test_shelf_performance_migrations_target_live_indexes_only(): void
    {
        $performanceMigrationPath = base_path('plugins/cesa/shelf/database/migrations/2026_03_17_020000_add_performance_indexes_to_shelf_tables.php');
        $cleanupMigrationPath = base_path('plugins/cesa/shelf/database/migrations/2026_03_17_160000_cleanup_redundant_shelf_indexes.php');

        $performanceMigrationContents = file_get_contents($performanceMigrationPath);
        $cleanupMigrationContents = file_get_contents($cleanupMigrationPath);

        $this->assertIsString($performanceMigrationContents);
        $this->assertIsString($cleanupMigrationContents);
        $this->assertStringContainsString("'shelf_vehicle_checksheets'", $performanceMigrationContents);
        $this->assertStringContainsString("'license_plate'", $performanceMigrationContents);
        $this->assertStringContainsString("'pic'", $performanceMigrationContents);
        $this->assertStringContainsString("'location'", $performanceMigrationContents);
        $this->assertStringNotContainsString('shelf_asset_transfer_details_asset_id_index', $performanceMigrationContents);
        $this->assertStringContainsString("'shelf_vehicle_checksheets'", $cleanupMigrationContents);
        $this->assertFileDoesNotExist(
            base_path('plugins/cesa/shelf/database/migrations/2026_03_17_070000_add_creator_indexes_to_permission_scoped_shelf_tables.php')
        );
    }

    public function test_it_can_autoload_shelf_feature_entrypoints(): void
    {
        $classes = [
            Configurations::class,
            ApprovalLevelResource::class,
            AssetLocationResource::class,
            AssetResource::class,
            AssetTransferResource::class,
            BrandResource::class,
            CategoryResource::class,
            CompanyDocumentSettingResource::class,
            CustomAssetAttributeResource::class,
            AssetRequestResource::class,
            ViewAssetRequest::class,
            TaskResource::class,
            VehicleChecksheetResource::class,
            VendorResource::class,
            AssetRequestController::class,
            PdfController::class,
            ShelfResource::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class), "Failed asserting {$class} can be autoloaded.");
        }
    }

    public function test_local_models_use_shelf_prefixed_tables(): void
    {
        $this->assertSame('shelf_assets', (new Asset)->getTable());
        $this->assertSame('shelf_asset_transfers', (new AssetTransfer)->getTable());
        $this->assertSame('shelf_tasks', (new Task)->getTable());
        $this->assertSame('shelf_vehicle_checksheets', (new VehicleChecksheet)->getTable());
        $this->assertSame('shelf_asset_requests', (new AssetRequest)->getTable());
        $this->assertSame('shelf_company_document_settings', (new CompanyDocumentSetting)->getTable());
        $this->assertSame('shelf_approval_levels', (new ApprovalLevel)->getTable());
    }

    public function test_asset_transfer_uses_webkul_company_convention(): void
    {
        $assetTransfer = new AssetTransfer;

        $this->assertContains('company_id', $assetTransfer->getFillable());
        $this->assertContains('transfer_type', $assetTransfer->getFillable());
        $this->assertNotContains('business_entity_id', $assetTransfer->getFillable());
        $this->assertSame('company_id', $assetTransfer->company()->getForeignKeyName());
    }

    public function test_shelf_policies_use_shelf_prefixed_permissions(): void
    {
        $user = new class extends User
        {
            /** @var array<int, string> */
            public array $abilities = [];

            public function can($ability, $arguments = []): bool
            {
                $this->abilities[] = $ability;

                return true;
            }
        };

        (new AssetPolicy)->viewAny($user);
        (new AssetTransferPolicy)->viewAny($user);
        (new ApprovalLevelPolicy)->create($user);
        (new CompanyDocumentSettingPolicy)->update($user, new CompanyDocumentSetting);

        $this->assertSame([
            'view_any_shelf_asset',
            'view_any_shelf_asset::transfer',
            'create_shelf_approval::level',
            'update_shelf_company::document::setting',
        ], $user->abilities);
    }

    public function test_shelf_shield_config_is_merged(): void
    {
        $manage = config('filament-shield.resources.manage', []);
        $excludedPages = config('filament-shield.pages.exclude', []);

        $this->assertArrayHasKey(AssetResource::class, $manage);
        $this->assertArrayHasKey(ApprovalLevelResource::class, $manage);
        $this->assertContains('force_delete', $manage[AssetResource::class]);
        $this->assertContains('reorder', $manage[ApprovalLevelResource::class]);
        $this->assertNotContains('replicate', $manage[AssetResource::class]);
        $this->assertNotContains('replicate', $manage[ApprovalLevelResource::class]);
        $this->assertContains(Configurations::class, $excludedPages);
    }

    public function test_shelf_configurations_abort_when_no_clustered_component_is_accessible(): void
    {
        $cluster = new class extends Configurations
        {
            public static function getClusteredComponents(): array
            {
                return [InaccessibleShelfConfigurationsPage::class];
            }
        };

        try {
            $cluster->mount();
            $this->fail('Expected the shelf configurations cluster to abort with 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_shelf_base_resource_uses_resource_permission_query_trait(): void
    {
        $this->assertContains(HasResourcePermissionQuery::class, class_uses_recursive(ShelfResource::class));
    }

    public function test_shelf_base_resource_falls_back_to_shelf_model_namespace(): void
    {
        $this->assertSame(
            'Cesa\\Shelf\\Models\\ExampleManaged',
            ExampleManagedResource::getModel(),
        );
    }

    public function test_shelf_navigation_matches_other_cesa_plugins(): void
    {
        $topLevelResources = [
            AssetResource::class,
            AssetTransferResource::class,
            AssetRequestResource::class,
            TaskResource::class,
            VehicleChecksheetResource::class,
        ];

        foreach ($topLevelResources as $resourceClass) {
            $this->assertTrue(is_subclass_of($resourceClass, ShelfResource::class));
            $this->assertNull($resourceClass::getCluster());
            $this->assertSame(__('admin.navigation.shelf'), $resourceClass::getNavigationGroup());
            $this->assertNull($resourceClass::getNavigationIcon());
        }

        $configurationResources = [
            ApprovalLevelResource::class,
            AssetLocationResource::class,
            BrandResource::class,
            CategoryResource::class,
            CompanyDocumentSettingResource::class,
            CustomAssetAttributeResource::class,
            VendorResource::class,
        ];

        foreach ($configurationResources as $resourceClass) {
            $this->assertTrue(is_subclass_of($resourceClass, ShelfResource::class));
            $this->assertSame(Configurations::class, $resourceClass::getCluster());
        }

        $this->assertSame(__('admin.navigation.shelf'), Configurations::getNavigationGroup());
        $this->assertSame(__('shelf::app.config.navigation.label'), Configurations::getNavigationLabel());
    }

    public function test_shelf_policies_match_cesa_permission_conventions(): void
    {
        $policyClasses = [
            ApprovalLevelPolicy::class,
            AssetLocationPolicy::class,
            AssetPolicy::class,
            AssetTransferPolicy::class,
            BrandPolicy::class,
            CategoryPolicy::class,
            CompanyDocumentSettingPolicy::class,
            CustomAssetAttributePolicy::class,
            AssetRequestPolicy::class,
            TaskPolicy::class,
            VehicleChecksheetPolicy::class,
            VendorPolicy::class,
        ];

        foreach ($policyClasses as $policyClass) {
            $this->assertFalse(method_exists($policyClass, 'replicate'));
        }
    }

    public function test_task_policy_respects_individual_resource_permission_for_direct_record_access(): void
    {
        $assignedUser = $this->fakeScopedUser(
            id: 10,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['view_shelf_task', 'update_shelf_task'],
        );

        $otherUser = $this->fakeScopedUser(
            id: 11,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: ['update_shelf_task'],
        );

        $task = new Task;
        $task->setRelation('user', $assignedUser);

        $this->assertTrue((new TaskPolicy)->view($assignedUser, $task));
        $this->assertTrue((new TaskPolicy)->update($assignedUser, $task));
        $this->assertFalse((new TaskPolicy)->update($otherUser, $task));
    }

    public function test_brand_policy_respects_group_resource_permission_for_creator_owned_records(): void
    {
        $actor = $this->fakeScopedUser(
            id: 20,
            permissionType: PermissionType::GROUP,
            grantedAbilities: ['update_shelf_brand'],
            teamIds: [7],
        );

        $sameGroupCreator = $this->fakeScopedUser(
            id: 21,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
            teamIds: [7],
        );

        $otherGroupCreator = $this->fakeScopedUser(
            id: 22,
            permissionType: PermissionType::INDIVIDUAL,
            grantedAbilities: [],
            teamIds: [8],
        );

        $brand = new Brand;
        $brand->setRelation('creator', $sameGroupCreator);

        $this->assertTrue((new BrandPolicy)->update($actor, $brand));

        $brand->setRelation('creator', $otherGroupCreator);

        $this->assertFalse((new BrandPolicy)->update($actor, $brand));
    }

    /**
     * @param  array<int, string>  $grantedAbilities
     * @param  array<int, int>  $teamIds
     */
    private function fakeScopedUser(int $id, PermissionType $permissionType, array $grantedAbilities, array $teamIds = []): User
    {
        $user = new class extends User
        {
            /** @var array<int, string> */
            public array $grantedAbilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->grantedAbilities, true);
            }
        };

        $user->id = $id;
        $user->resource_permission = $permissionType;
        $user->grantedAbilities = $grantedAbilities;
        $user->setRelation('teams', new EloquentCollection(
            array_map(function (int $teamId): Team {
                $team = new Team;
                $team->id = $teamId;

                return $team;
            }, $teamIds),
        ));

        return $user;
    }
}

class InaccessibleShelfConfigurationsPage extends Page
{
    public static function canAccess(): bool
    {
        return false;
    }
}

class ExampleManagedResource extends ShelfResource {}
