<?php

namespace Cesa\Kepegawaian\Tests;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Policies\EmployeePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class KepegawaianIdentityTestCase extends TestCase
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
            ])->assertExitCode(0);
        }

        if (! Route::has('admin.api.v1.kepegawaian.employees.index')) {
            require base_path('plugins/cesa/kepegawaian/routes/api.php');
        }

        Gate::policy(Employee::class, EmployeePolicy::class);
    }

    /**
     * @return array<int, string>
     */
    protected function migrationPaths(): array
    {
        return [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2024_11_04_132945_create_permission_tables.php',
            'database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php',
            'database/migrations/2026_01_28_134402_create_personal_access_tokens_table.php',
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
