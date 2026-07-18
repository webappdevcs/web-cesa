<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Laravel\Sanctum\Sanctum;
use Webkul\Security\Models\User;

class CanonicalEmployeeApiTest extends KepegawaianIdentityTestCase
{
    public function test_registry_api_requires_authentication_and_employee_permission(): void
    {
        $this->getJson('/admin/api/v1/kepegawaian/employees')
            ->assertUnauthorized();

        Sanctum::actingAs($this->apiUser());

        $this->getJson('/admin/api/v1/kepegawaian/employees')
            ->assertForbidden();
    }

    public function test_authorized_user_can_list_canonical_employees_without_private_fields(): void
    {
        Sanctum::actingAs($this->apiUser(['view_any_kepegawaian_employee']));

        Employee::query()->create([
            'name'              => 'Visible Employee',
            'employee_code'     => 'API-001',
            'job_title'         => 'GA Officer',
            'work_email'        => 'visible@company.test',
            'private_email'     => 'private@example.com',
            'private_street1'   => 'Private Address',
            'birthday'          => '1990-01-01',
            'identification_id' => 'SECRET-NIK',
            'passport_id'       => 'SECRET-PASSPORT',
            'pin'               => '123456',
            'is_active'         => true,
        ]);

        Employee::query()->create([
            'name'          => 'Second Employee',
            'employee_code' => 'API-002',
            'is_active'     => false,
        ]);

        $response = $this->getJson('/admin/api/v1/kepegawaian/employees?per_page=1&filter[is_active]=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.employee_code', 'API-001');

        $record = $response->json('data.0');

        foreach ([
            'private_email',
            'private_street1',
            'birthday',
            'identification_id',
            'passport_id',
            'pin',
            'work_email',
            'work_phone',
            'mobile_phone',
        ] as $privateField) {
            $this->assertArrayNotHasKey($privateField, $record);
        }
    }

    public function test_employee_is_shown_by_uuid_and_not_by_local_integer_id(): void
    {
        Sanctum::actingAs($this->apiUser(['view_kepegawaian_employee']));

        $employee = Employee::query()->create([
            'name'          => 'UUID Employee',
            'employee_code' => 'API-UUID',
            'is_active'     => true,
        ]);

        $this->getJson('/admin/api/v1/kepegawaian/employees/'.$employee->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $employee->uuid)
            ->assertJsonPath('data.employee_code', 'API-UUID');

        $this->getJson('/admin/api/v1/kepegawaian/employees/'.$employee->id)
            ->assertNotFound();
    }

    public function test_service_can_resolve_current_external_identifier_to_canonical_uuid(): void
    {
        Sanctum::actingAs($this->apiUser([
            'view_any_kepegawaian_employee',
            'view_kepegawaian_employee',
        ]));

        $employee = Employee::query()->create([
            'name'          => 'Resolved Employee',
            'employee_code' => 'API-RESOLVE',
            'is_active'     => true,
        ]);

        $identifier = $employee->identifiers()->create([
            'source_system'   => 'talenta',
            'source_instance' => 'production',
            'identifier_type' => 'record_id',
            'external_id'     => '2953185',
            'metadata'        => ['personal_email' => 'must-not-leak@example.com'],
        ]);

        $query = http_build_query([
            'source_system'   => 'TALENTA',
            'source_instance' => 'PRODUCTION',
            'identifier_type' => 'RECORD_ID',
            'external_id'     => ' 2953185 ',
        ]);

        $response = $this->getJson('/admin/api/v1/kepegawaian/employees/resolve?'.$query)
            ->assertOk()
            ->assertJsonPath('data.uuid', $employee->uuid)
            ->assertJsonPath('data.identifiers.0.external_id', '2953185');

        $this->assertArrayNotHasKey('metadata', $response->json('data.identifiers.0'));

        $identifier->retire();

        $this->getJson('/admin/api/v1/kepegawaian/employees/resolve?'.$query)
            ->assertNotFound();
    }

    public function test_resolver_requires_complete_source_identity(): void
    {
        Sanctum::actingAs($this->apiUser(['view_any_kepegawaian_employee']));

        $this->getJson('/admin/api/v1/kepegawaian/employees/resolve?source_system=talenta')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'source_instance',
                'identifier_type',
                'external_id',
            ]);
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function apiUser(array $abilities = []): User
    {
        $persistedUser = User::factory()->create();

        $user = new class extends User
        {
            /** @var array<int, string> */
            public array $grantedAbilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->grantedAbilities, true);
            }
        };

        $user->id = $persistedUser->id;
        $user->grantedAbilities = $abilities;

        return $user;
    }
}
