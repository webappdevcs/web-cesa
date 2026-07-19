<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Services\EmployeeJsonSyncService;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Webkul\Security\Models\User;

class EmployeeJsonSyncServiceTest extends KepegawaianIdentityTestCase
{
    public function test_stage_reads_once_and_encrypts_source_data_without_mutating_canonical_records(): void
    {
        $employee = $this->createEmployee('EMP-001', 'Existing Employee');
        $path = $this->writeJson([$this->vendorRecord()]);

        try {
            $run = $this->service()->stage($path, 'talenta', 'production');

            $this->assertSame('dry_run', $run->mode);
            $this->assertSame('completed', $run->status);
            $this->assertSame(1, $run->total_records);
            $this->assertSame(1, $run->would_link_count);
            $this->assertSame(0, $employee->identifiers()->count());
            $this->assertSame(1, Employee::query()->count());

            $sourceRecord = $run->sourceRecords()->firstOrFail();
            $rawRecord = DB::table('employees_source_records')->where('id', $sourceRecord->id)->first();

            $this->assertSame('would_link', $sourceRecord->status);
            $this->assertSame($employee->id, $sourceRecord->employee_id);
            $this->assertSame('private.employee@example.com', $sourceRecord->payload['email']);
            $this->assertStringNotContainsString('private.employee@example.com', (string) $rawRecord->payload);
            $this->assertStringNotContainsString('vendor-100', (string) $rawRecord->external_id);
            $this->assertStringNotContainsString('EMP-001', (string) $rawRecord->employee_code);
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_employee_code_match_requires_manual_review_instead_of_creating_identity_link(): void
    {
        $employee = $this->createEmployee('EMP-001', 'Existing Employee');
        $dryRun = $this->stage([$this->vendorRecord()]);

        $commitRun = $this->service()->commitReviewed(
            $dryRun,
            $this->actor(),
            'HR reviewed the staged checksum.',
        );

        $this->assertSame('completed', $commitRun->status);
        $this->assertSame(1, $commitRun->conflict_count);
        $this->assertSame(0, $commitRun->linked_count);
        $this->assertSame(0, $employee->identifiers()->count());
        $this->assertSame(
            'employee_code_candidate_match',
            $commitRun->sourceRecords()->firstOrFail()->conflict()->firstOrFail()->type
        );
    }

    public function test_reviewed_commit_uses_staged_bytes_and_creates_new_employee_inactive(): void
    {
        $path = $this->writeJson([$this->vendorRecord([
            'id'          => 'vendor-new-200',
            'id_employee' => 'EMP-NEW-200',
            'first_name'  => 'Reviewed',
            'last_name'   => 'Employee',
        ])]);

        $dryRun = $this->service()->stage($path, 'talenta', 'production');
        file_put_contents($path, json_encode([$this->vendorRecord([
            'id'          => 'tampered-id',
            'id_employee' => 'EMP-TAMPERED',
        ])], JSON_THROW_ON_ERROR));

        try {
            $commitRun = $this->service()->commitReviewed(
                $dryRun,
                $this->actor(),
                'Reviewed against the signed HR export.',
            );

            $employee = Employee::query()->where('employee_code', 'EMP-NEW-200')->firstOrFail();

            $this->assertSame($dryRun->id, $commitRun->reviewed_run_id);
            $this->assertSame(1, $commitRun->created_count);
            $this->assertFalse($employee->is_active);
            $this->assertTrue(Str::isUuid($employee->uuid));
            $this->assertDatabaseMissing('employees_employees', ['employee_code' => 'EMP-TAMPERED']);
            $this->assertDatabaseHas('employees_employee_identifiers', [
                'employee_id'      => $employee->id,
                'source_system'    => 'talenta',
                'source_instance'  => 'production',
                'normalized_value' => 'vendor-new-200',
            ]);
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_reviewed_commit_is_idempotent_for_an_exact_external_identity(): void
    {
        $employee = $this->createEmployee('EMP-001', 'Existing Employee');
        $identifier = $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-100',
        ]);
        $dryRun = $this->stage([$this->vendorRecord()]);

        $commitRun = $this->service()->commitReviewed(
            $dryRun,
            $this->actor(),
            'Exact identity verified.',
        );

        $this->assertSame(1, $commitRun->matched_count);
        $this->assertSame(1, $employee->identifiers()->count());
        $this->assertNotNull($identifier->fresh()->last_seen_at);
        $this->assertSame('matched', $commitRun->sourceRecords()->firstOrFail()->status);
    }

    public function test_retired_external_identity_is_staged_as_a_reviewable_conflict(): void
    {
        $employee = $this->createEmployee('EMP-001', 'Former Owner');
        $identifier = $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-100',
        ]);
        $identifier->retire();

        $run = $this->stage([$this->vendorRecord()]);

        $this->assertSame('completed', $run->status);
        $this->assertSame(1, $run->conflict_count);
        $this->assertSame(
            'retired_identifier_match',
            $run->sourceRecords()->firstOrFail()->conflict()->firstOrFail()->type
        );
    }

    public function test_second_current_record_id_for_the_same_source_is_a_conflict(): void
    {
        $employee = $this->createEmployee('EMP-001', 'Existing Employee');
        $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-original',
        ]);

        $run = $this->stage([$this->vendorRecord([
            'id' => 'vendor-second-current',
        ])]);

        $this->assertSame(1, $run->conflict_count);
        $this->assertSame(
            'multiple_source_record_ids',
            $run->sourceRecords()->firstOrFail()->conflict()->firstOrFail()->type
        );
        $this->assertSame(1, $employee->identifiers()->current()->count());
    }

    public function test_conflicting_external_id_and_employee_code_is_queued_for_review(): void
    {
        $identifierOwner = $this->createEmployee('EMP-A', 'Identifier Owner');
        $codeOwner = $this->createEmployee('EMP-B', 'Code Owner');

        $identifierOwner->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-conflict',
        ]);

        $run = $this->stage([$this->vendorRecord([
            'id'          => 'vendor-conflict',
            'id_employee' => 'EMP-B',
        ])]);

        $sourceRecord = $run->sourceRecords()->firstOrFail();
        $conflict = $sourceRecord->conflict()->firstOrFail();

        $this->assertSame(1, $run->conflict_count);
        $this->assertSame('identifier_employee_code_mismatch', $conflict->type);
        $this->assertSame($identifierOwner->id, $conflict->employee_id);
        $this->assertSame($codeOwner->id, $conflict->details['employee_code_owner_id']);
    }

    public function test_reviewed_commit_rolls_back_every_canonical_mutation_when_a_later_row_fails(): void
    {
        $dryRun = $this->stage([
            $this->vendorRecord([
                'id'          => 'vendor-atomic-1',
                'id_employee' => 'EMP-ATOMIC-1',
            ]),
            $this->vendorRecord([
                'id'          => 'vendor-atomic-2',
                'id_employee' => 'EMP-ATOMIC-2',
            ]),
        ]);

        Event::listen('eloquent.creating: '.Employee::class, function (Employee $employee): void {
            if ($employee->employee_code === 'EMP-ATOMIC-2') {
                throw new RuntimeException('Synthetic second-row failure.');
            }
        });

        try {
            $this->service()->commitReviewed(
                $dryRun,
                $this->actor(),
                'Atomicity test.',
            );

            $this->fail('The synthetic row failure should abort the commit.');
        } catch (RuntimeException) {
            $this->assertDatabaseMissing('employees_employees', ['employee_code' => 'EMP-ATOMIC-1']);
            $this->assertDatabaseMissing('employees_employees', ['employee_code' => 'EMP-ATOMIC-2']);
            $this->assertDatabaseCount('employees_employee_identifiers', 0);
            $this->assertSame(2, $dryRun->sourceRecords()->count());
            $this->assertDatabaseHas('employees_sync_runs', [
                'reviewed_run_id' => $dryRun->id,
                'mode'            => 'commit',
                'status'          => 'failed',
            ]);
        } finally {
            Event::forget('eloquent.creating: '.Employee::class);
        }
    }

    public function test_a_reviewed_dry_run_cannot_be_committed_twice(): void
    {
        $dryRun = $this->stage([$this->vendorRecord([
            'id'          => 'vendor-once',
            'id_employee' => 'EMP-ONCE',
        ])]);
        $actor = $this->actor();

        $this->service()->commitReviewed($dryRun, $actor, 'First approval.');

        $this->expectException(LogicException::class);

        $this->service()->commitReviewed($dryRun, $actor, 'Duplicate approval.');
    }

    public function test_missing_snapshot_rows_do_not_deactivate_existing_employees(): void
    {
        $employee = $this->createEmployee('EMP-STAYS-ACTIVE', 'Active Employee');
        $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'not-in-snapshot',
        ]);
        $dryRun = $this->stage([]);

        $commitRun = $this->service()->commitReviewed($dryRun, $this->actor(), 'Empty snapshot reviewed.');

        $this->assertSame(0, $commitRun->total_records);
        $this->assertTrue($employee->fresh()->is_active);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    private function stage(array $records): EmployeeSyncRun
    {
        $path = $this->writeJson($records);

        try {
            return $this->service()->stage($path, 'talenta', 'production');
        } finally {
            $this->removeFile($path);
        }
    }

    private function service(): EmployeeJsonSyncService
    {
        return app(EmployeeJsonSyncService::class);
    }

    private function actor(): User
    {
        return User::factory()->create();
    }

    private function createEmployee(string $employeeCode, string $name): Employee
    {
        return Employee::query()->create([
            'name'          => $name,
            'employee_code' => $employeeCode,
            'is_active'     => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function vendorRecord(array $overrides = []): array
    {
        return array_replace([
            'branch'          => 'PT Example',
            'organization'    => 'Human Capital',
            'id'              => 'vendor-100',
            'id_employee'     => 'EMP-001',
            'job'             => 'HR Officer',
            'title'           => 'Staff',
            'first_name'      => 'Existing',
            'last_name'       => 'Employee',
            'email'           => 'private.employee@example.com',
            'mobile_phone'    => '08123456789',
            'phone'           => null,
            'current_address' => 'Private Address',
            'marital_status'  => 'Single',
            'gender'          => 'Male',
            'birth_date'      => '01 Jan 1990',
            'join_date'       => null,
            'status_employee' => null,
        ], $overrides);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    private function writeJson(array $records): string
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-json-sync-');

        file_put_contents($path, json_encode($records, JSON_THROW_ON_ERROR));

        return $path;
    }

    private function removeFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
