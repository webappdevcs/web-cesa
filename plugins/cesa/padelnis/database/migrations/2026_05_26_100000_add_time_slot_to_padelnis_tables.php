<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('padelnis_catalog_items', function (Blueprint $table): void {
            $table->string('time_slot', 30)->nullable()->after('transaction_type');
            $table->index(['transaction_type', 'time_slot', 'is_active', 'sort'], 'padelnis_catalog_items_type_slot_active_sort_idx');
        });

        Schema::table('padelnis_special_prices', function (Blueprint $table): void {
            $table->string('time_slot', 30)->nullable()->after('coach_id');
            $table->index(['catalog_item_id', 'time_slot', 'is_active'], 'padelnis_special_prices_item_slot_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('padelnis_special_prices', function (Blueprint $table): void {
            $table->dropIndex('padelnis_special_prices_item_slot_active_idx');
            $table->dropColumn('time_slot');
        });

        Schema::table('padelnis_catalog_items', function (Blueprint $table): void {
            $table->dropIndex('padelnis_catalog_items_type_slot_active_sort_idx');
            $table->dropColumn('time_slot');
        });
    }
};
