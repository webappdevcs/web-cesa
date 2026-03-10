<?php

namespace Cesa\ExitClearance\Tests;

use Cesa\ExitClearance\ExitClearanceServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class ExitClearanceTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
        $this->app->register(ExitClearanceServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/exit-clearance/database/migrations',
            '--realpath' => false,
        ]);
    }
}
