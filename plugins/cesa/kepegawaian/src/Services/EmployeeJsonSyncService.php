<?php

namespace Cesa\Kepegawaian\Services;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Models\EmployeeSourceRecord;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use JsonException;
use LogicException;
use RuntimeException;
use Throwable;
use Webkul\Security\Models\User;

class EmployeeJsonSyncService
{
    private const int MAX_FILE_BYTES = 25 * 1024 * 1024;

    private const int MAX_RECORD_BYTES = 256 * 1024;

    private const int SOURCE_LOCK_SECONDS = 300;

    public function __construct(
        private readonly EmployeeSeedData $normalizer,
    ) {}

    public function stage(
        string $path,
        string $sourceSystem,
        string $sourceInstance,
        ?int $initiatedBy = null,
    ): EmployeeSyncRun {
        $sourceSystem = $this->normalizeSource($sourceSystem, 64, 'Source system');
        $sourceInstance = $this->normalizeSource($sourceInstance, 191, 'Source instance');
        $contents = $this->readSourceBuffer($path);
        $checksum = hash('sha256', $contents);

        $run = EmployeeSyncRun::query()->create([
            'source_system'   => $sourceSystem,
            'source_instance' => $sourceInstance,
            'mode'            => 'dry_run',
            'status'          => 'running',
            'file_name'       => basename($path),
            'file_checksum'   => $checksum,
            'channel'         => 'console',
            'initiated_by'    => $initiatedBy ?? Auth::id(),
            'started_at'      => now(),
        ]);

        $counts = $this->emptyCounts();

        try {
            $records = $this->decodeRecords($contents);
            $counts['total_records'] = count($records);

            foreach ($records as $index => $rawRecord) {
                DB::transaction(function () use (
                    $run,
                    $index,
                    $rawRecord,
                    $sourceSystem,
                    $sourceInstance,
                    &$counts,
                ): void {
                    $this->processRecord(
                        run: $run,
                        rowNumber: $index + 1,
                        rawRecord: $rawRecord,
                        sourceSystem: $sourceSystem,
                        sourceInstance: $sourceInstance,
                        apply: false,
                        counts: $counts,
                    );
                });
            }

            $sourceRecords = $run->sourceRecords()
                ->orderBy('row_number')
                ->get();

            $run->forceFill([
                ...$counts,
                'manifest_hash'=> $this->manifestHash($run, $sourceRecords),
                'status'       => 'completed',
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $throwable) {
            $this->markRunFailed($run, $counts, $throwable);

            throw $throwable;
        }

        return $run->refresh();
    }

    public function commitReviewed(
        EmployeeSyncRun $reviewedRun,
        User $actor,
        string $reason,
        string $channel = 'console',
    ): EmployeeSyncRun {
        if (! $actor->exists || $actor->getKey() === null) {
            throw new LogicException('A persisted user must approve an employee sync commit.');
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 2000) {
            throw new LogicException('Employee sync commit reason must contain between 5 and 2000 characters.');
        }

        $channel = $this->normalizeSource($channel, 32, 'Commit channel');
        $lock = Cache::lock(
            'kepegawaian:employee-sync:'.hash('sha256', $reviewedRun->source_system."\0".$reviewedRun->source_instance),
            self::SOURCE_LOCK_SECONDS,
        );

        if (! $lock->get()) {
            throw new LogicException('Another employee sync commit is already running for this source.');
        }

        try {
            return $this->commitWithinSourceLock($reviewedRun, $actor, $reason, $channel);
        } finally {
            $lock->release();
        }
    }

    private function commitWithinSourceLock(
        EmployeeSyncRun $reviewedRun,
        User $actor,
        string $reason,
        string $channel,
    ): EmployeeSyncRun {
        $attemptStarted = false;

        try {
            return DB::transaction(function () use (
                $reviewedRun,
                $actor,
                $reason,
                $channel,
                &$attemptStarted,
            ): EmployeeSyncRun {
                $lockedReview = EmployeeSyncRun::query()
                    ->lockForUpdate()
                    ->findOrFail($reviewedRun->getKey());

                $this->assertReviewCanBeCommitted($lockedReview);
                Gate::forUser($actor)->authorize('commit', $lockedReview);

                $sourceRecords = $lockedReview->sourceRecords()
                    ->orderBy('row_number')
                    ->get();
                $counts = $this->emptyCounts();
                $counts['total_records'] = $sourceRecords->count();

                if ($counts['total_records'] !== $lockedReview->total_records) {
                    throw new LogicException('Reviewed employee sync staging is incomplete.');
                }

                if (
                    ! is_string($lockedReview->manifest_hash)
                    || ! hash_equals(
                        $lockedReview->manifest_hash,
                        $this->manifestHash($lockedReview, $sourceRecords),
                    )
                ) {
                    throw new LogicException('Reviewed employee sync staging manifest does not match.');
                }

                $commitRun = EmployeeSyncRun::query()->create([
                    'source_system'   => $lockedReview->source_system,
                    'source_instance' => $lockedReview->source_instance,
                    'mode'            => 'commit',
                    'status'          => 'running',
                    'file_name'       => $lockedReview->file_name,
                    'file_checksum'   => $lockedReview->file_checksum,
                    'reviewed_run_id' => $lockedReview->id,
                    'channel'         => $channel,
                    'commit_reason'   => $reason,
                    'initiated_by'    => $actor->getKey(),
                    'started_at'      => now(),
                ]);
                $attemptStarted = true;

                foreach ($sourceRecords as $sourceRecord) {
                    $this->processRecord(
                        run: $commitRun,
                        rowNumber: $sourceRecord->row_number,
                        rawRecord: $sourceRecord->payload,
                        sourceSystem: $lockedReview->source_system,
                        sourceInstance: $lockedReview->source_instance,
                        apply: true,
                        counts: $counts,
                    );
                }

                $committedRecords = $commitRun->sourceRecords()
                    ->orderBy('row_number')
                    ->get();

                $commitRun->forceFill([
                    ...$counts,
                    'manifest_hash'=> $this->manifestHash($commitRun, $committedRecords),
                    'status'       => 'completed',
                    'completed_at' => now(),
                ])->save();

                return $commitRun->refresh();
            }, 3);
        } catch (Throwable $throwable) {
            if ($attemptStarted) {
                $this->recordFailedCommitAttempt($reviewedRun, $actor, $reason, $channel, $throwable);
            }

            throw $throwable;
        }
    }

    private function assertReviewCanBeCommitted(EmployeeSyncRun $reviewedRun): void
    {
        if ($reviewedRun->mode !== 'dry_run' || $reviewedRun->status !== 'completed') {
            throw new LogicException('Only a completed employee sync dry-run may be committed.');
        }

        $alreadyCommitted = EmployeeSyncRun::query()
            ->where('reviewed_run_id', $reviewedRun->id)
            ->where('mode', 'commit')
            ->where('status', 'completed')
            ->exists();

        if ($alreadyCommitted) {
            throw new LogicException('This employee sync dry-run has already been committed.');
        }
    }

    /**
     * @param  iterable<int, EmployeeSourceRecord>  $sourceRecords
     */
    private function manifestHash(EmployeeSyncRun $run, iterable $sourceRecords): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new LogicException('APP_KEY is required to seal employee sync staging manifests.');
        }

        $context = hash_init('sha256', HASH_HMAC, $key);
        hash_update($context, json_encode([
            'uuid'            => $run->uuid,
            'source_system'   => $run->source_system,
            'source_instance' => $run->source_instance,
            'file_checksum'   => $run->file_checksum,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n");

        foreach ($sourceRecords as $sourceRecord) {
            hash_update($context, json_encode([
                'row_number'         => $sourceRecord->row_number,
                'external_id'        => $sourceRecord->getRawOriginal('external_id'),
                'external_id_hash'   => $sourceRecord->getRawOriginal('external_id_hash'),
                'employee_code'      => $sourceRecord->getRawOriginal('employee_code'),
                'employee_code_hash' => $sourceRecord->getRawOriginal('employee_code_hash'),
                'checksum'           => $sourceRecord->getRawOriginal('checksum'),
                'payload'            => $sourceRecord->getRawOriginal('payload'),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n");
        }

        return hash_final($context);
    }

    private function recordFailedCommitAttempt(
        EmployeeSyncRun $reviewedRun,
        User $actor,
        string $reason,
        string $channel,
        Throwable $throwable,
    ): void {
        try {
            EmployeeSyncRun::query()->create([
                'source_system'   => $reviewedRun->source_system,
                'source_instance' => $reviewedRun->source_instance,
                'mode'            => 'commit',
                'status'          => 'failed',
                'file_name'       => $reviewedRun->file_name,
                'file_checksum'   => $reviewedRun->file_checksum,
                'reviewed_run_id' => $reviewedRun->id,
                'channel'         => $channel,
                'commit_reason'   => $reason,
                'total_records'   => $reviewedRun->total_records,
                'error_message'   => $throwable->getMessage(),
                'initiated_by'    => $actor->getKey(),
                'started_at'      => now(),
                'completed_at'    => now(),
            ]);
        } catch (Throwable $auditFailure) {
            report($auditFailure);
        }
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function processRecord(
        EmployeeSyncRun $run,
        int $rowNumber,
        mixed $rawRecord,
        string $sourceSystem,
        string $sourceInstance,
        bool $apply,
        array &$counts,
    ): void {
        $payload = is_array($rawRecord) ? $rawRecord : ['value' => $rawRecord];
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadTooLarge = strlen($payloadJson) > self::MAX_RECORD_BYTES;
        $normalized = ! $payloadTooLarge && is_array($rawRecord)
            ? $this->normalizer->normalizeRecord($rawRecord)
            : null;

        $sourceRecord = $run->sourceRecords()->create([
            'row_number'    => $rowNumber,
            'external_id'   => $normalized['source_id'] ?? null,
            'employee_code' => $normalized['employee_code'] ?? null,
            'checksum'      => hash('sha256', $payloadJson),
            'payload'       => $payloadTooLarge
                ? ['redacted' => true, 'reason' => 'record_exceeds_256_kb']
                : $payload,
            'status'        => 'processing',
        ]);

        if ($payloadTooLarge) {
            $this->recordConflict(
                $sourceRecord,
                type: 'invalid_record',
                details: ['reason' => 'Record exceeds the 256 KB staging limit.'],
            );
            $counts['conflict_count']++;
            $counts['invalid_count']++;

            return;
        }

        if ($normalized === null) {
            $this->recordConflict(
                $sourceRecord,
                type: 'invalid_record',
                details: ['reason' => 'Employee code and name are required.'],
            );
            $counts['conflict_count']++;
            $counts['invalid_count']++;

            return;
        }

        $externalId = $this->normalizeKey((string) ($normalized['source_id'] ?? ''));
        $employeeCode = Str::squish((string) $normalized['employee_code']);

        if ($externalId === '') {
            $this->recordConflict(
                $sourceRecord,
                type: 'missing_external_id',
                details: ['employee_code' => $employeeCode],
            );
            $counts['conflict_count']++;
            $counts['invalid_count']++;

            return;
        }

        if (
            mb_strlen($externalId) > 191
            || mb_strlen($employeeCode) > 191
            || mb_strlen((string) $normalized['name']) > 255
        ) {
            $this->recordConflict(
                $sourceRecord,
                type: 'invalid_record',
                details: ['reason' => 'Identity field exceeds the canonical length limit.'],
            );
            $counts['conflict_count']++;
            $counts['invalid_count']++;

            return;
        }

        $identifier = EmployeeIdentifier::query()
            ->where('source_system', $sourceSystem)
            ->where('source_instance', $sourceInstance)
            ->where('identifier_type', 'record_id')
            ->where('normalized_value', $externalId)
            ->first();

        if ($identifier !== null && $identifier->retired_at !== null) {
            $employee = Employee::query()->withTrashed()->find($identifier->employee_id);

            $this->recordConflict(
                $sourceRecord,
                type: 'retired_identifier_match',
                details: ['employee_code' => $employeeCode],
                employee: $employee,
            );
            $counts['conflict_count']++;

            return;
        }

        $employeeByCode = Employee::query()
            ->withTrashed()
            ->where('employee_code', $employeeCode)
            ->first();

        if ($identifier !== null) {
            $identifierEmployee = Employee::query()
                ->withTrashed()
                ->findOrFail($identifier->employee_id);

            if ($employeeByCode !== null && $employeeByCode->isNot($identifierEmployee)) {
                $this->recordConflict(
                    $sourceRecord,
                    type: 'identifier_employee_code_mismatch',
                    details: [
                        'identifier_owner_id'    => $identifierEmployee->id,
                        'employee_code_owner_id' => $employeeByCode->id,
                        'employee_code'          => $employeeCode,
                    ],
                    employee: $identifierEmployee,
                );
                $counts['conflict_count']++;

                return;
            }

            if ($identifierEmployee->employee_code !== $employeeCode) {
                $this->recordConflict(
                    $sourceRecord,
                    type: 'employee_code_change',
                    details: [
                        'current_employee_code' => $identifierEmployee->employee_code,
                        'source_employee_code'  => $employeeCode,
                    ],
                    employee: $identifierEmployee,
                );
                $counts['conflict_count']++;

                return;
            }

            if ($identifierEmployee->trashed()) {
                $this->recordConflict(
                    $sourceRecord,
                    type: 'retired_employee_match',
                    details: ['employee_code' => $employeeCode],
                    employee: $identifierEmployee,
                );
                $counts['conflict_count']++;

                return;
            }

            if ($apply) {
                $identifier->forceFill(['last_seen_at' => now()])->save();
            }

            $sourceRecord->forceFill([
                'status'         => 'matched',
                'match_strategy' => 'external_id',
                'employee_id'    => $identifierEmployee->id,
            ])->save();
            $counts['matched_count']++;

            return;
        }

        if ($employeeByCode !== null) {
            if ($employeeByCode->trashed()) {
                $this->recordConflict(
                    $sourceRecord,
                    type: 'retired_employee_match',
                    details: ['employee_code' => $employeeCode],
                    employee: $employeeByCode,
                );
                $counts['conflict_count']++;

                return;
            }

            $otherCurrentIdentifier = $employeeByCode->identifiers()
                ->current()
                ->where('source_system', $sourceSystem)
                ->where('source_instance', $sourceInstance)
                ->where('identifier_type', 'record_id')
                ->first();

            if ($otherCurrentIdentifier !== null) {
                $this->recordConflict(
                    $sourceRecord,
                    type: 'multiple_source_record_ids',
                    details: ['existing_identifier_id' => $otherCurrentIdentifier->id],
                    employee: $employeeByCode,
                );
                $counts['conflict_count']++;

                return;
            }

            if ($apply) {
                $this->recordConflict(
                    $sourceRecord,
                    type: 'employee_code_candidate_match',
                    details: ['employee_code' => $employeeCode],
                    employee: $employeeByCode,
                );
                $counts['conflict_count']++;

                return;
            }

            $sourceRecord->forceFill([
                'status'         => 'would_link',
                'match_strategy' => 'employee_code_candidate',
                'employee_id'    => $employeeByCode->id,
            ])->save();
            $counts['would_link_count']++;

            return;
        }

        if (! $apply) {
            $sourceRecord->forceFill([
                'status'         => 'would_create',
                'match_strategy' => 'new_employee',
            ])->save();
            $counts['would_create_count']++;

            return;
        }

        $employee = Employee::query()->create([
            'name'          => $normalized['name'],
            'employee_code' => $employeeCode,
            'job_title'     => $normalized['job'],
            'is_active'     => false,
        ]);

        $employee->identifiers()->create([
            'source_system'   => $sourceSystem,
            'source_instance' => $sourceInstance,
            'identifier_type' => 'record_id',
            'external_id'     => $externalId,
            'last_seen_at'    => now(),
        ]);

        $sourceRecord->forceFill([
            'status'         => 'created',
            'match_strategy' => 'new_employee',
            'employee_id'    => $employee->id,
        ])->save();
        $counts['created_count']++;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function recordConflict(
        EmployeeSourceRecord $sourceRecord,
        string $type,
        array $details,
        ?Employee $employee = null,
    ): void {
        $sourceRecord->forceFill([
            'status'      => 'conflict',
            'employee_id' => $employee?->id,
        ])->save();

        $sourceRecord->conflict()->create([
            'type'        => $type,
            'status'      => 'open',
            'details'     => $details,
            'employee_id' => $employee?->id,
        ]);
    }

    private function readSourceBuffer(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Employee JSON source is not a readable file.');
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open employee JSON source.');
        }

        try {
            if (! flock($handle, LOCK_SH)) {
                throw new RuntimeException('Unable to lock employee JSON source for a consistent read.');
            }

            $stat = fstat($handle);

            if (! is_array($stat) || ($stat['size'] ?? 0) > self::MAX_FILE_BYTES) {
                throw new RuntimeException('Employee JSON source exceeds the 25 MB limit.');
            }

            $contents = stream_get_contents($handle, self::MAX_FILE_BYTES + 1);

            if (! is_string($contents)) {
                throw new RuntimeException('Unable to read employee JSON source.');
            }

            if (strlen($contents) > self::MAX_FILE_BYTES) {
                throw new RuntimeException('Employee JSON source exceeds the 25 MB limit.');
            }

            return $contents;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array<int, mixed>
     *
     * @throws JsonException
     */
    private function decodeRecords(string $contents): array
    {
        $records = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($records) || ! array_is_list($records)) {
            throw new RuntimeException('Employee JSON source must contain a top-level list.');
        }

        return $records;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function markRunFailed(EmployeeSyncRun $run, array $counts, Throwable $throwable): void
    {
        $run->forceFill([
            ...$counts,
            'status'        => 'failed',
            'error_message' => $throwable->getMessage(),
            'completed_at'  => now(),
        ])->save();
    }

    /**
     * @return array<string, int>
     */
    private function emptyCounts(): array
    {
        return [
            'total_records'      => 0,
            'matched_count'      => 0,
            'linked_count'       => 0,
            'created_count'      => 0,
            'would_link_count'   => 0,
            'would_create_count' => 0,
            'conflict_count'     => 0,
            'invalid_count'      => 0,
        ];
    }

    private function normalizeSource(string $value, int $maxLength, string $label): string
    {
        $value = $this->normalizeKey($value);

        if ($value === '' || mb_strlen($value) > $maxLength) {
            throw new RuntimeException($label.' is required and exceeds its allowed length.');
        }

        return $value;
    }

    private function normalizeKey(string $value): string
    {
        return Str::lower(Str::squish($value));
    }
}
