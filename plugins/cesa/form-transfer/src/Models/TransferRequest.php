<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\TransferRequestFactory;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Security\Models\Scopes\UserPermissionScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class TransferRequest extends Model
{
    use HasChatter, HasFactory, SoftDeletes;

    protected $table = 'form_transfer_requests';

    /**
     * @var array<string, array<int, string>>
     */
    protected array $originalAttachmentSnapshot = [];

    protected $fillable = [
        'uid',
        'submission_status',
        'approval_status',
        'realization_status',
        'status_response_id',
        'form_transfer_id',
        'company_id',
        'user_id',
        'creator_id',
        'requester_name',
        'division_name',
        'division_id',
        'email',
        'account_number',
        'account_name',
        'bank_id',
        'transfer_amount',
        'purpose',
        'reference_note',
        'invoice_path',
        'account_attachment_path',
        'realized_at',
        'realization_proof_path',
        'realization_notes',
        'approval_workflow_id',
        'approvals',
    ];

    protected static function booted(): void
    {
        if (Auth::check()) {
            static::addGlobalScope(new UserPermissionScope('user', 'company_id'));
        }

        static::creating(function (TransferRequest $request): void {
            if (empty($request->form_transfer_id)) {
                throw new RuntimeException('Form transfer must be specified for a transfer request.');
            }

            $formTransfer = FormTransfer::query()->find($request->form_transfer_id);

            if (! $formTransfer) {
                throw new RuntimeException('Invalid form transfer selected for request.');
            }

            $authenticatedUser = Auth::user();

            if (empty($request->uid)) {
                $request->uid = $formTransfer->generateNextRequestUid();
            }

            if (empty($request->company_id)) {
                $request->company_id = $formTransfer->company_id;
            }

            if ($authenticatedUser && empty($request->user_id)) {
                $request->user_id = $authenticatedUser->getAuthIdentifier();
            }

            if (empty($request->user_id) && $formTransfer->company?->createdBy) {
                $request->user_id = $formTransfer->company->createdBy->getKey();
            }

            if ($authenticatedUser && empty($request->creator_id)) {
                $request->creator_id = $authenticatedUser->getAuthIdentifier();
            }

            if (empty($request->creator_id) && $request->user_id) {
                $request->creator_id = $request->user_id;
            }

            if (empty($request->status_response_id)) {
                $request->status_response_id = (string) Str::uuid();
            }

            if ($request->division_id && empty($request->division_name)) {
                $request->division_name = TransferDivision::query()
                    ->withTrashed()
                    ->find($request->division_id)?->name;
            }

            if (empty($request->submission_status)) {
                $request->submission_status = TransferRequestSubmissionStatus::BARU;
            }

            if (empty($request->approval_status)) {
                $request->approval_status = TransferRequestApprovalStatus::PENDING;
            }

            if (empty($request->realization_status)) {
                $request->realization_status = TransferRequestRealizationStatus::PENDING;
            }
        });

        static::saving(function (TransferRequest $request): void {
            $request->snapshotOriginalAttachments();

            $approvalStatus = $request->approval_status instanceof TransferRequestApprovalStatus
                ? $request->approval_status
                : TransferRequestApprovalStatus::tryFrom((string) $request->approval_status);

            if ($approvalStatus !== TransferRequestApprovalStatus::REJECTED) {
                return;
            }

            $realizationStatus = $request->realization_status instanceof TransferRequestRealizationStatus
                ? $request->realization_status
                : TransferRequestRealizationStatus::tryFrom((string) $request->realization_status);

            if ($realizationStatus !== TransferRequestRealizationStatus::CANCELLED) {
                $request->realization_status = TransferRequestRealizationStatus::CANCELLED;
            }
        });

        static::saved(function (TransferRequest $request): void {
            $request->syncAttachmentStorageNames();
            $request->deleteAttachmentsRemovedFromCurrentState();
        });

        static::forceDeleted(function (TransferRequest $request): void {
            $request->deleteCurrentAttachmentFiles();
        });
    }

    protected function casts(): array
    {
        return [
            'transfer_amount'    => 'decimal:2',
            'realized_at'        => 'date',
            'approvals'          => 'array',
            'submission_status'  => TransferRequestSubmissionStatus::class,
            'approval_status'    => TransferRequestApprovalStatus::class,
            'realization_status' => TransferRequestRealizationStatus::class,
            'realization_notes'  => 'string',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function normalizeAttachmentPaths(mixed $value): array
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return [];
            }

            $value = static::decodeAttachmentPayload($trimmed);

            if (is_string($value)) {
                $value = [$value];
            }
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            Arr::wrap($value),
            fn ($path): bool => is_string($path) && $path !== ''
        ));
    }

    /**
     * Decode attachment payloads that may be encoded more than once.
     */
    protected static function decodeAttachmentPayload(string $value): array|string
    {
        $candidate = $value;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $decoded = json_decode($candidate, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $unescaped = stripcslashes($candidate);

                if ($unescaped === $candidate) {
                    break;
                }

                $decoded = json_decode($unescaped, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    break;
                }
            }

            if (is_array($decoded)) {
                return $decoded;
            }

            if (! is_string($decoded)) {
                break;
            }

            $candidate = trim($decoded);

            if ($candidate === '') {
                return [];
            }
        }

        return trim($candidate, "\"'");
    }

    protected static function encodeAttachmentPaths(mixed $value): ?string
    {
        $paths = static::normalizeAttachmentPaths($value);

        if ($paths === []) {
            return null;
        }

        if (count($paths) === 1) {
            return $paths[0];
        }

        $encoded = json_encode($paths);

        return is_string($encoded) ? $encoded : null;
    }

    /**
     * @return array<int, string>
     */
    public function getInvoicePathAttribute(mixed $value): array
    {
        return static::normalizeAttachmentPaths($value);
    }

    /**
     * @return array<int, string>
     */
    public function getAccountAttachmentPathAttribute(mixed $value): array
    {
        return static::normalizeAttachmentPaths($value);
    }

    public function setInvoicePathAttribute(mixed $value): void
    {
        $this->attributes['invoice_path'] = static::encodeAttachmentPaths($value);
    }

    public function setAccountAttachmentPathAttribute(mixed $value): void
    {
        $this->attributes['account_attachment_path'] = static::encodeAttachmentPaths($value);
    }

    public function syncAttachmentStorageNames(): void
    {
        if (blank($this->uid)) {
            return;
        }

        $updatedAttributes = [];

        foreach ($this->attachmentStorageDirectories() as $attribute => $directory) {
            $renamedPaths = $this->renameAttachmentPaths($attribute, $directory);

            if ($renamedPaths === null) {
                continue;
            }

            $updatedAttributes[$attribute] = static::encodeAttachmentPaths($renamedPaths);
        }

        if ($updatedAttributes === []) {
            return;
        }

        $this->forceFill($updatedAttributes);
        $this->saveQuietly();
    }

    protected function snapshotOriginalAttachments(): void
    {
        if (! $this->exists) {
            $this->originalAttachmentSnapshot = [];

            return;
        }

        $snapshot = [];

        foreach (array_keys($this->attachmentStorageDirectories()) as $attribute) {
            $snapshot[$attribute] = static::normalizeAttachmentPaths($this->getRawOriginal($attribute));
        }

        $this->originalAttachmentSnapshot = $snapshot;
    }

    protected function deleteAttachmentsRemovedFromCurrentState(): void
    {
        if ($this->originalAttachmentSnapshot === []) {
            return;
        }

        $pathsToDelete = [];

        foreach ($this->originalAttachmentSnapshot as $attribute => $originalPaths) {
            $currentPaths = static::normalizeAttachmentPaths($this->getAttribute($attribute));
            $pathsToDelete = [
                ...$pathsToDelete,
                ...array_values(array_diff($originalPaths, $currentPaths)),
            ];
        }

        $this->originalAttachmentSnapshot = [];

        $this->deleteAttachmentFiles($pathsToDelete);
    }

    protected function deleteCurrentAttachmentFiles(): void
    {
        $paths = [];

        foreach (array_keys($this->attachmentStorageDirectories()) as $attribute) {
            $paths = [
                ...$paths,
                ...static::normalizeAttachmentPaths($this->getRawOriginal($attribute) ?? $this->getAttribute($attribute)),
            ];
        }

        $this->deleteAttachmentFiles($paths);
    }

    /**
     * @return array<string, string>
     */
    protected function attachmentStorageDirectories(): array
    {
        return [
            'invoice_path'            => 'form-transfer/invoices',
            'account_attachment_path' => 'form-transfer/account-attachments',
            'realization_proof_path'  => 'form-transfer/realizations',
        ];
    }

    /**
     * @return array<int, string>|null
     */
    protected function renameAttachmentPaths(string $attribute, string $directory): ?array
    {
        $paths = static::normalizeAttachmentPaths($this->getAttribute($attribute));

        if ($paths === []) {
            return null;
        }

        $renamedPaths = [];
        $hasChanges = false;
        $totalPaths = count($paths);

        foreach ($paths as $index => $path) {
            $renamedPath = $this->renameAttachmentPath($path, $directory, $index, $totalPaths);

            $renamedPaths[] = $renamedPath;
            $hasChanges = $hasChanges || ($renamedPath !== $path);
        }

        return $hasChanges ? $renamedPaths : null;
    }

    protected function renameAttachmentPath(string $path, string $directory, int $index, int $totalPaths): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $relativePath = ltrim($path, '/');

        if ($relativePath === '') {
            return $path;
        }

        $baseName = pathinfo($relativePath, PATHINFO_BASENAME);

        if (str_starts_with($baseName, "{$this->uid}-")) {
            return $relativePath;
        }

        $disk = $this->resolveAttachmentDisk($relativePath);

        if (! $disk) {
            return $path;
        }

        try {
            $targetDirectory = trim(pathinfo($relativePath, PATHINFO_DIRNAME), './');
            $targetDirectory = $targetDirectory !== '' ? $targetDirectory : $directory;
            $targetPath = $this->buildManagedAttachmentPath($targetDirectory, $index, $totalPaths, $relativePath, $disk);

            if ($targetPath === $relativePath) {
                return $relativePath;
            }

            Storage::disk($disk)->move($relativePath, $targetPath);

            return $targetPath;
        } catch (\Throwable $e) {
            logger()->warning('Failed to rename attachment path during sync.', [
                'transfer_request_id' => $this->getKey(),
                'original_path'       => $path,
                'target_directory'    => $directory,
                'disk'                => $disk,
                'error'               => $e->getMessage(),
            ]);

            return $path;
        }
    }

    protected function buildManagedAttachmentPath(
        string $directory,
        int $index,
        int $totalPaths,
        string $currentPath,
        string $disk
    ): string {
        $extension = Str::lower(pathinfo($currentPath, PATHINFO_EXTENSION));
        $pathPrefix = trim($directory, '/').'/';
        $sequenceSuffix = $totalPaths > 1
            ? '-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)
            : '';

        do {
            $fileName = $this->uid.$sequenceSuffix.'-'.Str::lower(Str::random(6));

            if ($extension !== '') {
                $fileName .= '.'.$extension;
            }

            $targetPath = $pathPrefix.$fileName;
        } while (Storage::disk($disk)->exists($targetPath));

        return $targetPath;
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function deleteAttachmentFiles(array $paths): void
    {
        foreach (array_values(array_unique($paths)) as $path) {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                continue;
            }

            $relativePath = ltrim($path, '/');

            if ($relativePath === '') {
                continue;
            }

            $disk = $this->resolveAttachmentDisk($relativePath);

            if (! $disk) {
                continue;
            }

            try {
                Storage::disk($disk)->delete($relativePath);
            } catch (\Throwable $e) {
                logger()->warning('Failed to delete attachment file.', [
                    'transfer_request_id' => $this->getKey(),
                    'path'                => $relativePath,
                    'disk'                => $disk,
                    'error'               => $e->getMessage(),
                ]);

                continue;
            }
        }
    }

    protected function resolveAttachmentDisk(string $relativePath): ?string
    {
        $candidateDisks = array_values(array_unique(array_filter([
            config('filament.default_filesystem_disk'),
            config('filesystems.default'),
            'local',
            'public',
            'ftp',
        ], fn (mixed $disk): bool => is_string($disk) && $disk !== '')));

        foreach ($candidateDisks as $disk) {
            if (! config()->has("filesystems.disks.{$disk}")) {
                continue;
            }

            try {
                if (Storage::disk($disk)->exists($relativePath)) {
                    return $disk;
                }
            } catch (\Throwable $e) {
                logger()->debug('Failed to check file existence on disk.', [
                    'disk'  => $disk,
                    'path'  => $relativePath,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        return null;
    }

    public function formTransfer(): BelongsTo
    {
        return $this->belongsTo(FormTransfer::class)->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withTrashed();
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(TransferDivision::class, 'division_id')->withTrashed();
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(TransferBank::class, 'bank_id')->withTrashed();
    }

    /**
     * Get bank information from association
     */
    public function getBankInfo(): ?TransferBank
    {
        if (! $this->bank_id) {
            return null;
        }

        if ($this->relationLoaded('bank')) {
            return $this->getRelationValue('bank');
        }

        return $this->bank()->first();
    }

    /**
     * Get bank display name
     */
    public function getBankDisplayNameAttribute(): ?string
    {
        return $this->getBankInfo()?->display_name;
    }

    /**
     * Get bank name accessor for backwards compatibility
     */
    public function getBankNameAttribute(): ?string
    {
        return $this->getBankInfo()?->name;
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(TransferApprovalWorkflow::class, 'approval_workflow_id')->withTrashed();
    }

    protected static function newFactory(): Factory
    {
        return TransferRequestFactory::new();
    }
}
