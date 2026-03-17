<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_request_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_request_id')->constrained('shelf_asset_requests')->cascadeOnDelete();
            $table->foreignId('approval_level_id')->nullable()->constrained('shelf_approval_levels')->nullOnDelete();
            $table->uuid('token')->unique();
            $table->unsignedSmallInteger('level');
            $table->string('approver_name');
            $table->string('approver_email');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_request_approvals');
    }
};
