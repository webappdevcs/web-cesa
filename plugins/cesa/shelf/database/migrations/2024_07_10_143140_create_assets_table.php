<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_assets', function (Blueprint $table): void {
            $table->id();
            $table->date('purchase_date')->nullable();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('audit_document_path')->nullable();
            $table->string('nbh_document_path')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('shelf_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('shelf_brands')->nullOnDelete();
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('imei1')->nullable();
            $table->string('imei2')->nullable();
            $table->bigInteger('item_price')->nullable();
            $table->foreignId('asset_location_id')->nullable()->constrained('shelf_asset_locations')->nullOnDelete();
            $table->integer('qty')->default(1);
            $table->string('condition_status')->default('available');
            $table->string('nbh_status')->default('none');
            $table->date('nbh_reported_at')->nullable();
            $table->text('nbh_notes')->nullable();
            $table->boolean('is_available')->default(true);
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_company_id')->nullable();
            $table->foreignId('nbh_responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_assets');
    }
};
