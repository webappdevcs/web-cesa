<?php

namespace Cesa\Rekrutmen\Tests;

use Cesa\Rekrutmen\RekrutmenServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class RekrutmenTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
        $this->app->register(RekrutmenServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/rekrutmen/database/migrations',
            '--realpath' => false,
        ]);
    }
}
