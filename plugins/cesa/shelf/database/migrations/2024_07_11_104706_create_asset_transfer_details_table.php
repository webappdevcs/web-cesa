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
        Schema::create('shelf_asset_transfer_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('asset_transfer_id');
            $table->unsignedBigInteger('asset_id');
            $table->string('equipment')->nullable();

            $table->foreign('asset_transfer_id')->references('id')->on('shelf_asset_transfers')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('shelf_assets')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelf_asset_transfer_details');
    }
};
