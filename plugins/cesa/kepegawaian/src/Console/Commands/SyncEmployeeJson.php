<?php

namespace Cesa\Kepegawaian\Console\Commands;

use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Services\EmployeeJsonSyncService;
use Illuminate\Console\Command;
use Throwable;
use Webkul\Security\Models\User;

class SyncEmployeeJson extends Command
{
    protected $signature = 'kepegawaian:sync-employees-json
                            {path? : Path to the vendor employee JSON file for staging}
                            {--source-system=talenta : Stable source system name}
                            {--source-instance=production : Stable source instance name}
                            {--commit-run= : UUID of a completed dry-run to commit}
                            {--actor= : User ID accountable for the commit}
                            {--reason= : Audited reason for committing the reviewed run}';

    protected $description = 'Stage employee JSON or atomically commit a reviewed dry-run';

    public function handle(EmployeeJsonSyncService $service): int
    {
        $commitRunUuid = trim((string) $this->option('commit-run'));
        $isCommit = $commitRunUuid !== '';

        $this->components->info(__(
            'kepegawaian::console.employee_sync.starting',
            ['mode' => $this->modeLabel($isCommit)]
        ));

        try {
            $run = $isCommit
                ? $this->commitReviewedRun($service, $commitRunUuid)
                : $this->stageSourceFile($service);
        } catch (Throwable $throwable) {
            report($throwable);
            $this->components->error(__('kepegawaian::console.employee_sync.failed'));

            return self::FAILURE;
        }

        $this->components->info(__('kepegawaian::console.employee_sync.completed'));
        $this->line(__('kepegawaian::console.employee_sync.run_uuid', ['uuid' => $run->uuid]));
        $this->line(__('kepegawaian::console.employee_sync.mode', [
            'mode' => $this->modeLabel($run->mode === 'commit'),
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

    private function stageSourceFile(EmployeeJsonSyncService $service): EmployeeSyncRun
    {
        $path = trim((string) $this->argument('path'));

        if ($path === '') {
            throw new \LogicException('A source path is required when staging employee JSON.');
        }

        if ($this->option('actor') !== null || $this->option('reason') !== null) {
            throw new \LogicException('Actor and reason options are only accepted with --commit-run.');
        }

        return $service->stage(
            path: $path,
            sourceSystem: (string) $this->option('source-system'),
            sourceInstance: (string) $this->option('source-instance'),
        );
    }

    private function commitReviewedRun(
        EmployeeJsonSyncService $service,
        string $commitRunUuid,
    ): EmployeeSyncRun {
        if ($this->argument('path') !== null) {
            throw new \LogicException('A path cannot be combined with --commit-run.');
        }

        $actorId = filter_var($this->option('actor'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $reason = trim((string) $this->option('reason'));

        if (! is_int($actorId) || $reason === '') {
            throw new \LogicException('A valid actor and reason are required for employee sync commits.');
        }

        $actor = User::query()
            ->where('is_active', true)
            ->findOrFail($actorId);
        $reviewedRun = EmployeeSyncRun::query()
            ->where('uuid', $commitRunUuid)
            ->firstOrFail();

        return $service->commitReviewed(
            reviewedRun: $reviewedRun,
            actor: $actor,
            reason: $reason,
            channel: 'console',
        );
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
