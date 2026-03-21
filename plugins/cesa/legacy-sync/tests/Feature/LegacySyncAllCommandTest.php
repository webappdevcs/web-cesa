<?php

namespace Cesa\LegacySync\Tests\Feature;

use Cesa\LegacySync\Console\Commands\SyncAllLegacyData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class LegacySyncAllCommandTest extends TestCase
{
    public function test_it_installs_required_plugins_and_syncs_all_legacy_sources_in_one_command(): void
    {
        $command = new class extends SyncAllLegacyData
        {
            /**
             * @var array<int, array{command: string, arguments: array<string, mixed>}>
             */
            public array $recordedCalls = [];

            protected function shouldRunInstallCommand(string $plugin): bool
            {
                return true;
            }

            protected function callCommand(string $command, array $arguments = []): int
            {
                $this->recordedCalls[] = [
                    'command'   => $command,
                    'arguments' => $arguments,
                ];

                return self::SUCCESS;
            }
        };

        $command->setLaravel($this->app);

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            '--host'                     => '127.0.0.1',
            '--port'                     => '3306',
            '--username'                 => 'root',
            '--password'                 => 'secret',
            '--old-database'             => 'app_old',
            '--presensi-database'        => 'app_presensi',
            '--shelf-database'           => 'app_shelf',
            '--helpdesk-database'        => 'app_helpdesk',
            '--truncate'                 => true,
            '--chunk'                    => '500',
            '--skip-missing-users'       => true,
            '--trust-legacy-user-ids'    => true,
            '--trust-legacy-company-ids' => true,
        ], [
            'interactive' => false,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame([
            [
                'command'   => 'kepegawaian:install',
                'arguments' => ['--no-interaction' => true],
            ],
            [
                'command'   => 'exit-clearance:install',
                'arguments' => ['--no-interaction' => true],
            ],
            [
                'command'   => 'form-transfer:install',
                'arguments' => ['--no-interaction' => true],
            ],
            [
                'command'   => 'presensi:install',
                'arguments' => ['--no-interaction' => true],
            ],
            [
                'command'   => 'payroll:install',
                'arguments' => ['--no-interaction' => true],
            ],
            [
                'command'   => 'shelf:install',
                'arguments' => ['--no-interaction' => true],
            ],
            [
                'command'   => 'helpdesk:install',
                'arguments' => ['--no-interaction' => true],
            ],
            [
                'command'   => 'legacy:sync',
                'arguments' => [
                    '--module'                    => ['form-transfer', 'exit-clearance'],
                    '--connection'                => 'legacy_sync',
                    '--database'                  => 'app_old',
                    '--chunk'                     => '500',
                    '--no-interaction'            => true,
                    '--host'                      => '127.0.0.1',
                    '--port'                      => '3306',
                    '--username'                  => 'root',
                    '--password'                  => 'secret',
                    '--truncate'                  => true,
                    '--skip-missing-users'        => true,
                    '--trust-legacy-user-ids'     => true,
                    '--trust-legacy-company-ids'  => true,
                ],
            ],
            [
                'command'   => 'legacy:sync',
                'arguments' => [
                    '--module'                    => ['presensi'],
                    '--connection'                => 'legacy_sync',
                    '--database'                  => 'app_presensi',
                    '--chunk'                     => '500',
                    '--no-interaction'            => true,
                    '--host'                      => '127.0.0.1',
                    '--port'                      => '3306',
                    '--username'                  => 'root',
                    '--password'                  => 'secret',
                    '--truncate'                  => true,
                    '--skip-missing-users'        => true,
                    '--trust-legacy-user-ids'     => true,
                    '--trust-legacy-company-ids'  => true,
                ],
            ],
            [
                'command'   => 'legacy:sync',
                'arguments' => [
                    '--module'                    => ['shelf'],
                    '--connection'                => 'legacy_sync',
                    '--database'                  => 'app_shelf',
                    '--chunk'                     => '500',
                    '--no-interaction'            => true,
                    '--host'                      => '127.0.0.1',
                    '--port'                      => '3306',
                    '--username'                  => 'root',
                    '--password'                  => 'secret',
                    '--truncate'                  => true,
                    '--skip-missing-users'        => true,
                    '--trust-legacy-user-ids'     => true,
                    '--trust-legacy-company-ids'  => true,
                ],
            ],
            [
                'command'   => 'legacy:sync',
                'arguments' => [
                    '--module'                    => ['helpdesk'],
                    '--connection'                => 'legacy_sync',
                    '--database'                  => 'app_helpdesk',
                    '--chunk'                     => '500',
                    '--no-interaction'            => true,
                    '--host'                      => '127.0.0.1',
                    '--port'                      => '3306',
                    '--username'                  => 'root',
                    '--password'                  => 'secret',
                    '--truncate'                  => true,
                    '--skip-missing-users'        => true,
                    '--trust-legacy-user-ids'     => true,
                    '--trust-legacy-company-ids'  => true,
                ],
            ],
        ], $command->recordedCalls);
    }

    public function test_it_can_skip_plugin_installation_before_running_sync_jobs(): void
    {
        $command = new class extends SyncAllLegacyData
        {
            /**
             * @var array<int, string>
             */
            public array $recordedCommands = [];

            protected function shouldRunInstallCommand(string $plugin): bool
            {
                return true;
            }

            protected function callCommand(string $command, array $arguments = []): int
            {
                $this->recordedCommands[] = $command;

                return self::SUCCESS;
            }
        };

        $command->setLaravel($this->app);

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            '--skip-install' => true,
        ], [
            'interactive' => false,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame([
            'legacy:sync',
            'legacy:sync',
            'legacy:sync',
            'legacy:sync',
        ], $command->recordedCommands);
    }
}
