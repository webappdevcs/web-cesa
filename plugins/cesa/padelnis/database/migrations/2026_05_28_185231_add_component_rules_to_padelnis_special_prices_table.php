<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('padelnis_special_prices', function (Blueprint $table): void {
            if (! Schema::hasColumn('padelnis_special_prices', 'component')) {
                $table->string('component', 30)->nullable()->after('catalog_item_id');
            }

            if (! Schema::hasColumn('padelnis_special_prices', 'calculation_type')) {
                $table->string('calculation_type', 30)->default('fixed')->after('amount');
            }

            if (! Schema::hasColumn('padelnis_special_prices', 'percentage')) {
                $table->decimal('percentage', 8, 4)->nullable()->after('calculation_type');
            }

            if (! Schema::hasColumn('padelnis_special_prices', 'basis')) {
                $table->string('basis', 30)->default('quoted_amount')->after('percentage');
            }
        });

        $this->ensureIndexes();
    }

    public function down(): void
    {
        Schema::table('padelnis_special_prices', function (Blueprint $table): void {
            if ($this->hasIndex('padelnis_special_prices', 'padelnis_special_prices_item_component_active_idx')) {
                $table->dropIndex('padelnis_special_prices_item_component_active_idx');
            }

            foreach (['basis', 'percentage', 'calculation_type', 'component'] as $column) {
                if (Schema::hasColumn('padelnis_special_prices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function ensureIndexes(): void
    {
        if ($this->hasIndex('padelnis_special_prices', 'padelnis_special_prices_item_component_active_idx')) {
            return;
        }

        Schema::table('padelnis_special_prices', function (Blueprint $table): void {
            $table->index(['catalog_item_id', 'component', 'is_active'], 'padelnis_special_prices_item_component_active_idx');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
