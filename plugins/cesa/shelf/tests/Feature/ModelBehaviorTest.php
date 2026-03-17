<?php

namespace Cesa\Shelf\Tests\Feature;

use Cesa\Shelf\Enums\AssetCondition;
use Cesa\Shelf\Models\ApprovalLevel;
use Cesa\Shelf\Models\Asset;
use Cesa\Shelf\Models\AssetAttribute;
use Cesa\Shelf\Models\AssetLocation;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\AssetTransfer;
use Cesa\Shelf\Models\AssetTransferDetail;
use Cesa\Shelf\Models\Brand;
use Cesa\Shelf\Models\Category;
use Cesa\Shelf\Models\CompanyDocumentSetting;
use Cesa\Shelf\Models\CustomAssetAttribute;
use Cesa\Shelf\Models\JobTitle;
use Cesa\Shelf\Models\RequestApproval;
use Cesa\Shelf\Models\Task;
use Cesa\Shelf\Models\User;
use Cesa\Shelf\Models\VehicleChecksheet;
use Cesa\Shelf\Models\Vendor;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ModelBehaviorTest extends TestCase
{
    public function test_shelf_models_allow_mass_assigning_creator_id(): void
    {
        $asset = new Asset;
        $task = new Task;

        $this->assertContains('creator_id', $asset->getFillable());
        $this->assertContains('creator_id', $task->getFillable());
    }

    public function test_task_allows_mass_assigning_user_id(): void
    {
        $task = new Task;

        $this->assertContains('user_id', $task->getFillable());
    }

    public function test_assets_and_tasks_use_company_column_names(): void
    {
        $asset = new Asset;
        $task = new Task;

        $this->assertContains('company_id', $asset->getFillable());
        $this->assertContains('recipient_company_id', $asset->getFillable());
        $this->assertNotContains('business_entity_id', $asset->getFillable());
        $this->assertNotContains('recipient_business_entity_id', $asset->getFillable());
        $this->assertSame('company_id', $asset->company()->getForeignKeyName());
        $this->assertSame('recipient_company_id', $asset->recipientCompany()->getForeignKeyName());

        $this->assertContains('company_id', $task->getFillable());
        $this->assertNotContains('business_entity_id', $task->getFillable());
        $this->assertSame('company_id', $task->company()->getForeignKeyName());
    }

    public function test_shelf_models_only_expose_company_relations(): void
    {
        $this->assertTrue(method_exists(new Asset, 'company'));
        $this->assertTrue(method_exists(new Task, 'company'));
        $this->assertTrue(method_exists(new User, 'company'));
        $this->assertTrue(method_exists(new AssetTransfer, 'company'));

        $this->assertFalse(method_exists(new Asset, 'badanusaha'));
        $this->assertFalse(method_exists(new Asset, 'businessEntity'));
        $this->assertFalse(method_exists(new Task, 'badanusaha'));
        $this->assertFalse(method_exists(new Task, 'businessEntity'));
        $this->assertFalse(method_exists(new User, 'badanusaha'));
        $this->assertFalse(method_exists(new User, 'businessEntity'));
        $this->assertFalse(method_exists(new AssetTransfer, 'badanusaha'));
        $this->assertFalse(method_exists(new AssetTransfer, 'businessEntity'));
    }

    public function test_task_attachment_files_round_trip_multiple_uploads(): void
    {
        $task = new Task;
        $task->attachment = ['task/first.png', 'task/second.png'];

        $this->assertSame(
            '["task/first.png","task/second.png"]',
            $task->getAttributes()['attachment'],
        );

        $this->assertSame(
            ['task/first.png', 'task/second.png'],
            $task->attachment_files,
        );
    }

    public function test_task_attachment_files_support_legacy_single_file_values(): void
    {
        $task = new Task;
        $task->forceFill([
            'attachment' => 'task/legacy.png',
        ]);

        $this->assertSame(['task/legacy.png'], $task->attachment_files);
    }

    public function test_task_attachment_preview_formatter_handles_legacy_single_file_state(): void
    {
        $formatted = $this->formatTaskAttachmentPreview('https://example.com/task/legacy.png');

        $this->assertInstanceOf(Htmlable::class, $formatted);
        $this->assertStringContainsString('https://example.com/task/legacy.png', $formatted->toHtml());
    }

    public function test_asset_item_age_is_null_without_purchase_date(): void
    {
        $asset = new Asset;

        $this->assertNull($asset->item_age);
    }

    public function test_asset_is_available_accessor_returns_boolean(): void
    {
        $asset = new Asset;
        $asset->condition_status = AssetCondition::Available;

        $this->assertTrue($asset->is_available);

        $asset->condition_status = AssetCondition::Transferred;

        $this->assertFalse($asset->is_available);
    }

    public function test_asset_transfer_exposes_consistent_status_options(): void
    {
        $this->assertSame([
            AssetTransfer::STATUS_HANDOVER     => AssetTransfer::STATUS_HANDOVER,
            AssetTransfer::STATUS_REASSIGNMENT => AssetTransfer::STATUS_REASSIGNMENT,
            AssetTransfer::STATUS_RETURN       => AssetTransfer::STATUS_RETURN,
        ], AssetTransfer::statusOptions());

        $this->assertSame([
            AssetTransfer::TYPE_HANDOVER     => AssetTransfer::STATUS_HANDOVER,
            AssetTransfer::TYPE_REASSIGNMENT => AssetTransfer::STATUS_REASSIGNMENT,
            AssetTransfer::TYPE_RETURN       => AssetTransfer::STATUS_RETURN,
        ], AssetTransfer::transferTypeOptions());
    }

    public function test_asset_transfer_status_scope_filters_via_relations_not_missing_status_column(): void
    {
        $query = AssetTransfer::query()->statusLabel(AssetTransfer::STATUS_HANDOVER);
        $sql = $query->toSql();

        $this->assertStringNotContainsString('`shelf_asset_transfers`.`status`', $sql);
        $this->assertStringNotContainsString('"shelf_asset_transfers"."status"', $sql);
        $this->assertStringContainsString('transfer_type', $sql);
        $this->assertContains(AssetTransfer::TYPE_HANDOVER, $query->getBindings());
    }

    public function test_asset_transfer_status_uses_explicit_transfer_type(): void
    {
        $transfer = new AssetTransfer;
        $transfer->transfer_type = AssetTransfer::TYPE_RETURN;

        $this->assertSame(AssetTransfer::STATUS_RETURN, $transfer->status);
    }

    public function test_asset_transfer_status_is_unknown_without_transfer_type(): void
    {
        $transfer = new AssetTransfer;
        $transfer->transfer_type = null;

        $this->assertSame(AssetTransfer::STATUS_UNKNOWN, $transfer->status);
    }

    public function test_shelf_models_use_soft_deletes(): void
    {
        $modelClasses = [
            ApprovalLevel::class,
            Asset::class,
            AssetAttribute::class,
            AssetLocation::class,
            AssetRequest::class,
            AssetTransfer::class,
            AssetTransferDetail::class,
            Brand::class,
            Category::class,
            CompanyDocumentSetting::class,
            CustomAssetAttribute::class,
            JobTitle::class,
            RequestApproval::class,
            Task::class,
            User::class,
            Vendor::class,
            VehicleChecksheet::class,
        ];

        foreach ($modelClasses as $modelClass) {
            $this->assertContains(SoftDeletes::class, class_uses_recursive($modelClass), $modelClass);
        }
    }

    public function test_shelf_user_selectable_query_excludes_default_users(): void
    {
        $sql = User::selectableQuery()->toSql();

        $this->assertStringContainsString('is_default', $sql);
        $this->assertStringContainsString('= ?', $sql);
        $this->assertContains(false, User::selectableQuery()->getBindings());
    }

    public function test_shelf_relations_include_trashed_related_models_by_default(): void
    {
        $this->assertRelationQueryDoesNotFilterDeletedAt(new ApprovalLevel, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new ApprovalLevel, 'requestApprovals', ['shelf_request_approvals']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'attributes', ['shelf_asset_attributes']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'company', ['companies']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'category', ['shelf_categories']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'brand', ['shelf_brands']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'assetLocation', ['shelf_asset_locations']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'assetTransfers', ['shelf_asset_transfers']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'assetTransferDetails', ['shelf_asset_transfer_details']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'recipient', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'recipientCompany', ['companies']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'companyDocumentSetting', ['shelf_company_document_settings']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'nbhResponsible', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Asset, 'vehicleChecksheets', ['shelf_vehicle_checksheets']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetAttribute, 'asset', ['shelf_assets']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetAttribute, 'customAttribute', ['shelf_custom_asset_attributes']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetRequest, 'user', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetRequest, 'asset', ['shelf_assets']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetRequest, 'approvals', ['shelf_request_approvals']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransfer, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransfer, 'company', ['companies']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransfer, 'companyDocumentSetting', ['shelf_company_document_settings']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransfer, 'asset', ['shelf_assets']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransfer, 'details', ['shelf_asset_transfer_details']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransfer, 'fromUser', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransfer, 'toUser', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransferDetail, 'assetTransfer', ['shelf_asset_transfers']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new AssetTransferDetail, 'asset', ['shelf_assets']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Brand, 'assets', ['shelf_assets']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Brand, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Category, 'parent', ['shelf_categories']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Category, 'children', ['shelf_categories']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Category, 'assets', ['shelf_assets']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Category, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new CompanyDocumentSetting, 'company', ['companies']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new CompanyDocumentSetting, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new CustomAssetAttribute, 'category', ['shelf_categories']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new CustomAssetAttribute, 'assetAttributes', ['shelf_asset_attributes']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new CustomAssetAttribute, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new RequestApproval, 'assetRequest', ['shelf_asset_requests']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new RequestApproval, 'approvalLevel', ['shelf_approval_levels']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new RequestApproval, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Task, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Task, 'company', ['companies']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Task, 'companyDocumentSetting', ['shelf_company_document_settings']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Task, 'user', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Task, 'vendor', ['shelf_vendors']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new Vendor, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new User, 'company', ['companies']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new User, 'jobTitle', ['employees_employees', 'employees_job_positions']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new User, 'assetTransfersFrom', ['shelf_asset_transfers']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new User, 'assetTransfersTo', ['shelf_asset_transfers']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new User, 'tasks', ['shelf_tasks']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new VehicleChecksheet, 'creator', ['users']);
        $this->assertRelationQueryDoesNotFilterDeletedAt(new VehicleChecksheet, 'asset', ['shelf_assets']);
    }

    public function test_shelf_belongs_to_relations_keep_the_expected_foreign_keys(): void
    {
        $this->assertSame('category_id', (new Asset)->category()->getForeignKeyName());
        $this->assertSame('brand_id', (new Asset)->brand()->getForeignKeyName());
        $this->assertSame('asset_location_id', (new Asset)->assetLocation()->getForeignKeyName());
        $this->assertSame('user_id', (new AssetRequest)->user()->getForeignKeyName());
        $this->assertSame('asset_id', (new AssetRequest)->asset()->getForeignKeyName());
        $this->assertSame('asset_transfer_id', (new AssetTransferDetail)->assetTransfer()->getForeignKeyName());
        $this->assertSame('asset_id', (new AssetTransferDetail)->asset()->getForeignKeyName());
        $this->assertSame('approval_level_id', (new RequestApproval)->approvalLevel()->getForeignKeyName());
        $this->assertSame('parent_id', (new Category)->parent()->getForeignKeyName());
        $this->assertSame('vendor_id', (new Task)->vendor()->getForeignKeyName());
    }

    public function test_shelf_migrations_define_deleted_at_for_local_tables(): void
    {
        $createMigrationPaths = [
            base_path('plugins/cesa/shelf/database/migrations/2024_07_08_133816_create_categories_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_07_10_141644_create_brands_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_07_10_142706_create_asset_locations_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_07_10_143140_create_assets_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_07_10_155351_create_asset_transfers_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_07_11_104706_create_asset_transfer_details_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_10_21_135802_create_vendors_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_10_21_135803_create_tasks_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_11_12_111750_create_vehicle_checksheets_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_11_14_101451_create_custom_asset_attributes_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2024_11_14_101453_create_asset_attributes_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2026_03_09_000001_create_asset_requests_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2026_03_09_234746_create_approval_levels_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2026_03_09_234746_create_request_approvals_table.php'),
            base_path('plugins/cesa/shelf/database/migrations/2026_03_17_010000_create_company_document_settings_table.php'),
        ];

        foreach ($createMigrationPaths as $migrationPath) {
            $migrationContents = file_get_contents($migrationPath);

            $this->assertIsString($migrationContents);
            $this->assertStringContainsString('$table->softDeletes();', $migrationContents, basename($migrationPath));
        }

        $upgradeMigrationContents = file_get_contents(
            base_path('plugins/cesa/shelf/database/migrations/2026_03_17_150000_add_soft_deletes_to_shelf_tables.php')
        );

        $this->assertIsString($upgradeMigrationContents);
        $this->assertStringContainsString("'shelf_assets'", $upgradeMigrationContents);
        $this->assertStringContainsString("'shelf_company_document_settings'", $upgradeMigrationContents);
        $this->assertStringContainsString('$table->softDeletes();', $upgradeMigrationContents);
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function assertRelationQueryDoesNotFilterDeletedAt(object $model, string $relation, array $tables): void
    {
        $sql = strtolower($model->{$relation}()->getQuery()->toSql());

        foreach ($tables as $table) {
            $table = strtolower($table);

            $this->assertStringNotContainsString("`{$table}`.`deleted_at`", $sql, "{$relation} still filters trashed {$table} records.");
            $this->assertStringNotContainsString("\"{$table}\".\"deleted_at\"", $sql, "{$relation} still filters trashed {$table} records.");
            $this->assertStringNotContainsString("{$table}.deleted_at", $sql, "{$relation} still filters trashed {$table} records.");
        }
    }

    private function formatTaskAttachmentPreview(array|string|null $state): Htmlable|string
    {
        $urls = collect(is_array($state) ? $state : [$state])
            ->filter(fn (mixed $url): bool => is_string($url) && $url !== '')
            ->values();

        if ($urls->isEmpty()) {
            return 'Tidak ada lampiran';
        }

        $images = $urls
            ->map(function (string $url): string {
                $url = e($url);

                return "<img src=\"{$url}\" alt=\"Lampiran\" style=\"max-width: 100px; border-radius: 5px;\">";
            })
            ->implode('');

        if ($images === '') {
            return 'Tidak ada lampiran';
        }

        return new \Illuminate\Support\HtmlString(
            "<div style=\"display: flex; flex-wrap: wrap; gap: 10px;\">{$images}</div>"
        );
    }
}
