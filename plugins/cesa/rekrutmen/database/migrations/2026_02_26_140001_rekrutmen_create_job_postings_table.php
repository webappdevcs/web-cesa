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
        Schema::create('rekrutmen_job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_man_power_id')->nullable()->constrained('rekrutmen_request_man_powers')->nullOnDelete();
            $table->foreignId('rekrutmen_pipeline_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->longText('requirements')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_published')->default(false);
            $table->date('closing_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_job_postings');
    }
};
