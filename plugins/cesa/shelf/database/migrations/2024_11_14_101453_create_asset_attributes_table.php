<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shelf_asset_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('shelf_assets')->onDelete('cascade');
            $table->foreignId('custom_attribute_id')->nullable()->constrained('shelf_custom_asset_attributes')->onDelete('set null');
            $table->string('attribute_value')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelf_asset_attributes');
    }
};
