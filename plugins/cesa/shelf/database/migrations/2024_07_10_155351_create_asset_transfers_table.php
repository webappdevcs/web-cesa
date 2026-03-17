<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_asset_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id');
            $table->string('letter_number')->unique();
            $table->string('transfer_type', 30)->nullable()->index();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('transfer_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('document')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_asset_transfers');
    }
};
