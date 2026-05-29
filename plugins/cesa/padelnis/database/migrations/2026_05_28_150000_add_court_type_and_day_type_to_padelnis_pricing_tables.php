<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('padelnis_courts') && ! Schema::hasColumn('padelnis_courts', 'court_type')) {
            Schema::table('padelnis_courts', function (Blueprint $table): void {
                $table->string('court_type')->nullable()->after('name');

                $table->index('court_type', 'padelnis_courts_court_type_idx');
            });
        }

        if (Schema::hasTable('padelnis_special_prices')) {
            Schema::table('padelnis_special_prices', function (Blueprint $table): void {
                if (! Schema::hasColumn('padelnis_special_prices', 'court_type')) {
                    $table->string('court_type')->nullable()->after('court_id');
                }

                if (! Schema::hasColumn('padelnis_special_prices', 'day_type')) {
                    $table->string('day_type', 20)->nullable()->after('time_slot');
                }
            });

            Schema::table('padelnis_special_prices', function (Blueprint $table): void {
                $table->index(['catalog_item_id', 'court_type'], 'padelnis_special_prices_catalog_court_type_idx');
                $table->index(['catalog_item_id', 'day_type'], 'padelnis_special_prices_catalog_day_type_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('padelnis_special_prices')) {
            Schema::table('padelnis_special_prices', function (Blueprint $table): void {
                $table->dropIndex('padelnis_special_prices_catalog_court_type_idx');
                $table->dropIndex('padelnis_special_prices_catalog_day_type_idx');

                if (Schema::hasColumn('padelnis_special_prices', 'court_type')) {
                    $table->dropColumn('court_type');
                }

                if (Schema::hasColumn('padelnis_special_prices', 'day_type')) {
                    $table->dropColumn('day_type');
                }
            });
        }

        if (Schema::hasTable('padelnis_courts') && Schema::hasColumn('padelnis_courts', 'court_type')) {
            Schema::table('padelnis_courts', function (Blueprint $table): void {
                $table->dropIndex('padelnis_courts_court_type_idx');
                $table->dropColumn('court_type');
            });
        }
    }
};
