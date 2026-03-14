<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use Cesa\Rekrutmen\RekrutmenServiceProvider;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use ReflectionClass;
use Webkul\PluginManager\Package;

class RekrutmenInstallCommandTest extends RekrutmenTestCase
{
    public function test_rekrutmen_package_registers_the_latest_migrations_for_install_and_uninstall(): void
    {
        $package = new Package;
        $provider = new RekrutmenServiceProvider($this->app);

        $provider->configureCustomPackage($package);

        $this->assertContains(
            '2026_03_12_210000_rekrutmen_add_status_response_id_to_request_man_powers_table',
            $package->migrationFileNames,
        );
    }

    public function test_rekrutmen_install_and_uninstall_commands_are_registered(): void
    {
        $package = new Package;
        $provider = new RekrutmenServiceProvider($this->app);

        $provider->configureCustomPackage($package);

        $commandSignatures = collect($package->consoleCommands)
            ->map(function (object $command): string {
                $property = (new ReflectionClass($command))->getProperty('signature');
                $property->setAccessible(true);

                return (string) $property->getValue($command);
            })
            ->all();

        $this->assertContains('rekrutmen:install', $commandSignatures);
        $this->assertContains('rekrutmen:uninstall {--force : Force the operation to run without confirmation}', $commandSignatures);
    }
}
