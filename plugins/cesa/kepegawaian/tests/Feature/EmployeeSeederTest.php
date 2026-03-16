<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Database\Seeders\EmployeeSeeder;
use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Department;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeJobPosition;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

class EmployeeSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_login_users_for_seeded_employees(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-seed-data-');

        file_put_contents($path, json_encode([
            [
                'branch'         => 'PT Seeded Employee',
                'organization'   => 'IT',
                'id'             => '1',
                'id_employee'    => 'EMP-001',
                'job'            => 'Developer',
                'title'          => 'Staff',
                'first_name'     => 'Seeded',
                'last_name'      => 'Employee',
                'email'          => 'seeded.employee@example.com',
                'mobile_phone'   => '+62 812-3456-789',
                'phone'          => '021555000',
                'current_address'=> 'Jl. Example No. 1',
                'marital_status' => 'single',
                'gender'         => 'male',
                'birth_date'     => '13 Mar 1998',
                'join_date'      => '13 Mar 2024',
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $currency = Currency::factory()->create([
                'name' => 'IDR',
            ]);

            $company = Company::query()->create([
                'name'        => 'PT Seeded Employee',
                'company_id'  => 'CMP-SEEDED',
                'currency_id' => $currency->id,
                'color'       => '#123456',
                'is_active'   => true,
            ]);

            $department = Department::query()->create([
                'company_id' => $company->id,
                'name'       => 'IT',
                'color'      => '#654321',
                'manager_id' => null,
            ]);

            EmployeeJobPosition::query()->create([
                'sort'               => 1,
                'name'               => 'Developer',
                'description'        => 'Seeded role',
                'requirements'       => 'Seeded requirements',
                'is_active'          => true,
                'expected_employees' => 1,
                'no_of_employee'     => 1,
                'no_of_recruitment'  => 0,
                'department_id'      => $department->id,
                'company_id'         => $company->id,
                'employment_type_id' => null,
            ]);

            $seeder = new class($path) extends EmployeeSeeder
            {
                public function __construct(
                    private readonly string $path,
                ) {}

                protected function seedData(): EmployeeSeedData
                {
                    return new EmployeeSeedData($this->path);
                }
            };

            $seeder->run();

            $role = Role::query()->where('name', 'user')->first();
            $user = User::query()->where('email', 'seeded.employee@example.com')->first();
            $employee = Employee::query()->where('employee_code', 'EMP-001')->first();

            $this->assertNotNull($role);
            $this->assertNotNull($user);
            $this->assertNotNull($employee);
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertNotNull($user->email_verified_at);
            $this->assertSame($company->id, $user->default_company_id);
            $this->assertNotNull($user->partner_id);
            $this->assertSame('seeded.employee@example.com', $user->partner?->email);
            $this->assertTrue($user->allowedCompanies()->whereKey($company->id)->exists());
            $this->assertTrue($user->roles()->whereKey($role->id)->exists());
            $this->assertSame($user->id, $employee->user_id);
            $this->assertSame('628123456789', $employee->mobile_phone);
            $this->assertSame('seeded.employee@example.com', $employee->work_email);
        } finally {
            if ($path !== false && is_file($path)) {
                unlink($path);
            }
        }
    }
}
