<?php

namespace Cesa\Kepegawaian\Services;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Webkul\Security\Models\User;

class EmployeeSyncConflictResolver
{
    /** @var array<int, string> */
    public const REJECTION_REASONS = [
        'duplicate_source_record',
        'invalid_source_record',
        'out_of_scope',
    ];

    public function recheck(EmployeeSyncConflict $conflict, User $actor): bool
    {
        return DB::transaction(function () use ($conflict, $actor): bool {
            $lockedConflict = $this->lockOpenConflict($conflict);

            Gate::forUser($actor)->authorize('resolve', $lockedConflict);

            $sourceRecord = $lockedConflict->sourceRecord()->firstOrFail();
            $run = $sourceRecord->syncRun()->firstOrFail();
            $externalId = Str::lower(Str::squish((string) $sourceRecord->external_id));
            $employeeCode = Str::squish((string) $sourceRecord->employee_code);

            if ($externalId === '' || $employeeCode === '') {
                return false;
            }

            $identifier = EmployeeIdentifier::query()
                ->current()
                ->where('source_system', $run->source_system)
                ->where('source_instance', $run->source_instance)
                ->where('identifier_type', 'record_id')
                ->where('normalized_value', $externalId)
                ->first();

            if ($identifier === null) {
                return false;
            }

            $identifierOwner = Employee::query()
                ->withTrashed()
                ->find($identifier->employee_id);
            $codeOwner = Employee::query()
                ->withTrashed()
                ->where('employee_code', $employeeCode)
                ->first();

            if (
                $identifierOwner === null
                || $identifierOwner->trashed()
                || $identifierOwner->employee_code !== $employeeCode
                || $codeOwner === null
                || $codeOwner->trashed()
                || $codeOwner->isNot($identifierOwner)
            ) {
                return false;
            }

            $lockedConflict->forceFill([
                'status'           => 'resolved',
                'resolution'       => 'rechecked_match',
                'resolution_notes' => null,
                'resolved_by'      => $actor->getKey(),
                'resolved_at'      => now(),
            ])->save();

            $this->logResolution($lockedConflict, $run->uuid, $actor);

            return true;
        });
    }

    public function rejectSource(
        EmployeeSyncConflict $conflict,
        User $actor,
        string $reasonCode,
        string $notes,
    ): EmployeeSyncConflict {
        $reasonCode = Str::lower(Str::squish($reasonCode));
        $notes = trim($notes);

        if (! in_array($reasonCode, self::REJECTION_REASONS, true)) {
            throw new InvalidArgumentException('Unsupported employee source rejection reason.');
        }

        if ($notes === '' || mb_strlen($notes) > 2000) {
            throw new InvalidArgumentException('Resolution notes must contain between 1 and 2000 characters.');
        }

        return DB::transaction(function () use ($conflict, $actor, $reasonCode, $notes): EmployeeSyncConflict {
            $lockedConflict = $this->lockOpenConflict($conflict);

            Gate::forUser($actor)->authorize('resolve', $lockedConflict);

            $run = $lockedConflict->sourceRecord()->firstOrFail()->syncRun()->firstOrFail();

            $lockedConflict->forceFill([
                'status'           => 'resolved',
                'resolution'       => 'rejected_'.$reasonCode,
                'resolution_notes' => $notes,
                'resolved_by'      => $actor->getKey(),
                'resolved_at'      => now(),
            ])->save();

            $this->logResolution($lockedConflict, $run->uuid, $actor);

            return $lockedConflict->refresh();
        });
    }

    private function lockOpenConflict(EmployeeSyncConflict $conflict): EmployeeSyncConflict
    {
        $lockedConflict = EmployeeSyncConflict::query()
            ->lockForUpdate()
            ->findOrFail($conflict->getKey());

        if ($lockedConflict->status !== 'open') {
            throw new LogicException('Only an open employee sync conflict may be resolved.');
        }

        return $lockedConflict;
    }

    private function logResolution(EmployeeSyncConflict $conflict, string $runUuid, User $actor): void
    {
        Log::info('Employee sync conflict resolved.', [
            'conflict_id' => $conflict->getKey(),
            'run_uuid'    => $runUuid,
            'type'        => $conflict->type,
            'resolution'  => $conflict->resolution,
            'actor_id'    => $actor->getKey(),
        ]);
    }
}
