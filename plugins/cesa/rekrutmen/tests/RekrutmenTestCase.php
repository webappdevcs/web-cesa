<?php

namespace Cesa\Rekrutmen\Tests;

use Cesa\Rekrutmen\RekrutmenServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

        if (! Route::has('rekrutmen.public.request-man-power.progress')) {
            require base_path('plugins/cesa/rekrutmen/routes/web.php');
        }

        if (! Route::has('api.rekrutmen.job-postings.index')) {
            require base_path('plugins/cesa/rekrutmen/routes/api.php');
        }

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/rekrutmen/database/migrations',
            '--realpath' => false,
        ]);
    }
}
