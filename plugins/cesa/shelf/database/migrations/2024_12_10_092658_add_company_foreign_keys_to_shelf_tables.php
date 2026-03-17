<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addForeignKeyIfMissing('shelf_assets', 'company_id', 'companies', 'set null');
        $this->addForeignKeyIfMissing('shelf_assets', 'recipient_company_id', 'companies', 'set null');
        $this->addForeignKeyIfMissing('shelf_asset_transfers', 'company_id', 'companies', 'cascade');
        $this->addForeignKeyIfMissing('shelf_tasks', 'company_id', 'companies');
    }

    public function down(): void
    {
        $this->dropForeignIfExists('shelf_assets', 'company_id');
        $this->dropForeignIfExists('shelf_assets', 'recipient_company_id');
        $this->dropForeignIfExists('shelf_asset_transfers', 'company_id');
        $this->dropForeignIfExists('shelf_tasks', 'company_id');
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $column,
        string $foreignTable,
        ?string $onDelete = null,
        string $foreignColumn = 'id',
    ): void {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable($foreignTable)
            || ! Schema::hasColumn($table, $column)
            || $this->hasForeignKey($table, $column, $foreignTable, $foreignColumn)
        ) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column, $foreignTable, $foreignColumn, $onDelete): void {
            $foreignKey = $table->foreign($column)->references($foreignColumn)->on($foreignTable);

            if ($onDelete === 'cascade') {
                $foreignKey->cascadeOnDelete();

                return;
            }

            if ($onDelete === 'set null') {
                $foreignKey->nullOnDelete();
            }
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || ! $this->hasForeignKey($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column): void {
            $table->dropForeign([$column]);
        });
    }

    private function hasForeignKey(
        string $table,
        string $column,
        ?string $foreignTable = null,
        string $foreignColumn = 'id',
    ): bool {
        return collect(Schema::getForeignKeys($table))
            ->contains(function (array $foreignKey) use ($column, $foreignTable, $foreignColumn): bool {
                if (($foreignKey['columns'] ?? null) !== [$column]) {
                    return false;
                }

                if ($foreignTable !== null && ($foreignKey['foreign_table'] ?? null) !== $foreignTable) {
                    return false;
                }

                return ($foreignKey['foreign_columns'] ?? null) === [$foreignColumn];
            });
    }
};
