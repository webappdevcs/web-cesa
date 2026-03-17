<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->enum('request_type', ['pengadaan_aset', 'perbaikan_aset', 'penarikan_aset']);
            $table->string('division')->default('*');
            $table->unsignedSmallInteger('level');
            $table->string('approver_name');
            $table->string('approver_email');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['request_type', 'division', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_approval_levels');
    }
};
