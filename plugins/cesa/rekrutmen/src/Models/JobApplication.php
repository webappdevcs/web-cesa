<?php

namespace Cesa\Rekrutmen\Models;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    public const RESUME_DIRECTORY = 'rekrutmen/cv';

    protected ?string $originalResumePath = null;

    protected $table = 'rekrutmen_job_applications';

    protected $fillable = [
        'job_posting_id',
        'current_stage_id',
        'full_name',
        'email',
        'phone',
        'resume_path',
        'cover_letter',
        'portfolio_url',
        'status',
    ];

    protected $casts = [
        'status'     => JobApplicationStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (JobApplication $application): void {
            $application->snapshotOriginalResumePath();
            $application->prepareManagedResumePathForPersistence();
        });

        static::saved(function (JobApplication $application): void {
            if ($application->wasRecentlyCreated) {
                $application->syncManagedResumePath();
            }

            $application->deleteRemovedResumeFile();
        });

        static::forceDeleted(function (JobApplication $application): void {
            $application->deleteManagedFile(
                $application->normalizeManagedFilePath(
                    $application->getRawOriginal('resume_path') ?? $application->resume_path
                )
            );
        });
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id')->withTrashed();
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(RekrutmenStage::class, 'current_stage_id')->withTrashed();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(JobApplicationHistory::class, 'job_application_id')->latest();
    }

    public static function resumeDisk(): string
    {
        $disk = config('filament.default_filesystem_disk', config('filesystems.default', 'local'));

        return is_string($disk) && $disk !== '' ? $disk : 'local';
    }

    protected function snapshotOriginalResumePath(): void
    {
        if (! $this->exists) {
            $this->originalResumePath = null;

            return;
        }

        $this->originalResumePath = $this->normalizeManagedFilePath(
            $this->getRawOriginal('resume_path')
        );
    }

    protected function prepareManagedResumePathForPersistence(): void
    {
        if (! $this->exists) {
            return;
        }

        $path = $this->normalizeManagedFilePath($this->resume_path);

        if (! $path) {
            return;
        }

        $renamedPath = $this->renameManagedFile(
            $path,
            self::RESUME_DIRECTORY,
            'CV-'.$this->getKey()
        );

        if ($renamedPath !== $path) {
            $this->resume_path = $renamedPath;
        }
    }

    protected function syncManagedResumePath(): void
    {
        $path = $this->normalizeManagedFilePath($this->resume_path);

        if (! $path || ! $this->exists) {
            return;
        }

        $renamedPath = $this->renameManagedFile(
            $path,
            self::RESUME_DIRECTORY,
            'CV-'.$this->getKey()
        );

        if ($renamedPath === $path) {
            return;
        }

        $this->forceFill([
            'resume_path' => $renamedPath,
        ]);

        $this->saveQuietly();
    }

    protected function deleteRemovedResumeFile(): void
    {
        if ($this->originalResumePath === null) {
            return;
        }

        $originalPath = $this->originalResumePath;
        $currentPath = $this->normalizeManagedFilePath($this->resume_path);

        $this->originalResumePath = null;

        if ($originalPath === $currentPath) {
            return;
        }

        $this->deleteManagedFile($originalPath);
    }

    protected function normalizeManagedFilePath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $relativePath = ltrim(trim($path), '/');

        return $relativePath !== '' ? $relativePath : null;
    }

    protected function renameManagedFile(string $path, string $directory, string $prefix): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $canonicalDirectory = trim($directory, '/');
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $expectedPrefix = $canonicalDirectory.'/'.$prefix.'-';

        if (str_starts_with($path, $expectedPrefix)) {
            return $path;
        }

        $targetPath = $this->buildManagedResumePath($canonicalDirectory, $extension);

        if ($targetPath === $path) {
            return $path;
        }

        $disk = $this->resolveManagedFileDisk($path);

        if (! $disk) {
            return $path;
        }

        try {
            if (Storage::disk($disk)->exists($targetPath)) {
                Storage::disk($disk)->delete($targetPath);
            }

            Storage::disk($disk)->move($path, $targetPath);

            return $targetPath;
        } catch (\Throwable) {
            return $path;
        }
    }

    protected function buildManagedResumePath(string $directory, string $extension): string
    {
        $positionSlug = $this->resolveResumePositionSlug();
        $fileName = 'CV-'.$this->getKey().'-'.$positionSlug;

        if ($extension !== '') {
            $fileName .= '.'.$extension;
        }

        return trim($directory, '/').'/'.$fileName;
    }

    protected function resolveResumePositionSlug(): string
    {
        $position = $this->jobPosting?->title;
        $slug = Str::slug((string) $position);

        if ($slug === '') {
            return __('rekrutmen::app.resources.job_application.generated.unknown_position');
        }

        return Str::limit($slug, 80, '');
    }

    protected function deleteManagedFile(?string $path): void
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        $disk = $this->resolveManagedFileDisk($path);

        if (! $disk) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable) {
            return;
        }
    }

    protected function resolveManagedFileDisk(string $path): ?string
    {
        $candidateDisks = array_values(array_unique(array_filter([
            config('filament.default_filesystem_disk'),
            config('filesystems.default'),
            'public',
            'local',
        ], fn (mixed $disk): bool => is_string($disk) && $disk !== '')));

        foreach ($candidateDisks as $disk) {
            if (! config()->has("filesystems.disks.{$disk}")) {
                continue;
            }

            try {
                if (Storage::disk($disk)->exists($path)) {
                    return $disk;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
