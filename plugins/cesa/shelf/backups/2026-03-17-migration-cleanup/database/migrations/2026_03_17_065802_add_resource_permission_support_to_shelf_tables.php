<?php

use Cesa\Shelf\Support\InteractsWithSchemaForeignKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use InteractsWithSchemaForeignKeys;

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

        $fallbackCreatorId = $this->resolveFallbackCreatorId();

        $this->backfillSimpleCreator('shelf_categories', $fallbackCreatorId);
        $this->backfillSimpleCreator('shelf_brands', $fallbackCreatorId);
        $this->backfillSimpleCreator('shelf_asset_locations', $fallbackCreatorId);
        $this->backfillCreatorFromColumns('shelf_assets', ['recipient_id', 'nbh_responsible_user_id'], $fallbackCreatorId);
        $this->backfillCreatorFromColumns('shelf_asset_transfers', ['from_user_id', 'to_user_id'], $fallbackCreatorId);
        $this->backfillCreatorFromParent('shelf_asset_transfer_details', 'asset_transfer_id', 'shelf_asset_transfers', $fallbackCreatorId);
        $this->backfillSimpleCreator('shelf_vendors', $fallbackCreatorId);
        $this->backfillCreatorFromColumns('shelf_tasks', ['user_id'], $fallbackCreatorId);
        $this->backfillCreatorFromParent('shelf_vehicle_checksheets', 'asset_id', 'shelf_assets', $fallbackCreatorId);
        $this->backfillSimpleCreator('shelf_custom_asset_attributes', $fallbackCreatorId);
        $this->backfillCreatorFromParent('shelf_asset_attributes', 'asset_id', 'shelf_assets', $fallbackCreatorId);
        $this->backfillCreatorFromColumns('shelf_asset_requests', ['user_id'], $fallbackCreatorId);
        $this->backfillSimpleCreator('shelf_approval_levels', $fallbackCreatorId);
        $this->backfillCreatorFromParent('shelf_request_approvals', 'asset_request_id', 'shelf_asset_requests', $fallbackCreatorId);
        $this->backfillSimpleCreator('shelf_company_document_settings', $fallbackCreatorId);
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

    private function backfillSimpleCreator(string $table, ?int $creatorId): void
    {
        if ($creatorId === null || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'creator_id')) {
            return;
        }

        DB::table($table)
            ->whereNull('creator_id')
            ->update(['creator_id' => $creatorId]);
    }

    /**
     * @param  array<int, string>  $candidateColumns
     */
    private function backfillCreatorFromColumns(string $table, array $candidateColumns, ?int $fallbackCreatorId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'creator_id')) {
            return;
        }

        DB::table($table)
            ->select(['id', ...$candidateColumns])
            ->whereNull('creator_id')
            ->chunkById(200, function (Collection $records) use ($table, $candidateColumns, $fallbackCreatorId): void {
                foreach ($records as $record) {
                    $creatorId = collect($candidateColumns)
                        ->map(fn (string $column): ?int => isset($record->{$column}) ? (int) $record->{$column} : null)
                        ->first(fn (?int $value): bool => $value !== null)
                        ?? $fallbackCreatorId;

                    if ($creatorId === null) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['creator_id' => $creatorId]);
                }
            });
    }

    private function backfillCreatorFromParent(
        string $table,
        string $foreignKey,
        string $parentTable,
        ?int $fallbackCreatorId,
    ): void {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable($parentTable)
            || ! Schema::hasColumn($table, 'creator_id')
            || ! Schema::hasColumn($table, $foreignKey)
            || ! Schema::hasColumn($parentTable, 'creator_id')
        ) {
            return;
        }

        DB::table($table)
            ->select(['id', $foreignKey])
            ->whereNull('creator_id')
            ->chunkById(200, function (Collection $records) use ($table, $foreignKey, $parentTable, $fallbackCreatorId): void {
                $parentCreatorIds = DB::table($parentTable)
                    ->whereIn('id', $records->pluck($foreignKey)->filter()->unique()->all())
                    ->pluck('creator_id', 'id');

                foreach ($records as $record) {
                    $creatorId = null;

                    if (filled($record->{$foreignKey})) {
                        $creatorId = $parentCreatorIds->get($record->{$foreignKey});
                        $creatorId = $creatorId === null ? null : (int) $creatorId;
                    }

                    $creatorId ??= $fallbackCreatorId;

                    if ($creatorId === null) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['creator_id' => $creatorId]);
                }
            });
    }

    private function resolveFallbackCreatorId(): ?int
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        $query = DB::table('users')->orderBy('id');

        if (Schema::hasColumn('users', 'is_default')) {
            $preferredUserId = (clone $query)
                ->where('is_default', false)
                ->value('id');

            if ($preferredUserId !== null) {
                return (int) $preferredUserId;
            }
        }

        $firstUserId = $query->value('id');

        return $firstUserId === null ? null : (int) $firstUserId;
    }
};
