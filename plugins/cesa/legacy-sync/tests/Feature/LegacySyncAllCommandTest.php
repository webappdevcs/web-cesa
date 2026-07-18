<?php

namespace Cesa\LegacySync\Tests\Feature;

use Cesa\LegacySync\Console\Commands\SyncAllLegacyData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;

class LegacySyncAllCommandTest extends TestCase
{
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();
    }

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
            '--document-database'        => 'app_cesa',
            '--form-transfer-database'   => 'app_cesa',
            '--exit-clearance-database'  => 'app_cesa',
            '--lead-database'            => 'app_lead',
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
                'command'   => 'document:install',
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
                'command'   => 'lead:install',
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
            $this->buildExpectedSyncCall(['document'], 'app_cesa'),
            $this->buildExpectedSyncCall(['form-transfer'], 'app_cesa'),
            $this->buildExpectedSyncCall(['exit-clearance'], 'app_cesa'),
            $this->buildExpectedSyncCall(['lead'], 'app_lead'),
            $this->buildExpectedSyncCall(['presensi'], 'app_presensi'),
            $this->buildExpectedSyncCall(['shelf'], 'app_shelf'),
            $this->buildExpectedSyncCall(['helpdesk'], 'app_helpdesk'),
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
            'legacy:sync',
            'legacy:sync',
            'legacy:sync',
        ], $command->recordedCommands);
    }

    /**
     * @param  array<int, string>  $modules
     * @return array{command: string, arguments: array<string, mixed>}
     */
    protected function buildExpectedSyncCall(array $modules, string $database): array
    {
        return [
            'command'   => 'legacy:sync',
            'arguments' => [
                '--module'                    => $modules,
                '--connection'                => 'legacy_sync',
                '--database'                  => $database,
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
        ];
    }
}
