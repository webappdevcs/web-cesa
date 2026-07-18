<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Database\Seeders\EmployeeSeeder;
use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Department;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeJobPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

class EmployeeSeederTest extends TestCase
{
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        config([
            'database.default'                     => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        foreach ($this->migrationPaths() as $migrationPath) {
            $this->artisan('migrate', [
                '--path'     => $migrationPath,
                '--realpath' => false,
            ]);
        }
    }

    public function test_it_seeds_employee_master_data_without_creating_accounts_or_deleting_existing_employees(): void
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

            $unrelatedEmployeeId = DB::table('employees_employees')->insertGetId([
                'uuid'          => (string) Str::orderedUuid(),
                'name'          => 'Existing Employee',
                'employee_code' => 'KEEP-001',
                'work_email'    => 'existing.employee@example.com',
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $existingPassword = Hash::make('unique-existing-password');
            $existingUser = User::factory()->create([
                'name'              => 'Existing Login',
                'email'             => 'seeded.employee@example.com',
                'password'          => $existingPassword,
                'is_active'         => false,
                'email_verified_at' => null,
            ]);

            $existingUser->delete();

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
            $firstSeededEmployeeId = Employee::query()
                ->where('employee_code', 'EMP-001')
                ->value('id');

            $seeder->run();

            $role = Role::query()->where('name', 'user')->first();
            $employee = Employee::query()->where('employee_code', 'EMP-001')->first();
            $unchangedUser = User::query()->withTrashed()->findOrFail($existingUser->id);

            $this->assertNull($role);
            $this->assertNotNull($employee);
            $this->assertSame($firstSeededEmployeeId, $employee->id);
            $this->assertNull($employee->user_id);
            $this->assertSame('628123456789', $employee->mobile_phone);
            $this->assertSame('seeded.employee@example.com', $employee->work_email);
            $this->assertDatabaseHas('employees_employees', [
                'id'            => $unrelatedEmployeeId,
                'employee_code' => 'KEEP-001',
            ]);
            $this->assertSame(2, Employee::query()->count());
            $this->assertSame($existingPassword, $unchangedUser->password);
            $this->assertFalse((bool) $unchangedUser->is_active);
            $this->assertNull($unchangedUser->email_verified_at);
            $this->assertNotNull($unchangedUser->deleted_at);
            $this->assertSame(0, $unchangedUser->roles()->count());
        } finally {
            if ($path !== false && is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function migrationPaths(): array
    {
        return [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2024_11_04_132945_create_permission_tables.php',
            'database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php',
            'plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php',
            'plugins/webkul/support/database/migrations/2024_12_10_092651_create_countries_table.php',
            'plugins/webkul/support/database/migrations/2024_12_10_092657_create_states_table.php',
            'plugins/webkul/support/database/migrations/2024_12_10_101420_create_banks_table.php',
            'plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_industries_table.php',
            'plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_titles_table.php',
            'plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php',
            'plugins/webkul/partners/database/migrations/2024_12_11_101420_create_partners_bank_accounts_table.php',
            'plugins/webkul/partners/database/migrations/2025_03_28_115218_add_address_columns_in_partners_partners_table.php',
            'plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php',
            'plugins/webkul/support/database/migrations/2024_12_10_100944_create_user_allowed_companies_table.php',
            'plugins/webkul/support/database/migrations/2024_12_12_114620_create_activity_plans_table.php',
            'plugins/webkul/support/database/migrations/2025_01_07_125015_add_partner_id_to_companies_table.php',
            'plugins/webkul/security/database/migrations/2024_12_10_101127_add_default_company_id_column_to_users_table.php',
            'plugins/webkul/security/database/migrations/2024_12_13_130906_add_partner_id_to_users_table.php',
            'plugins/webkul/security/database/migrations/2025_08_01_073954_alter_users_table.php',
            'plugins/cesa/kepegawaian/database/migrations',
        ];
    }
}
