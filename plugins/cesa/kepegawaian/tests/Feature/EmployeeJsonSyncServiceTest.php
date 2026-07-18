<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Services\EmployeeJsonSyncService;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeJsonSyncServiceTest extends KepegawaianIdentityTestCase
{
    public function test_dry_run_stages_encrypted_data_without_mutating_canonical_records(): void
    {
        $employee = $this->createEmployee('EMP-001', 'Existing Employee');
        $path = $this->writeJson([$this->vendorRecord()]);

        try {
            $run = app(EmployeeJsonSyncService::class)->sync(
                path: $path,
                sourceSystem: 'talenta',
                sourceInstance: 'production',
                commit: false,
            );

            $this->assertSame('dry_run', $run->mode);
            $this->assertSame('completed', $run->status);
            $this->assertSame(1, $run->total_records);
            $this->assertSame(1, $run->would_link_count);
            $this->assertSame(0, $employee->identifiers()->count());
            $this->assertSame(1, Employee::query()->count());

            $sourceRecord = $run->sourceRecords()->firstOrFail();

            $this->assertSame('would_link', $sourceRecord->status);
            $this->assertSame($employee->id, $sourceRecord->employee_id);
            $this->assertSame('private.employee@example.com', $sourceRecord->payload['email']);
            $this->assertStringNotContainsString(
                'private.employee@example.com',
                (string) DB::table('employees_source_records')->where('id', $sourceRecord->id)->value('payload')
            );
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_commit_links_by_employee_code_then_becomes_idempotent_by_external_id(): void
    {
        $employee = $this->createEmployee('EMP-001', 'Existing Employee');
        $path = $this->writeJson([$this->vendorRecord()]);

        try {
            $firstRun = app(EmployeeJsonSyncService::class)->sync(
                path: $path,
                sourceSystem: 'talenta',
                sourceInstance: 'production',
                commit: true,
            );

            $this->assertSame(1, $firstRun->linked_count);
            $this->assertSame(1, $employee->identifiers()->count());
            $this->assertDatabaseHas('employees_employee_identifiers', [
                'employee_id'     => $employee->id,
                'source_system'   => 'talenta',
                'source_instance' => 'production',
                'identifier_type' => 'record_id',
                'normalized_value'=> 'vendor-100',
            ]);

            $secondRun = app(EmployeeJsonSyncService::class)->sync(
                path: $path,
                sourceSystem: 'talenta',
                sourceInstance: 'production',
                commit: true,
            );

            $this->assertSame(1, $secondRun->matched_count);
            $this->assertSame(1, $employee->identifiers()->count());
            $this->assertSame(
                'matched',
                $secondRun->sourceRecords()->firstOrFail()->status
            );
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_commit_creates_new_employee_inactive_until_hr_verifies_it(): void
    {
        $path = $this->writeJson([$this->vendorRecord([
            'id'          => 'vendor-new-200',
            'id_employee' => 'EMP-NEW-200',
            'first_name'  => 'New',
            'last_name'   => 'Employee',
        ])]);

        try {
            $run = app(EmployeeJsonSyncService::class)->sync(
                path: $path,
                sourceSystem: 'talenta',
                sourceInstance: 'production',
                commit: true,
            );

            $employee = Employee::query()->where('employee_code', 'EMP-NEW-200')->firstOrFail();

            $this->assertSame(1, $run->created_count);
            $this->assertFalse($employee->is_active);
            $this->assertTrue(Str::isUuid($employee->uuid));
            $this->assertDatabaseHas('employees_employee_identifiers', [
                'employee_id'     => $employee->id,
                'source_system'   => 'talenta',
                'source_instance' => 'production',
                'normalized_value'=> 'vendor-new-200',
            ]);
        } finally {
            $this->removeFile($path);
        }
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

        $path = $this->writeJson([$this->vendorRecord([
            'id'          => 'vendor-conflict',
            'id_employee' => 'EMP-B',
        ])]);

        try {
            $run = app(EmployeeJsonSyncService::class)->sync(
                path: $path,
                sourceSystem: 'talenta',
                sourceInstance: 'production',
                commit: true,
            );

            $sourceRecord = $run->sourceRecords()->firstOrFail();
            $conflict = $sourceRecord->conflict()->firstOrFail();

            $this->assertSame(1, $run->conflict_count);
            $this->assertSame('conflict', $sourceRecord->status);
            $this->assertSame('identifier_employee_code_mismatch', $conflict->type);
            $this->assertSame('open', $conflict->status);
            $this->assertSame($identifierOwner->id, $conflict->employee_id);
            $this->assertSame($codeOwner->id, $conflict->details['employee_code_owner_id']);
            $this->assertSame('EMP-A', $identifierOwner->fresh()->employee_code);
            $this->assertSame('EMP-B', $codeOwner->fresh()->employee_code);
        } finally {
            $this->removeFile($path);
        }
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

        $path = $this->writeJson([]);

        try {
            $run = app(EmployeeJsonSyncService::class)->sync(
                path: $path,
                sourceSystem: 'talenta',
                sourceInstance: 'production',
                commit: true,
            );

            $this->assertSame(0, $run->total_records);
            $this->assertTrue($employee->fresh()->is_active);
        } finally {
            $this->removeFile($path);
        }
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
