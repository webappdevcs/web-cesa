<?php

use Cesa\Padelnis\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('padelnis_courts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort']);
        });

        Schema::create('padelnis_coaches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort']);
        });

        Schema::create('padelnis_catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('transaction_type', 20)->default(TransactionType::Regular->value);
            $table->decimal('price_amount', 15, 2)->default(0);
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->unsignedSmallInteger('session_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['transaction_type', 'is_active', 'sort']);
        });

        Schema::create('padelnis_special_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('catalog_item_id')->constrained('padelnis_catalog_items')->cascadeOnDelete();
            $table->foreignId('court_id')->nullable()->constrained('padelnis_courts')->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('padelnis_coaches')->nullOnDelete();
            $table->string('name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['catalog_item_id', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('padelnis_special_prices');
        Schema::dropIfExists('padelnis_catalog_items');
        Schema::dropIfExists('padelnis_coaches');
        Schema::dropIfExists('padelnis_courts');
    }
};
