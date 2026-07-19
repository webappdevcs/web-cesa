<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Database\Seeders\DatabaseSeeder;
use Cesa\Kepegawaian\KepegawaianServiceProvider;
use LogicException;
use ReflectionClass;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\PluginManager\Package;

class KepegawaianInstallSafetyTest extends TestCase
{
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
    }

    public function test_install_command_runs_migrations_without_running_demo_seeders(): void
    {
        $package = new Package;
        $provider = new KepegawaianServiceProvider($this->app);

        $provider->configureCustomPackage($package);

        $this->assertContains(
            '2026_07_19_000004_harden_employee_sync_tables',
            $package->migrationFileNames,
        );
        $this->assertContains(
            '2026_07_19_000005_create_hr_workflow_tables',
            $package->migrationFileNames,
        );
        $this->assertContains(
            '2026_07_19_000006_seed_default_hr_workflow_templates',
            $package->migrationFileNames,
        );

        $installCommand = collect($package->consoleCommands)
            ->first(fn (object $command): bool => $this->readProperty($command, 'signature') === 'kepegawaian:install');

        $this->assertNotNull($installCommand);
        $this->assertTrue((bool) $this->readProperty($installCommand, 'runsMigrations'));
        $this->assertFalse((bool) $this->readProperty($installCommand, 'runsSeeders'));
    }

    public function test_database_seeder_refuses_to_run_outside_local_and_testing(): void
    {
        $this->app->instance('env', 'production');

        $this->expectException(LogicException::class);

        (new DatabaseSeeder)->run();
    }

    private function readProperty(object $target, string $name): mixed
    {
        $property = (new ReflectionClass($target))->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($target);
    }
}
