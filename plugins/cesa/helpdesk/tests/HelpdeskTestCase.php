<?php

namespace Cesa\Helpdesk\Tests;

use Cesa\Helpdesk\Database\Seeders\DatabaseSeeder;
use Cesa\Helpdesk\HelpdeskServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class HelpdeskTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
        $this->app->register(HelpdeskServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_100944_create_user_allowed_companies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2024_12_10_101127_add_default_company_id_column_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/helpdesk/database/migrations',
            '--realpath' => false,
        ]);

        $this->seed(DatabaseSeeder::class);
    }
}
