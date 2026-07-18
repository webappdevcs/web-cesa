<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_employees', function (Blueprint $table): void {
            $table->uuid('uuid')
                ->nullable()
                ->after('id');
            $table->unique('uuid', 'employees_employees_uuid_unique');
        });

        DB::table('employees_employees')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(250, function ($employees): void {
                foreach ($employees as $employee) {
                    DB::table('employees_employees')
                        ->where('id', $employee->id)
                        ->update(['uuid' => (string) Str::orderedUuid()]);
                }
            });

        Schema::table('employees_employees', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::create('employees_employee_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees_employees')
                ->restrictOnDelete();
            $table->string('source_system', 64);
            $table->string('source_instance', 191);
            $table->string('identifier_type', 64);
            $table->string('external_id', 191);
            $table->string('normalized_value', 191);
            $table->text('metadata')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['source_system', 'source_instance', 'identifier_type', 'normalized_value'],
                'employee_identifiers_source_unique'
            );
            $table->index(
                ['employee_id', 'retired_at'],
                'employee_identifiers_current_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees_employee_identifiers');

        Schema::table('employees_employees', function (Blueprint $table): void {
            $table->dropUnique('employees_employees_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
