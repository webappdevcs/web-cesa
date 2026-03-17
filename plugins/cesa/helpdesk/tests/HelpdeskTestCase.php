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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
