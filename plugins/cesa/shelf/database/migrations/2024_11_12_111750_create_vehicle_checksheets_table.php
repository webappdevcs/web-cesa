<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_vehicle_checksheets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->nullable()->constrained('shelf_assets')->onDelete('cascade');
            $table->string('reference_number')->unique();
            $table->string('pic')->nullable();
            $table->string('license_plate');
            $table->string('location')->nullable();
            $table->string('destination')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('start_km')->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->text('departure_photo')->nullable();
            $table->text('departure_damage_report')->nullable();
            $table->integer('end_km')->nullable();
            $table->dateTime('return_time')->nullable();
            $table->text('return_photo')->nullable();
            $table->text('return_damage_report')->nullable();
            $table->float('rental_duration')->nullable();
            $table->float('distance_traveled')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_vehicle_checksheets');
    }
};
