<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_company_document_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('format', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('letterhead_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_company_document_settings');
    }
};
