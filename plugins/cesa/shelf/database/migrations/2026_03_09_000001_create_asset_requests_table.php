<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_asset_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('request_type', ['pengadaan_aset', 'perbaikan_aset', 'penarikan_aset']);
            $table->string('requester_name');
            $table->string('email');
            $table->string('division');
            $table->string('approval_track')->nullable();
            $table->string('placement');
            $table->string('item_name');
            $table->integer('qty');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('shelf_assets')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_asset_requests');
    }
};
