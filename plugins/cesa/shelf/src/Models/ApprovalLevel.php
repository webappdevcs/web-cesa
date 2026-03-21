<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Employee\Models\Employee;

class ApprovalLevel extends ShelfModel
{
    use HasFactory;

    public const ALL_DIVISIONS = '*';

    /**
     * @var array{request_type: string, division: string}|null
     */
    private ?array $previousTrack = null;

    protected $fillable = [
        'request_type',
        'division',
        'level',
        'approver_employee_id',
        'approver_user_id',
        'approver_name',
        'approver_email',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $approvalLevel): void {
            $approvalLevel->prepareForSave();

            if ($approvalLevel->exists) {
                $approvalLevel->previousTrack = $approvalLevel->originalTrackIdentity();
            }

            if (! $approvalLevel->exists && ! $approvalLevel->level) {
                $approvalLevel->level = $approvalLevel->nextTemporaryLevelForTrack();
            }

            if (
                $approvalLevel->exists
                && $approvalLevel->previousTrack !== null
                && $approvalLevel->previousTrack !== $approvalLevel->trackIdentity()
            ) {
                $approvalLevel->level = $approvalLevel->nextTemporaryLevelForTrack();
            }

            $approvalLevel->ensureUniqueTrack();
        });

        static::saved(function (self $approvalLevel): void {
            $track = $approvalLevel->trackIdentity();

            self::resequenceTrack($track['request_type'], $track['division']);

            if ($approvalLevel->previousTrack !== null && $approvalLevel->previousTrack !== $track) {
                self::resequenceTrack(
                    $approvalLevel->previousTrack['request_type'],
                    $approvalLevel->previousTrack['division'],
                );
            }

            $approvalLevel->previousTrack = null;
        });

        static::deleted(function (self $approvalLevel): void {
            $track = $approvalLevel->trackIdentity();

            self::resequenceTrack($track['request_type'], $track['division']);
        });

        static::restored(function (self $approvalLevel): void {
            $track = $approvalLevel->trackIdentity();

            self::resequenceTrack($track['request_type'], $track['division']);
        });
    }

    public static function normalizeDivision(?string $division): string
    {
        $division = preg_replace('/\s+/u', ' ', trim((string) $division)) ?? '';

        return $division === '' ? self::ALL_DIVISIONS : $division;
    }

    public static function normalizeDivisionKey(?string $division): string
    {
        return mb_strtolower(self::normalizeDivision($division));
    }

    public function scopeForTrack(Builder $query, string $requestType, ?string $division): Builder
    {
        return $query
            ->where('request_type', $requestType)
            ->whereRaw('LOWER(division) = ?', [self::normalizeDivisionKey($division)]);
    }

    public function usesAllDivisions(): bool
    {
        return $this->division === self::ALL_DIVISIONS;
    }

    public function requestApprovals(): HasMany
    {
        return $this->hasManyIncludingTrashed(RequestApproval::class);
    }

    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    /**
     * @return array{employee_id: int, user_id: int, name: string, email: string}|null
     */
    public function resolveActiveApprover(): ?array
    {
        $employee = $this->approverEmployee()->with('user')->first();

        if ($employee === null || ! $employee->user_id) {
            return null;
        }

        $user = $employee->user;

        if ($user === null || ! $user->is_active) {
            return null;
        }

        $email = trim((string) ($user->email ?: $employee->work_email ?: $employee->private_email));

        if ($email === '') {
            return null;
        }

        return [
            'employee_id' => (int) $employee->getKey(),
            'user_id'     => (int) $user->getKey(),
            'name'        => self::formatApproverDisplayName($employee),
            'email'       => $email,
        ];
    }

    public function hasActiveApprover(): bool
    {
        return $this->resolveActiveApprover() !== null;
    }

    public function canMoveUpInTrack(): bool
    {
        return $this->adjacentLevelInTrack('up') !== null;
    }

    public function canMoveDownInTrack(): bool
    {
        return $this->adjacentLevelInTrack('down') !== null;
    }

    public function moveUpInTrack(): void
    {
        $this->moveWithinTrack('up');
    }

    public function moveDownInTrack(): void
    {
        $this->moveWithinTrack('down');
    }

    public static function formatApproverDisplayName(Employee $employee): string
    {
        $name = trim((string) ($employee->name ?? ''));
        $jobTitle = trim((string) ($employee->job_title ?? ''));

        if ($name !== '' && $jobTitle !== '' && mb_strtolower($name) !== mb_strtolower($jobTitle)) {
            return "{$name} - {$jobTitle}";
        }

        return $jobTitle !== '' ? $jobTitle : $name;
    }

    public static function formatApproverOptionLabel(Employee $employee): string
    {
        $label = self::formatApproverDisplayName($employee);
        $email = trim((string) ($employee->user?->email ?: $employee->work_email ?: $employee->private_email));

        return $email !== ''
            ? "{$label} ({$email})"
            : $label;
    }

    public static function resequenceTrack(string $requestType, ?string $division): void
    {
        $records = self::withTrashed()
            ->forTrack($requestType, $division)
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('level')
            ->orderBy('deleted_at')
            ->orderBy('id')
            ->get(['id', 'level']);

        if ($records->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($records): void {
            $temporaryOffset = ((int) ($records->max('level') ?? 0)) + $records->count() + 100;

            foreach ($records as $index => $record) {
                self::withTrashed()
                    ->whereKey($record->getKey())
                    ->update([
                        'level' => $temporaryOffset + $index,
                    ]);
            }

            foreach ($records as $index => $record) {
                self::withTrashed()
                    ->whereKey($record->getKey())
                    ->update([
                        'level' => $index + 1,
                    ]);
            }
        });
    }

    private function ensureUniqueTrack(): void
    {
        if (! $this->request_type || ! $this->level) {
            return;
        }

        $duplicateExists = self::withTrashed()
            ->where('request_type', $this->request_type)
            ->where('level', $this->level)
            ->whereRaw('LOWER(division) = ?', [self::normalizeDivisionKey($this->division)])
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists();

        if (! $duplicateExists) {
            return;
        }

        $divisionLabel = $this->usesAllDivisions() ? 'Semua Divisi' : $this->division;

        throw ValidationException::withMessages([
            'level' => "Konfigurasi approval untuk divisi {$divisionLabel} di level {$this->level} sudah ada.",
        ]);
    }

    private function syncApproverSnapshot(): void
    {
        if (! $this->approver_employee_id) {
            throw ValidationException::withMessages([
                'approver_employee_id' => 'Pilih approver dari data employee yang terhubung ke user aktif.',
            ]);
        }

        $snapshot = $this->resolveActiveApprover();

        if ($snapshot === null) {
            throw ValidationException::withMessages([
                'approver_employee_id' => 'Employee approver harus terhubung ke user aktif dan memiliki email yang bisa digunakan.',
            ]);
        }

        $this->approver_user_id = $snapshot['user_id'];
        $this->approver_name = $snapshot['name'];
        $this->approver_email = $snapshot['email'];
    }

    private function prepareForSave(): void
    {
        $this->division = self::normalizeDivision($this->division);
        $this->syncApproverSnapshot();
    }

    /**
     * @return array{request_type: string, division: string}
     */
    private function trackIdentity(): array
    {
        return [
            'request_type' => (string) $this->request_type,
            'division'     => self::normalizeDivision($this->division),
        ];
    }

    /**
     * @return array{request_type: string, division: string}
     */
    private function originalTrackIdentity(): array
    {
        return [
            'request_type' => (string) $this->getOriginal('request_type'),
            'division'     => self::normalizeDivision((string) $this->getOriginal('division')),
        ];
    }

    private function nextTemporaryLevelForTrack(): int
    {
        $track = $this->trackIdentity();

        return ((int) (self::withTrashed()
            ->forTrack($track['request_type'], $track['division'])
            ->max('level') ?? 0)) + 1;
    }

    private function moveWithinTrack(string $direction): void
    {
        DB::transaction(function () use ($direction): void {
            $current = self::query()
                ->lockForUpdate()
                ->findOrFail($this->getKey());

            $adjacent = $current->adjacentLevelInTrack($direction, true);

            if ($adjacent === null) {
                return;
            }

            $currentLevel = (int) $current->level;
            $adjacentLevel = (int) $adjacent->level;
            $temporaryLevel = $current->nextTemporaryLevelForTrack();

            $current->forceFill(['level' => $temporaryLevel])->saveQuietly();
            $adjacent->forceFill(['level' => $currentLevel])->saveQuietly();
            $current->forceFill(['level' => $adjacentLevel])->saveQuietly();

            self::resequenceTrack($current->request_type, $current->division);
        });

        $this->refresh();
    }

    private function adjacentLevelInTrack(string $direction, bool $lock = false): ?self
    {
        $query = self::query()
            ->forTrack($this->request_type, $this->division)
            ->whereKeyNot($this->getKey());

        if ($lock) {
            $query->lockForUpdate();
        }

        if ($direction === 'up') {
            return $query
                ->where('level', '<', $this->level)
                ->orderByDesc('level')
                ->first();
        }

        return $query
            ->where('level', '>', $this->level)
            ->orderBy('level')
            ->first();
    }
}
