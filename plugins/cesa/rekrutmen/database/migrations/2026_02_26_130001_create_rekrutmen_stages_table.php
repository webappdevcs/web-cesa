<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekrutmen_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekrutmen_pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('order_column')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_stages');
    }
};
