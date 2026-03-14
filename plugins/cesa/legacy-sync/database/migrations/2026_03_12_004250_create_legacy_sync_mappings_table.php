<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_sync_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('connection_name', 64);
            $table->string('legacy_table', 128);
            $table->string('legacy_id', 191);
            $table->string('target_table', 128);
            $table->unsignedBigInteger('target_id');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['connection_name', 'legacy_table', 'legacy_id', 'target_table'],
                'legacy_sync_mappings_unique'
            );
            $table->index(['target_table', 'target_id'], 'legacy_sync_mappings_target_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_sync_mappings');
    }
};
