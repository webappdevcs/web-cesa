<?php

namespace Cesa\Kepegawaian\Console\Commands;

use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Services\EmployeeJsonSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncEmployeeJson extends Command
{
    protected $signature = 'kepegawaian:sync-employees-json
                            {path : Path to the vendor employee JSON file}
                            {--source-system=talenta : Stable source system name}
                            {--source-instance=production : Stable source instance name}
                            {--commit : Persist safe identity links and inactive new employees}';

    protected $description = 'Stage and reconcile employee JSON data against the canonical employee registry';

    public function handle(EmployeeJsonSyncService $service): int
    {
        $commit = (bool) $this->option('commit');

        $this->components->info(__(
            'kepegawaian::console.employee_sync.starting',
            ['mode' => $this->modeLabel($commit)]
        ));

        try {
            $run = $service->sync(
                path: (string) $this->argument('path'),
                sourceSystem: (string) $this->option('source-system'),
                sourceInstance: (string) $this->option('source-instance'),
                commit: $commit,
            );
        } catch (Throwable $throwable) {
            report($throwable);
            $this->components->error(__('kepegawaian::console.employee_sync.failed'));

            return self::FAILURE;
        }

        $this->components->info(__('kepegawaian::console.employee_sync.completed'));
        $this->line(__('kepegawaian::console.employee_sync.run_uuid', ['uuid' => $run->uuid]));
        $this->line(__('kepegawaian::console.employee_sync.mode', [
            'mode' => $this->modeLabel($commit),
        ]));
        $this->table(
            [
                __('kepegawaian::console.employee_sync.metric'),
                __('kepegawaian::console.employee_sync.count'),
            ],
            $this->summaryRows($run)
        );

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function summaryRows(EmployeeSyncRun $run): array
    {
        return [
            [__('kepegawaian::console.employee_sync.total'), $run->total_records],
            [__('kepegawaian::console.employee_sync.matched'), $run->matched_count],
            [__('kepegawaian::console.employee_sync.linked'), $run->linked_count],
            [__('kepegawaian::console.employee_sync.created'), $run->created_count],
            [__('kepegawaian::console.employee_sync.would_link'), $run->would_link_count],
            [__('kepegawaian::console.employee_sync.would_create'), $run->would_create_count],
            [__('kepegawaian::console.employee_sync.conflicts'), $run->conflict_count],
            [__('kepegawaian::console.employee_sync.invalid'), $run->invalid_count],
        ];
    }

    private function modeLabel(bool $commit): string
    {
        return $commit
            ? __('kepegawaian::console.employee_sync.mode_commit')
            : __('kepegawaian::console.employee_sync.mode_dry_run');
    }
}
