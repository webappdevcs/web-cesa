<?php

namespace Cesa\LegacySync\Tests;

use Cesa\ExitClearance\ExitClearanceServiceProvider;
use Cesa\FormTransfer\FormTransferServiceProvider;
use Cesa\Helpdesk\HelpdeskServiceProvider;
use Cesa\LegacySync\LegacySyncServiceProvider;
use Cesa\Presensi\PresensiServiceProvider;
use Cesa\Shelf\ShelfServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class LegacySyncTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected string $legacyDatabasePath;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        config([
            'database.default'                     => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->legacyDatabasePath = tempnam(sys_get_temp_dir(), 'legacy-sync-');

        config([
            'legacy-sync.connections.legacy_sync' => [
                'driver'                  => 'sqlite',
                'database'                => $this->legacyDatabasePath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
            'database.connections.legacy_sync' => [
                'driver'                  => 'sqlite',
                'database'                => $this->legacyDatabasePath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        $this->app->register(FormTransferServiceProvider::class);
        $this->app->register(ExitClearanceServiceProvider::class);
        $this->app->register(PresensiServiceProvider::class);
        $this->app->register(HelpdeskServiceProvider::class);
        $this->app->register(ShelfServiceProvider::class);
        $this->app->register(LegacySyncServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/0001_01_01_000000_create_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/2024_11_04_132945_create_permission_tables.php',
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

        if (! Schema::hasTable('partners_partners')) {
            $this->artisan('migrate', [
                '--path'     => 'plugins/webkul/partners/database/migrations',
                '--realpath' => false,
            ]);
        }

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/form-transfer/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/exit-clearance/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/presensi/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/helpdesk/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/shelf/database/migrations',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/legacy-sync/database/migrations',
            '--realpath' => false,
        ]);

        DB::purge('legacy_sync');
        DB::reconnect('legacy_sync');

        $this->createLegacySchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->legacyDatabasePath) && is_file($this->legacyDatabasePath)) {
            @unlink($this->legacyDatabasePath);
        }
    }

    abstract protected function createLegacySchema(): void;
}
