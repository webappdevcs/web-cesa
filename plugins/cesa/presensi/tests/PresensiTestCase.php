<?php

namespace Cesa\Presensi\Tests;

use Cesa\Presensi\PresensiServiceProvider;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class PresensiTestCase extends TestCase
{
    use UsesSqliteInMemoryDatabase;

    protected string $sqliteDatabasePath;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        $this->sqliteDatabasePath = tempnam(sys_get_temp_dir(), 'presensi-test-');

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

        $this->app->register(PresensiServiceProvider::class);

        if (! \Webkul\PluginManager\Package::isPluginInstalled('presensi')) {
            require base_path('plugins/cesa/presensi/routes/api.php');
            require base_path('plugins/cesa/presensi/routes/web.php');
        }

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
            '--path'     => 'plugins/webkul/security/database/migrations/2024_12_13_130906_add_partner_id_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/security/database/migrations/2025_08_01_073954_alter_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/presensi/database/migrations',
            '--realpath' => false,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->sqliteDatabasePath) && is_file($this->sqliteDatabasePath)) {
            @unlink($this->sqliteDatabasePath);
        }
    }
}
