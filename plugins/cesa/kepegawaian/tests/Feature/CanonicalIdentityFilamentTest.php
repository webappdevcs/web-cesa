<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Filament\Resources\EmployeeResource;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers\EmployeeIdentifierRelationManager;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Policies\EmployeeIdentifierPolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

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
}
