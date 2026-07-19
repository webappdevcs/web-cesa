<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers\EmployeeIdentifierRelationManager;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Policies\EmployeeIdentifierPolicy;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Webkul\Security\Models\User;

class EmployeeIdentifierAuthorizationTest extends KepegawaianIdentityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(EmployeeIdentifier::class, EmployeeIdentifierPolicy::class);
    }

    public function test_generic_employee_editor_cannot_manage_external_identifiers(): void
    {
        [$employee, $identifier] = $this->identityFixture();

        $this->actingAs($this->actor([
            'view_any_kepegawaian_employee',
            'view_kepegawaian_employee',
            'update_kepegawaian_employee',
        ]));

        Livewire::test(EmployeeIdentifierRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass'   => ViewEmployee::class,
        ])
            ->assertTableActionHidden('create')
            ->assertTableActionHidden('verify', $identifier)
            ->assertTableActionHidden('retire', $identifier);
    }

    public function test_identity_manager_can_manage_external_identifiers_without_employee_update_permission(): void
    {
        [$employee, $identifier] = $this->identityFixture();

        $this->actingAs($this->actor([
            'view_any_kepegawaian_employee',
            'view_kepegawaian_employee',
            'manage_identifiers_kepegawaian_employee',
        ]));

        Livewire::test(EmployeeIdentifierRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass'   => ViewEmployee::class,
        ])
            ->assertTableActionVisible('create')
            ->assertTableActionVisible('verify', $identifier)
            ->assertTableActionVisible('retire', $identifier);

        $identifier->retire();

        Livewire::test(EmployeeIdentifierRelationManager::class, [
            'ownerRecord' => $employee,
            'pageClass'   => ViewEmployee::class,
        ])->assertTableActionVisible('reactivate', $identifier->refresh());
    }

    /**
     * @return array{0: Employee, 1: EmployeeIdentifier}
     */
    private function identityFixture(): array
    {
        $employee = Employee::query()->create([
            'name'          => 'Identity Boundary Employee',
            'employee_code' => 'EMP-IDENTITY-BOUNDARY',
            'is_active'     => true,
        ]);
        $identifier = $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => 'vendor-identity-boundary',
        ]);

        return [$employee, $identifier];
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function actor(array $abilities): User
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
