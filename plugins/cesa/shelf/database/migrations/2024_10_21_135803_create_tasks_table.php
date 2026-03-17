<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('company_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('work_timestamp');
            $table->string('name');
            $table->text('description');
            $table->foreignId('vendor_id')->constrained('shelf_vendors');
            $table->decimal('cost', 12, 2);
            $table->string('location');
            $table->enum('status', ['open', 'in_progress', 'completed'])->default('open');
            $table->text('attachment')->nullable();
            $table->string('document_upload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_tasks');
    }
};
