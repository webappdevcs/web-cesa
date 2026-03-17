<?php

namespace Cesa\Shelf\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait InteractsWithSchemaIndexes
{
    protected function addIndexIfMissing(string $tableName, string $column, string $indexName): void
    {
        if (
            ! Schema::hasTable($tableName)
            || ! Schema::hasColumn($tableName, $column)
            || $this->hasIndexOnColumn($tableName, $column)
        ) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $indexName): void {
            $table->index($column, $indexName);
        });
    }

    protected function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! $this->hasNamedIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }

    protected function hasIndexOnColumn(string $tableName, string $column): bool
    {
        return collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index): bool => ($index['columns'] ?? null) === [$column]);
    }

    protected function hasNamedIndex(string $tableName, string $indexName): bool
    {
        return collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === $indexName);
    }
}
