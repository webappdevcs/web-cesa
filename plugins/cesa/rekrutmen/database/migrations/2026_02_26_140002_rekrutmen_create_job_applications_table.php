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
        Schema::create('rekrutmen_job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('rekrutmen_job_postings')->cascadeOnDelete();
            $table->foreignId('current_stage_id')->nullable()->constrained('rekrutmen_stages')->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('resume_path')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_job_applications');
    }
};
