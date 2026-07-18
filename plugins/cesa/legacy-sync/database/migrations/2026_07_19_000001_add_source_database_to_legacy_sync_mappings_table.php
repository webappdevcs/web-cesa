<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legacy_sync_mappings', function (Blueprint $table): void {
            $table->string('source_database', 191)
                ->default('__legacy_unscoped__')
                ->after('connection_name');

            $table->dropUnique('legacy_sync_mappings_unique');
            $table->unique(
                ['connection_name', 'source_database', 'legacy_table', 'legacy_id', 'target_table'],
                'legacy_sync_mappings_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('legacy_sync_mappings', function (Blueprint $table): void {
            $table->dropUnique('legacy_sync_mappings_source_unique');
            $table->dropColumn('source_database');
            $table->unique(
                ['connection_name', 'legacy_table', 'legacy_id', 'target_table'],
                'legacy_sync_mappings_unique'
            );
        });
    }
};
