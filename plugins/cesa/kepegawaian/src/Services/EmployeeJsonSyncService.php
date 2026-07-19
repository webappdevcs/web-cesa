<?php

namespace Cesa\Kepegawaian\Services;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Models\EmployeeSourceRecord;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

class EmployeeJsonSyncService
{
    private const int MAX_FILE_BYTES = 25 * 1024 * 1024;

    public function __construct(
        private readonly EmployeeSeedData $normalizer,
    ) {}

    public function sync(
        string $path,
        string $sourceSystem,
        string $sourceInstance,
        bool $commit = false,
        ?int $initiatedBy = null,
    ): EmployeeSyncRun {
        $this->validateSourceFile($path);

        $sourceSystem = $this->normalizeKey($sourceSystem);
        $sourceInstance = $this->normalizeKey($sourceInstance);

        if ($sourceSystem === '' || $sourceInstance === '') {
            throw new RuntimeException('Source system and source instance are required.');
        }

        $checksum = hash_file('sha256', $path);

        if (! is_string($checksum)) {
            throw new RuntimeException('Unable to checksum employee JSON source.');
        }

        $run = EmployeeSyncRun::query()->create([
            'source_system'   => $sourceSystem,
            'source_instance' => $sourceInstance,
            'mode'            => $commit ? 'commit' : 'dry_run',
            'status'          => 'running',
            'file_name'       => basename($path),
            'file_checksum'   => $checksum,
            'initiated_by'    => $initiatedBy ?? Auth::id(),
            'started_at'      => now(),
        ]);

        $counts = $this->emptyCounts();

        try {
            $records = $this->decodeRecords($path);
            $counts['total_records'] = count($records);

            foreach ($records as $index => $rawRecord) {
                DB::transaction(function () use (
                    $run,
                    $index,
                    $rawRecord,
                    $sourceSystem,
                    $sourceInstance,
                    $commit,
                    &$counts,
                ): void {
                    $this->processRecord(
                        run: $run,
                        rowNumber: $index + 1,
                        rawRecord: $rawRecord,
                        sourceSystem: $sourceSystem,
                        sourceInstance: $sourceInstance,
                        commit: $commit,
                        counts: $counts,
                    );
                });
            }

            $run->forceFill([
                ...$counts,
                'status'       => 'completed',
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $throwable) {
            $run->forceFill([
                ...$counts,
                'status'        => 'failed',
                'error_message' => $throwable->getMessage(),
                'completed_at'  => now(),
            ])->save();

            throw $throwable;
        }

        return $run->refresh();
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
        bool $commit,
        array &$counts,
    ): void {
        $payload = is_array($rawRecord) ? $rawRecord : ['value' => $rawRecord];
        $normalized = is_array($rawRecord)
            ? $this->normalizer->normalizeRecord($rawRecord)
            : null;

        $sourceRecord = $run->sourceRecords()->create([
            'row_number'    => $rowNumber,
            'external_id'   => $normalized['source_id'] ?? null,
            'employee_code' => $normalized['employee_code'] ?? null,
            'checksum'      => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'payload'       => $payload,
            'status'        => 'processing',
        ]);

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

        if ($externalId === '') {
            $this->recordConflict(
                $sourceRecord,
                type: 'missing_external_id',
                details: ['employee_code' => $normalized['employee_code']],
            );
            $counts['conflict_count']++;
            $counts['invalid_count']++;

            return;
        }

        $identifier = EmployeeIdentifier::query()
            ->current()
            ->where('source_system', $sourceSystem)
            ->where('source_instance', $sourceInstance)
            ->where('identifier_type', 'record_id')
            ->where('normalized_value', $externalId)
            ->first();

        $employeeByCode = Employee::query()
            ->withTrashed()
            ->where('employee_code', $normalized['employee_code'])
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
                        'employee_code'          => $normalized['employee_code'],
                    ],
                    employee: $identifierEmployee,
                );
                $counts['conflict_count']++;

                return;
            }

            if ($identifierEmployee->employee_code !== $normalized['employee_code']) {
                $this->recordConflict(
                    $sourceRecord,
                    type: 'employee_code_change',
                    details: [
                        'current_employee_code' => $identifierEmployee->employee_code,
                        'source_employee_code'  => $normalized['employee_code'],
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
                    details: ['employee_code' => $normalized['employee_code']],
                    employee: $identifierEmployee,
                );
                $counts['conflict_count']++;

                return;
            }

            if ($commit) {
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
                    details: ['employee_code' => $normalized['employee_code']],
                    employee: $employeeByCode,
                );
                $counts['conflict_count']++;

                return;
            }

            if ($commit) {
                $employeeByCode->identifiers()->create([
                    'source_system'   => $sourceSystem,
                    'source_instance' => $sourceInstance,
                    'identifier_type' => 'record_id',
                    'external_id'     => $externalId,
                    'last_seen_at'    => now(),
                ]);
            }

            $sourceRecord->forceFill([
                'status'         => $commit ? 'linked' : 'would_link',
                'match_strategy' => 'employee_code',
                'employee_id'    => $employeeByCode->id,
            ])->save();
            $counts[$commit ? 'linked_count' : 'would_link_count']++;

            return;
        }

        if (! $commit) {
            $sourceRecord->forceFill([
                'status'         => 'would_create',
                'match_strategy' => 'new_employee',
            ])->save();
            $counts['would_create_count']++;

            return;
        }

        $employee = Employee::query()->create([
            'name'          => $normalized['name'],
            'employee_code' => $normalized['employee_code'],
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

    private function validateSourceFile(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Employee JSON source is not a readable file.');
        }

        $size = filesize($path);

        if (! is_int($size) || $size > self::MAX_FILE_BYTES) {
            throw new RuntimeException('Employee JSON source exceeds the 25 MB limit.');
        }
    }

    /**
     * @return array<int, mixed>
     *
     * @throws JsonException
     */
    private function decodeRecords(string $path): array
    {
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read employee JSON source.');
        }

        $records = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($records) || ! array_is_list($records)) {
            throw new RuntimeException('Employee JSON source must contain a top-level list.');
        }

        return $records;
    }

    /**
     * @return array<string, int>
     */
    private function emptyCounts(): array
    {
        return [
            'total_records'     => 0,
            'matched_count'     => 0,
            'linked_count'      => 0,
            'created_count'     => 0,
            'would_link_count'  => 0,
            'would_create_count'=> 0,
            'conflict_count'    => 0,
            'invalid_count'     => 0,
        ];
    }

    private function normalizeKey(string $value): string
    {
        return Str::lower(Str::squish($value));
    }
}
