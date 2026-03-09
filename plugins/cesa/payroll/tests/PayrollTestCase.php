<?php

namespace Cesa\Payroll\Tests;

use Cesa\Payroll\PayrollServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class PayrollTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
        $this->app->register(PayrollServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/presensi/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/payroll/database/migrations',
            '--realpath' => false,
        ]);
    }
}
