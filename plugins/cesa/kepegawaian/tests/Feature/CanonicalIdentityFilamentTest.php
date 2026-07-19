<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Filament\Resources\EmployeeResource;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers\EmployeeIdentifierRelationManager;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Policies\EmployeeIdentifierPolicy;
use Cesa\Kepegawaian\Policies\EmployeePolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Webkul\Security\Models\User;

class CanonicalIdentityFilamentTest extends TestCase
{
    public function test_employee_resource_exposes_uuid_search_and_identifier_management(): void
    {
        $this->assertContains('uuid', EmployeeResource::getGloballySearchableAttributes());
        $this->assertContains(
            EmployeeIdentifierRelationManager::class,
            EmployeeResource::getRelations()
        );
    }

    public function test_identifier_policy_is_registered(): void
    {
        Gate::policy(EmployeeIdentifier::class, EmployeeIdentifierPolicy::class);

        $this->assertInstanceOf(
            EmployeeIdentifierPolicy::class,
            Gate::getPolicyFor(EmployeeIdentifier::class)
        );
    }

    public function test_identifier_mutations_require_a_dedicated_permission(): void
    {
        $policy = new EmployeeIdentifierPolicy;
        $identifier = new EmployeeIdentifier;
        $genericEditor = $this->userWithAbilities(['update_kepegawaian_employee']);
        $identityManager = $this->userWithAbilities(['manage_identifiers_kepegawaian_employee']);

        $this->assertFalse($policy->create($genericEditor));
        $this->assertFalse($policy->update($genericEditor, $identifier));
        $this->assertTrue($policy->create($identityManager));
        $this->assertTrue($policy->update($identityManager, $identifier));
    }

    public function test_identifier_interface_is_translated_in_english_and_indonesian(): void
    {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            $title = __('kepegawaian::filament/resources/employee/relation-manager/identifier.title');
            $create = __('kepegawaian::filament/resources/employee/relation-manager/identifier.actions.create');

            $this->assertNotSame(
                'kepegawaian::filament/resources/employee/relation-manager/identifier.title',
                $title
            );
            $this->assertNotSame(
                'kepegawaian::filament/resources/employee/relation-manager/identifier.actions.create',
                $create
            );
        }
    }

    public function test_canonical_employees_cannot_be_permanently_deleted(): void
    {
        $user = new class extends User
        {
            public function can($ability, $arguments = []): bool
            {
                return true;
            }
        };

        $this->assertFalse((new EmployeePolicy)->forceDeleteAny($user));

        $shield = require base_path('plugins/cesa/kepegawaian/config/filament-shield.php');
        $permissions = $shield['resources']['manage'][EmployeeResource::class];

        $this->assertNotContains('force_delete', $permissions);
        $this->assertNotContains('force_delete_any', $permissions);
        $this->assertContains('manage_identifiers', $permissions);
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function userWithAbilities(array $abilities): User
    {
        $user = new class extends User
        {
            /** @var array<int, string> */
            public array $abilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        };
        $user->abilities = $abilities;

        return $user;
    }
}
