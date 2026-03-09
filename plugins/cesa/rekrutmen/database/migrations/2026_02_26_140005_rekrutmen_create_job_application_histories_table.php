<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekrutmen_job_application_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('rekrutmen_job_applications')->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('rekrutmen_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->nullable()->constrained('rekrutmen_stages')->nullOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_job_application_histories');
    }
};
