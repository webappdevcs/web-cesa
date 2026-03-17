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
        Schema::create('shelf_custom_asset_attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->boolean('required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('category_id')->nullable();
            $table->boolean('is_notifiable')->default(false);
            $table->enum('notification_type', ['fixed_date', 'relative_date', 'monthly'])->nullable();
            $table->integer('notification_offset')->nullable();
            $table->date('fixed_notification_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelf_custom_asset_attributes');
    }
};
