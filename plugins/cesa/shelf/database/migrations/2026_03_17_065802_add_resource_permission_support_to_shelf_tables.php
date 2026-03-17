<?php

use Cesa\Shelf\Support\InteractsWithSchemaForeignKeys;
use Cesa\Shelf\Support\InteractsWithShelfCreatorBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use InteractsWithSchemaForeignKeys;
    use InteractsWithShelfCreatorBackfill;

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
            $this->addCreatorColumnIfMissing($table);
        }

        $this->backfillShelfCreatorIds();
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            $this->dropCreatorColumnIfExists($table);
        }
    }

    private function addCreatorColumnIfMissing(string $table): void
    {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable('users')
            || Schema::hasColumn($table, 'creator_id')
        ) {
            return;
        }

        Schema::table($table, function (Blueprint $table): void {
            $table->foreignId('creator_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    private function dropCreatorColumnIfExists(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'creator_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if ($this->hasForeignKey($tableName, 'creator_id')) {
                $table->dropForeign(['creator_id']);
            }

            $table->dropColumn('creator_id');
        });
    }
};
