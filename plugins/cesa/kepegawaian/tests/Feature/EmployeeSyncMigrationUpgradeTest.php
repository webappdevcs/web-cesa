<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmployeeSyncMigrationUpgradeTest extends KepegawaianIdentityTestCase
{
    public function test_follow_up_migration_hardens_an_already_migrated_legacy_sync_schema(): void
    {
        $this->replaceCurrentSyncSchemaWithLegacyVersion();
        $payloadCiphertext = Crypt::encryptString('{"email":"private@example.com"}');
        $detailsCiphertext = Crypt::encryptString('{"reason":"legacy conflict"}');

        $runId = DB::table('employees_sync_runs')->insertGetId([
            'uuid'               => (string) Str::uuid(),
            'source_system'      => 'talenta',
            'source_instance'    => 'production',
            'mode'               => 'dry_run',
            'status'             => 'completed',
            'file_name'          => 'list-employee.json',
            'file_checksum'      => str_repeat('a', 64),
            'total_records'      => 1,
            'matched_count'      => 0,
            'linked_count'       => 0,
            'created_count'      => 0,
            'would_link_count'   => 0,
            'would_create_count' => 0,
            'conflict_count'     => 1,
            'invalid_count'      => 1,
            'error_message'      => 'Sensitive legacy failure',
            'initiated_by'       => null,
            'started_at'         => now(),
            'completed_at'       => now(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
        $sourceRecordId = DB::table('employees_source_records')->insertGetId([
            'sync_run_id'    => $runId,
            'row_number'     => 1,
            'external_id'    => ' Vendor-Upgrade-100 ',
            'employee_code'  => ' EMP-UPGRADE-100 ',
            'checksum'       => str_repeat('b', 64),
            'payload'        => $payloadCiphertext,
            'status'         => 'conflict',
            'match_strategy' => null,
            'employee_id'    => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        DB::table('employees_sync_conflicts')->insert([
            'source_record_id' => $sourceRecordId,
            'type'             => 'invalid_record',
            'status'           => 'resolved',
            'details'          => $detailsCiphertext,
            'employee_id'      => null,
            'resolution'       => 'rejected_invalid_source_record',
            'resolution_notes' => 'Vendor confirmed the bad row.',
            'resolved_by'      => null,
            'resolved_at'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $migration = require base_path(
            'plugins/cesa/kepegawaian/database/migrations/2026_07_19_000004_harden_employee_sync_tables.php'
        );
        $migration->up();
        $migration->up();

        foreach (['manifest_hash', 'reviewed_run_id', 'channel', 'commit_reason'] as $column) {
            $this->assertTrue(Schema::hasColumn('employees_sync_runs', $column));
        }

        foreach (['external_id_hash', 'employee_code_hash'] as $column) {
            $this->assertTrue(Schema::hasColumn('employees_source_records', $column));
        }

        $run = DB::table('employees_sync_runs')->where('id', $runId)->first();
        $sourceRecord = DB::table('employees_source_records')->where('id', $sourceRecordId)->first();
        $conflict = DB::table('employees_sync_conflicts')->where('source_record_id', $sourceRecordId)->first();

        $this->assertSame('console', $run->channel);
        $this->assertNull($run->manifest_hash);
        $this->assertNull($run->reviewed_run_id);
        $this->assertSame('Sensitive legacy failure', Crypt::decryptString($run->error_message));
        $this->assertNotSame('Sensitive legacy failure', $run->error_message);
        $this->assertSame(' Vendor-Upgrade-100 ', Crypt::decryptString($sourceRecord->external_id));
        $this->assertSame(' EMP-UPGRADE-100 ', Crypt::decryptString($sourceRecord->employee_code));
        $this->assertSame('Vendor confirmed the bad row.', Crypt::decryptString($conflict->resolution_notes));
        $this->assertSame($payloadCiphertext, $sourceRecord->payload);
        $this->assertSame($detailsCiphertext, $conflict->details);
        $this->assertSame(
            hash_hmac('sha256', 'vendor-upgrade-100', (string) config('app.key')),
            $sourceRecord->external_id_hash,
        );
        $this->assertSame(
            hash_hmac('sha256', 'emp-upgrade-100', (string) config('app.key')),
            $sourceRecord->employee_code_hash,
        );

        $identityIndex = collect(Schema::getIndexes('employees_source_records'))
            ->firstWhere('name', 'employee_source_records_identity_index');

        $this->assertNotNull($identityIndex);
        $this->assertSame(['external_id_hash', 'employee_code_hash'], $identityIndex['columns']);
        $this->assertTrue(collect(Schema::getForeignKeys('employees_sync_runs'))->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['reviewed_run_id']
                && $foreignKey['foreign_table'] === 'employees_sync_runs'
        ));
    }

    private function replaceCurrentSyncSchemaWithLegacyVersion(): void
    {
        Schema::drop('employees_sync_conflicts');
        Schema::drop('employees_source_records');
        Schema::drop('employees_sync_runs');

        Schema::create('employees_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_system', 64);
            $table->string('source_instance', 191);
            $table->string('mode', 32);
            $table->string('status', 32);
            $table->string('file_name');
            $table->char('file_checksum', 64);
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('linked_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('would_link_count')->default(0);
            $table->unsignedInteger('would_create_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(
                ['source_system', 'source_instance', 'started_at'],
                'employee_sync_runs_source_index',
            );
        });

        Schema::create('employees_source_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sync_run_id')->constrained('employees_sync_runs')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('external_id', 191)->nullable();
            $table->string('employee_code', 191)->nullable();
            $table->char('checksum', 64);
            $table->text('payload');
            $table->string('status', 64);
            $table->string('match_strategy', 64)->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees_employees')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['sync_run_id', 'row_number'], 'employee_source_records_row_unique');
            $table->index(['external_id', 'employee_code'], 'employee_source_records_identity_index');
        });

        Schema::create('employees_sync_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_record_id')->unique()->constrained('employees_source_records')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('status', 32)->default('open');
            $table->text('details');
            $table->foreignId('employee_id')->nullable()->constrained('employees_employees')->restrictOnDelete();
            $table->string('resolution', 64)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'type'], 'employee_sync_conflicts_review_index');
        });
    }
}
