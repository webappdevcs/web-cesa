<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_system', 64);
            $table->string('source_instance', 191);
            $table->string('mode', 32);
            $table->string('status', 32);
            $table->string('file_name');
            $table->char('file_checksum', 64);
            $table->char('manifest_hash', 64)->nullable();
            $table->foreignId('reviewed_run_id')
                ->nullable()
                ->constrained('employees_sync_runs')
                ->restrictOnDelete();
            $table->string('channel', 32)->default('console');
            $table->longText('commit_reason')->nullable();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('linked_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('would_link_count')->default(0);
            $table->unsignedInteger('would_create_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->longText('error_message')->nullable();
            $table->foreignId('initiated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['source_system', 'source_instance', 'started_at'],
                'employee_sync_runs_source_index'
            );
        });

        Schema::create('employees_source_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sync_run_id')
                ->constrained('employees_sync_runs')
                ->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->longText('external_id')->nullable();
            $table->char('external_id_hash', 64)->nullable();
            $table->longText('employee_code')->nullable();
            $table->char('employee_code_hash', 64)->nullable();
            $table->char('checksum', 64);
            $table->longText('payload');
            $table->string('status', 64);
            $table->string('match_strategy', 64)->nullable();
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees_employees')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['sync_run_id', 'row_number'],
                'employee_source_records_row_unique'
            );
            $table->index(
                ['external_id_hash', 'employee_code_hash'],
                'employee_source_records_identity_index'
            );
        });

        Schema::create('employees_sync_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_record_id')
                ->unique()
                ->constrained('employees_source_records')
                ->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('status', 32)->default('open');
            $table->longText('details');
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees_employees')
                ->restrictOnDelete();
            $table->string('resolution', 64)->nullable();
            $table->longText('resolution_notes')->nullable();
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type'], 'employee_sync_conflicts_review_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees_sync_conflicts');
        Schema::dropIfExists('employees_source_records');
        Schema::dropIfExists('employees_sync_runs');
    }
};
