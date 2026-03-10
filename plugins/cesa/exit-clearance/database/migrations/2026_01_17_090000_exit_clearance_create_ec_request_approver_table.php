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
        Schema::create('exit_clearance_request_approver', function (Blueprint $table): void {
            $table->foreignId('request_id')->constrained('exit_clearance_requests')->restrictOnDelete();
            $table->foreignId('approver_id')->constrained('exit_clearance_approvers')->restrictOnDelete();
            $table->primary(['request_id', 'approver_id']);
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exit_clearance_request_approver');
    }
};
