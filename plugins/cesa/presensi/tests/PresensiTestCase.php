<?php

namespace Cesa\Presensi\Tests;

use Cesa\Presensi\PresensiServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class PresensiTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
        $this->app->register(PresensiServiceProvider::class);

        if (! \Webkul\PluginManager\Package::isPluginInstalled('presensi')) {
            require base_path('plugins/cesa/presensi/routes/api.php');
            require base_path('plugins/cesa/presensi/routes/web.php');
        }

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/presensi/database/migrations',
            '--realpath' => false,
        ]);
    }
}
