<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeSourceRecord;
use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Policies\EmployeeSyncConflictPolicy;
use Cesa\Kepegawaian\Services\EmployeeSyncConflictResolver;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Webkul\Security\Models\User;

class EmployeeSyncConflictResolverTest extends KepegawaianIdentityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(EmployeeSyncConflict::class, EmployeeSyncConflictPolicy::class);
    }

    public function test_source_record_can_be_rejected_with_a_dedicated_permission_and_audit_note(): void
    {
        [$conflict, $sourceRecord] = $this->createConflict();
        $actor = $this->actor(['resolve_kepegawaian_employee::sync::conflict']);

        app(EmployeeSyncConflictResolver::class)->rejectSource(
            conflict: $conflict,
            actor: $actor,
            reasonCode: 'duplicate_source_record',
            notes: 'Vendor confirmed that this row is a duplicate.',
        );

        $conflict->refresh();

        $this->assertSame('resolved', $conflict->status);
        $this->assertSame('rejected_duplicate_source_record', $conflict->resolution);
        $this->assertSame('Vendor confirmed that this row is a duplicate.', $conflict->resolution_notes);
        $this->assertSame($actor->id, $conflict->resolved_by);
        $this->assertNotNull($conflict->resolved_at);
        $this->assertSame('conflict', $sourceRecord->fresh()->status);
        $this->assertStringNotContainsString(
            'Vendor confirmed',
            (string) DB::table('employees_sync_conflicts')
                ->where('id', $conflict->id)
                ->value('resolution_notes')
        );
    }

    public function test_recheck_resolves_only_after_the_canonical_identity_is_unambiguous(): void
    {
        [$conflict, $sourceRecord] = $this->createConflict();
        $employee = $this->createEmployee('EMP-REVIEW');
        $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-review-100',
        ]);

        $resolved = app(EmployeeSyncConflictResolver::class)->recheck(
            conflict: $conflict,
            actor: $this->actor(['resolve_kepegawaian_employee::sync::conflict']),
        );

        $this->assertTrue($resolved);
        $this->assertSame('resolved', $conflict->fresh()->status);
        $this->assertSame('rechecked_match', $conflict->fresh()->resolution);
        $this->assertSame('conflict', $sourceRecord->fresh()->status);
        $this->assertSame(1, $employee->identifiers()->count());
    }

    public function test_recheck_leaves_an_ambiguous_conflict_open_without_mutating_canonical_data(): void
    {
        [$conflict, $sourceRecord] = $this->createConflict();
        $employee = $this->createEmployee('EMP-REVIEW');

        $resolved = app(EmployeeSyncConflictResolver::class)->recheck(
            conflict: $conflict,
            actor: $this->actor(['resolve_kepegawaian_employee::sync::conflict']),
        );

        $this->assertFalse($resolved);
        $this->assertSame('open', $conflict->fresh()->status);
        $this->assertSame('conflict', $sourceRecord->fresh()->status);
        $this->assertSame(0, $employee->identifiers()->count());
    }

    public function test_actor_without_dedicated_permission_cannot_resolve_conflict(): void
    {
        [$conflict] = $this->createConflict();

        $this->expectException(AuthorizationException::class);

        app(EmployeeSyncConflictResolver::class)->rejectSource(
            conflict: $conflict,
            actor: $this->actor(),
            reasonCode: 'out_of_scope',
            notes: 'This should not be accepted.',
        );
    }

    public function test_already_resolved_conflict_cannot_be_resolved_again(): void
    {
        [$conflict] = $this->createConflict();
        $actor = $this->actor(['resolve_kepegawaian_employee::sync::conflict']);

        app(EmployeeSyncConflictResolver::class)->rejectSource(
            conflict: $conflict,
            actor: $actor,
            reasonCode: 'out_of_scope',
            notes: 'Not part of the employee master.',
        );

        $this->expectException(LogicException::class);

        app(EmployeeSyncConflictResolver::class)->recheck($conflict, $actor);
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

    /**
     * @param  array<int, string>  $abilities
     */
    private function actor(array $abilities = []): User
    {
        $persisted = User::factory()->create();

        $actor = new class extends User
        {
            /** @var array<int, string> */
            public array $abilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };

        $actor->id = $persisted->id;
        $actor->exists = true;
        $actor->abilities = $abilities;

        return $actor;
    }
}
