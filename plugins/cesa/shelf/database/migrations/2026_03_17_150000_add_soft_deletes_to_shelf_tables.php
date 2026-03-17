<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'shelf_categories',
        'shelf_brands',
        'shelf_asset_locations',
        'shelf_assets',
        'shelf_asset_transfers',
        'shelf_asset_transfer_details',
        'shelf_vendors',
        'shelf_tasks',
        'shelf_vehicle_checksheets',
        'shelf_custom_asset_attributes',
        'shelf_asset_attributes',
        'shelf_asset_requests',
        'shelf_approval_levels',
        'shelf_request_approvals',
        'shelf_company_document_settings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->addSoftDeletesIfMissing($table);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $this->dropSoftDeletesIfExists($table);
        }
    }

    private function addSoftDeletesIfMissing(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    private function dropSoftDeletesIfExists(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
