<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeSourceRecord;
use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Services\EmployeeSyncConflictResolver;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use LogicException;
use Webkul\Security\Models\User;

class EmployeeSyncConflictResolverTest extends KepegawaianIdentityTestCase
{
    public function test_open_conflict_can_be_ignored_with_an_audited_reason(): void
    {
        [$conflict, $sourceRecord] = $this->createConflict();
        $resolver = User::factory()->create();

        app(EmployeeSyncConflictResolver::class)->ignore(
            conflict: $conflict,
            resolvedBy: $resolver->id,
            notes: 'Vendor row is a known duplicate.',
        );

        $conflict->refresh();

        $this->assertSame('resolved', $conflict->status);
        $this->assertSame('ignored', $conflict->resolution);
        $this->assertSame('Vendor row is a known duplicate.', $conflict->resolution_notes);
        $this->assertSame($resolver->id, $conflict->resolved_by);
        $this->assertNotNull($conflict->resolved_at);
        $this->assertSame('conflict', $sourceRecord->fresh()->status);
    }

    public function test_open_conflict_can_be_linked_to_a_canonical_employee(): void
    {
        [$conflict, $sourceRecord] = $this->createConflict();
        $employee = $this->createEmployee('EMP-REVIEW');
        $resolver = User::factory()->create();

        app(EmployeeSyncConflictResolver::class)->link(
            conflict: $conflict,
            employee: $employee,
            resolvedBy: $resolver->id,
            notes: 'Verified against the signed HR workbook.',
        );

        $conflict->refresh();
        $sourceRecord->refresh();

        $this->assertSame('resolved', $conflict->status);
        $this->assertSame('linked', $conflict->resolution);
        $this->assertSame($employee->id, $conflict->employee_id);
        $this->assertSame($resolver->id, $conflict->resolved_by);
        $this->assertSame('linked', $sourceRecord->status);
        $this->assertSame('manual_review', $sourceRecord->match_strategy);
        $this->assertSame($employee->id, $sourceRecord->employee_id);
        $this->assertDatabaseHas('employees_employee_identifiers', [
            'employee_id'      => $employee->id,
            'source_system'    => 'talenta',
            'source_instance'  => 'production',
            'identifier_type'  => 'record_id',
            'normalized_value' => 'vendor-review-100',
        ]);
    }

    public function test_retired_external_identity_cannot_be_reassigned_during_review(): void
    {
        $formerOwner = $this->createEmployee('EMP-FORMER');
        $identifier = $formerOwner->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-review-100',
        ]);
        $identifier->retire();

        [$conflict, $sourceRecord] = $this->createConflict();
        $newOwner = $this->createEmployee('EMP-NEW-OWNER');

        try {
            app(EmployeeSyncConflictResolver::class)->link(
                conflict: $conflict,
                employee: $newOwner,
                resolvedBy: User::factory()->create()->id,
                notes: 'Attempted reassignment.',
            );

            $this->fail('A retired external identity must never be reassigned.');
        } catch (LogicException) {
            $this->assertSame('open', $conflict->fresh()->status);
            $this->assertSame('conflict', $sourceRecord->fresh()->status);
            $this->assertSame(0, $newOwner->identifiers()->count());
            $this->assertSame($formerOwner->id, $identifier->fresh()->employee_id);
        }
    }

    public function test_employee_cannot_receive_a_second_current_record_id_from_the_same_source(): void
    {
        $employee = $this->createEmployee('EMP-ONE-RECORD');
        $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-original',
        ]);
        [$conflict] = $this->createConflict();

        try {
            app(EmployeeSyncConflictResolver::class)->link(
                conflict: $conflict,
                employee: $employee,
                resolvedBy: User::factory()->create()->id,
                notes: 'Attempted second current record ID.',
            );

            $this->fail('A source may only have one current record ID per employee.');
        } catch (LogicException) {
            $this->assertSame('open', $conflict->fresh()->status);
            $this->assertSame(1, $employee->identifiers()->current()->count());
        }
    }

    /**
     * @return array{0: EmployeeSyncConflict, 1: EmployeeSourceRecord}
     */
    private function createConflict(): array
    {
        $run = EmployeeSyncRun::factory()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'mode'            => 'commit',
        ]);
        $sourceRecord = EmployeeSourceRecord::factory()
            ->for($run, 'syncRun')
            ->create([
                'external_id'   => 'vendor-review-100',
                'employee_code' => 'EMP-REVIEW',
                'status'        => 'conflict',
            ]);
        $conflict = EmployeeSyncConflict::factory()
            ->for($sourceRecord, 'sourceRecord')
            ->create();

        return [$conflict, $sourceRecord];
    }

    private function createEmployee(string $code): Employee
    {
        return Employee::query()->create([
            'name'          => $code,
            'employee_code' => $code,
            'is_active'     => true,
        ]);
    }
}
