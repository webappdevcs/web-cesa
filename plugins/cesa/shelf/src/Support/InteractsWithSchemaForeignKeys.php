<?php

namespace Cesa\Shelf\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait InteractsWithSchemaForeignKeys
{
    protected function addForeignKeyIfMissing(
        string $tableName,
        string $column,
        string $foreignTable,
        ?string $onDelete = null,
        string $foreignColumn = 'id',
    ): void {
        if (
            ! Schema::hasTable($tableName)
            || ! Schema::hasTable($foreignTable)
            || ! Schema::hasColumn($tableName, $column)
            || $this->hasForeignKey($tableName, $column, $foreignTable, $foreignColumn)
        ) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $foreignTable, $foreignColumn, $onDelete): void {
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

    protected function dropForeignIfExists(string $tableName, string $column): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column) || ! $this->hasForeignKey($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $table->dropForeign([$column]);
        });
    }

    protected function hasForeignKey(
        string $tableName,
        string $column,
        ?string $foreignTable = null,
        string $foreignColumn = 'id',
    ): bool {
        return collect(Schema::getForeignKeys($tableName))
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
}
