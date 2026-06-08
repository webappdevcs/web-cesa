<?php

namespace Cesa\WhatsAppAuth\Tests;

use Cesa\WhatsAppAuth\WhatsAppAuthServiceProvider;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

abstract class WhatsAppAuthTestCase extends TestCase
{
    use UsesSqliteInMemoryDatabase;

    protected string $sqliteDatabasePath;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        $this->sqliteDatabasePath = tempnam(sys_get_temp_dir(), 'whatsapp-auth-test-');

        config([
            'database.default'            => 'sqlite',
            'database.connections.sqlite' => [
                'driver'                  => 'sqlite',
                'database'                => $this->sqliteDatabasePath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
            'whatsapp-auth.gateway'       => 'log',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->app->register(WhatsAppAuthServiceProvider::class, true);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/0001_01_01_000000_create_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/whatsapp-auth/database/migrations',
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

    protected function createUser(array $attributes = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name'       => 'Test Admin',
            'email'      => 'admin'.uniqid().'@example.com',
            'password'   => bcrypt('password'),
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
