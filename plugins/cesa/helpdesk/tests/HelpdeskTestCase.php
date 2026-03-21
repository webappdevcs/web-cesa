<?php

namespace Cesa\Helpdesk\Tests;

use Cesa\Helpdesk\Database\Seeders\DatabaseSeeder;
use Cesa\Helpdesk\HelpdeskServiceProvider;
use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Models\Priority;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Cesa\Helpdesk\Models\Unit;
use Cesa\Helpdesk\Policies\CommentPolicy;
use Cesa\Helpdesk\Policies\PriorityPolicy;
use Cesa\Helpdesk\Policies\ProblemCategoryPolicy;
use Cesa\Helpdesk\Policies\TicketPolicy;
use Cesa\Helpdesk\Policies\TicketStatusPolicy;
use Cesa\Helpdesk\Policies\UnitPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class HelpdeskTestCase extends TestCase
{
    use UsesSqliteInMemoryDatabase;

    protected string $sqliteDatabasePath;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        $this->sqliteDatabasePath = tempnam(sys_get_temp_dir(), 'helpdesk-test-');

        config([
            'database.default'            => 'sqlite',
            'database.connections.sqlite' => [
                'driver'                  => 'sqlite',
                'database'                => $this->sqliteDatabasePath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->app->register(HelpdeskServiceProvider::class);

        if (! Route::has('admin.api.v1.helpdesk.meta')) {
            require base_path('plugins/cesa/helpdesk/routes/api.php');
        }

        Gate::policy(Priority::class, PriorityPolicy::class);
        Gate::policy(TicketStatus::class, TicketStatusPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(ProblemCategory::class, ProblemCategoryPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/0001_01_01_000000_create_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_04_132945_create_permission_tables.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2026_01_14_151113_create_notifications_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2026_01_28_134402_create_personal_access_tokens_table.php',
            '--realpath' => false,
        ]);

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
            '--path'     => 'plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_industries_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_titles_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2025_01_07_125015_add_partner_id_to_companies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2024_12_10_101127_add_default_company_id_column_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2024_12_13_130906_add_partner_id_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2025_08_01_073954_alter_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/helpdesk/database/migrations',
            '--realpath' => false,
        ]);

        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->sqliteDatabasePath) && is_file($this->sqliteDatabasePath)) {
            @unlink($this->sqliteDatabasePath);
        }
    }
}
