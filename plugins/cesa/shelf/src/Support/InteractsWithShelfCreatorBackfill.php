<?php

namespace Cesa\Shelf\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait InteractsWithShelfCreatorBackfill
{
    /**
     * @return array<int, array{table: string, strategy: 'simple'|'columns'|'parent', columns?: array<int, string>, foreign_key?: string, parent_table?: string}>
     */
    protected function shelfCreatorBackfillDefinitions(): array
    {
        return [
            ['table' => 'shelf_categories', 'strategy' => 'simple'],
            ['table' => 'shelf_brands', 'strategy' => 'simple'],
            ['table' => 'shelf_asset_locations', 'strategy' => 'simple'],
            ['table' => 'shelf_assets', 'strategy' => 'columns', 'columns' => ['recipient_id', 'nbh_responsible_user_id']],
            ['table' => 'shelf_asset_transfers', 'strategy' => 'columns', 'columns' => ['from_user_id', 'to_user_id']],
            ['table' => 'shelf_asset_transfer_details', 'strategy' => 'parent', 'foreign_key' => 'asset_transfer_id', 'parent_table' => 'shelf_asset_transfers'],
            ['table' => 'shelf_vendors', 'strategy' => 'simple'],
            ['table' => 'shelf_tasks', 'strategy' => 'columns', 'columns' => ['user_id']],
            ['table' => 'shelf_vehicle_checksheets', 'strategy' => 'parent', 'foreign_key' => 'asset_id', 'parent_table' => 'shelf_assets'],
            ['table' => 'shelf_custom_asset_attributes', 'strategy' => 'simple'],
            ['table' => 'shelf_asset_attributes', 'strategy' => 'parent', 'foreign_key' => 'asset_id', 'parent_table' => 'shelf_assets'],
            ['table' => 'shelf_asset_requests', 'strategy' => 'columns', 'columns' => ['user_id']],
            ['table' => 'shelf_approval_levels', 'strategy' => 'simple'],
            ['table' => 'shelf_request_approvals', 'strategy' => 'parent', 'foreign_key' => 'asset_request_id', 'parent_table' => 'shelf_asset_requests'],
            ['table' => 'shelf_company_document_settings', 'strategy' => 'simple'],
        ];
    }

    protected function backfillShelfCreatorIds(): void
    {
        $fallbackCreatorId = $this->resolveFallbackCreatorId();

        foreach ($this->shelfCreatorBackfillDefinitions() as $definition) {
            match ($definition['strategy']) {
                'simple'  => $this->backfillSimpleCreator($definition['table'], $fallbackCreatorId),
                'columns' => $this->backfillCreatorFromColumns(
                    $definition['table'],
                    $definition['columns'] ?? [],
                    $fallbackCreatorId,
                ),
                'parent' => $this->backfillCreatorFromParent(
                    $definition['table'],
                    $definition['foreign_key'] ?? '',
                    $definition['parent_table'] ?? '',
                    $fallbackCreatorId,
                ),
            };
        }
    }

    protected function backfillSimpleCreator(string $table, ?int $creatorId): void
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
    protected function backfillCreatorFromColumns(string $table, array $candidateColumns, ?int $fallbackCreatorId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'creator_id')) {
            return;
        }

        $availableColumns = collect($candidateColumns)
            ->filter(fn (string $column): bool => Schema::hasColumn($table, $column))
            ->values()
            ->all();

        if ($availableColumns === []) {
            $this->backfillSimpleCreator($table, $fallbackCreatorId);

            return;
        }

        DB::table($table)
            ->select(array_merge(['id'], $availableColumns))
            ->whereNull('creator_id')
            ->chunkById(200, function (Collection $records) use ($table, $availableColumns, $fallbackCreatorId): void {
                foreach ($records as $record) {
                    $creatorId = collect($availableColumns)
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

    protected function backfillCreatorFromParent(
        string $table,
        string $foreignKey,
        string $parentTable,
        ?int $fallbackCreatorId,
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'creator_id')) {
            return;
        }

        if (
            ! Schema::hasTable($parentTable)
            || ! Schema::hasColumn($table, $foreignKey)
            || ! Schema::hasColumn($parentTable, 'creator_id')
        ) {
            $this->backfillSimpleCreator($table, $fallbackCreatorId);

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

    protected function resolveFallbackCreatorId(): ?int
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
}
