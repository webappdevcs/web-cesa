<?php

namespace Cesa\Shelf\Concerns;

use Cesa\Shelf\Support\ShelfManagedFileRegistry;
use Cesa\Shelf\Support\ShelfStorage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait InteractsWithManagedFiles
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $originalManagedFileSnapshot = [];

    protected static function bootInteractsWithManagedFiles(): void
    {
        static::saving(function (self $model): void {
            $model->snapshotOriginalManagedFiles();
        });

        static::saved(function (self $model): void {
            $model->syncManagedFileStorageNames();
            $model->deleteManagedFilesRemovedFromCurrentState();
        });

        static::forceDeleted(function (self $model): void {
            $model->deleteCurrentManagedFiles();
        });
    }

    /**
     * @return array<string, array{directory: string, multiple?: bool, original_name_attribute?: string|null}>
     */
    protected function managedFileAttributes(): array
    {
        return [];
    }

    public function managedFileUrl(string $attribute, ?int $index = null, int $expiresInMinutes = 60): ?string
    {
        if (! $this->exists) {
            return null;
        }

        $path = $this->managedFilePath($attribute, $index);

        if ($path === null) {
            return null;
        }

        return URL::temporarySignedRoute(
            'shelf.attachments.download',
            now()->addMinutes($expiresInMinutes),
            [
                'type'      => ShelfManagedFileRegistry::typeForModel(static::class),
                'record'    => $this->getKey(),
                'attribute' => $attribute,
                'index'     => $index,
            ],
        );
    }

    public function managedFileUrlForPath(string $attribute, string $path, int $expiresInMinutes = 60): ?string
    {
        $index = $this->managedFileIndexForPath($attribute, $path);

        return $index === null
            ? null
            : $this->managedFileUrl($attribute, $index, $expiresInMinutes);
    }

    public function managedFileName(string $attribute, ?int $index = null): ?string
    {
        $path = $this->managedFilePath($attribute, $index);

        if ($path === null) {
            return null;
        }

        $originalNameAttribute = $this->managedFileAttributes()[$attribute]['original_name_attribute'] ?? null;

        if (is_string($originalNameAttribute) && filled($this->getAttribute($originalNameAttribute))) {
            return (string) $this->getAttribute($originalNameAttribute);
        }

        return basename($path);
    }

    public function managedFileNameForPath(string $attribute, string $path): ?string
    {
        $index = $this->managedFileIndexForPath($attribute, $path);

        return $index === null
            ? null
            : $this->managedFileName($attribute, $index);
    }

    public function managedFilePath(string $attribute, ?int $index = null): ?string
    {
        $paths = $this->managedFilePaths($attribute);

        if ($paths === []) {
            return null;
        }

        if ($index === null) {
            return $paths[0];
        }

        return $paths[$index] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function managedFilePaths(string $attribute): array
    {
        if (! array_key_exists($attribute, $this->managedFileAttributes())) {
            return [];
        }

        return $this->normalizeManagedFilePaths($this->getAttribute($attribute));
    }

    public function managedFileAbsolutePath(string $attribute, ?int $index = null): ?string
    {
        $path = $this->managedFilePath($attribute, $index);

        if ($path === null) {
            return null;
        }

        return $this->absolutePathForManagedFile($path);
    }

    public function managedFileAbsolutePathForPath(string $attribute, string $path): ?string
    {
        $index = $this->managedFileIndexForPath($attribute, $path);

        return $index === null
            ? null
            : $this->managedFileAbsolutePath($attribute, $index);
    }

    public function managedFileResponse(string $attribute, ?int $index = null): StreamedResponse
    {
        $path = $this->managedFilePath($attribute, $index);
        abort_if($path === null, 404);

        $disk = $this->resolveManagedFileDisk($path);
        abort_if($disk === null, 404);

        return Storage::disk($disk)->response($path, $this->managedFileName($attribute, $index));
    }

    protected function snapshotOriginalManagedFiles(): void
    {
        if (! $this->exists) {
            $this->originalManagedFileSnapshot = [];

            return;
        }

        $snapshot = [];

        foreach (array_keys($this->managedFileAttributes()) as $attribute) {
            $snapshot[$attribute] = $this->normalizeManagedFilePaths($this->getRawOriginal($attribute));
        }

        $this->originalManagedFileSnapshot = $snapshot;
    }

    protected function syncManagedFileStorageNames(): void
    {
        if (! $this->exists) {
            return;
        }

        $updatedAttributes = [];

        foreach ($this->managedFileAttributes() as $attribute => $configuration) {
            $renamedPaths = $this->renameManagedFilePaths(
                $attribute,
                $configuration['directory'],
                (bool) ($configuration['multiple'] ?? false),
            );

            if ($renamedPaths === null) {
                continue;
            }

            $updatedAttributes[$attribute] = $this->encodeManagedFilePaths(
                $renamedPaths,
                (bool) ($configuration['multiple'] ?? false),
            );
        }

        if ($updatedAttributes === []) {
            return;
        }

        $this->forceFill($updatedAttributes);
        $this->saveQuietly();
    }

    protected function deleteManagedFilesRemovedFromCurrentState(): void
    {
        if ($this->originalManagedFileSnapshot === []) {
            return;
        }

        $pathsToDelete = [];

        foreach ($this->originalManagedFileSnapshot as $attribute => $originalPaths) {
            $currentPaths = $this->managedFilePaths($attribute);
            $pathsToDelete = [
                ...$pathsToDelete,
                ...array_values(array_diff($originalPaths, $currentPaths)),
            ];
        }

        $this->originalManagedFileSnapshot = [];

        $this->deleteManagedFiles($pathsToDelete);
    }

    protected function deleteCurrentManagedFiles(): void
    {
        $paths = [];

        foreach (array_keys($this->managedFileAttributes()) as $attribute) {
            $paths = [
                ...$paths,
                ...$this->normalizeManagedFilePaths(
                    $this->getRawOriginal($attribute) ?? $this->getAttribute($attribute),
                ),
            ];
        }

        $this->deleteManagedFiles($paths);
    }

    /**
     * @return array<int, string>|null
     */
    protected function renameManagedFilePaths(string $attribute, string $directory, bool $isMultiple): ?array
    {
        $paths = $this->managedFilePaths($attribute);

        if ($paths === []) {
            return null;
        }

        $renamedPaths = [];
        $hasChanges = false;
        $totalPaths = count($paths);

        foreach ($paths as $index => $path) {
            $renamedPath = $this->renameManagedFilePath(
                $attribute,
                $path,
                $directory,
                $isMultiple ? $index : 0,
                $isMultiple ? $totalPaths : 1,
            );

            $renamedPaths[] = $renamedPath;
            $hasChanges = $hasChanges || ($renamedPath !== $path);
        }

        return $hasChanges ? $renamedPaths : null;
    }

    protected function renameManagedFilePath(
        string $attribute,
        string $path,
        string $directory,
        int $index,
        int $totalPaths,
    ): string {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $relativePath = ltrim($path, '/');

        if ($relativePath === '') {
            return $path;
        }

        $expectedPrefix = $this->managedFilePrefix($attribute, $index, $totalPaths).'-';
        $baseName = pathinfo($relativePath, PATHINFO_BASENAME);

        if (str_starts_with($baseName, $expectedPrefix)) {
            return $relativePath;
        }

        $disk = $this->resolveManagedFileDisk($relativePath);

        if ($disk === null) {
            return $path;
        }

        $targetPath = $this->buildManagedFilePath(
            $directory,
            $attribute,
            $relativePath,
            $index,
            $totalPaths,
            $disk,
        );

        if ($targetPath === $relativePath) {
            return $relativePath;
        }

        try {
            Storage::disk($disk)->move($relativePath, $targetPath);

            return $targetPath;
        } catch (\Throwable $exception) {
            logger()->warning('Failed to rename Shelf managed file.', [
                'model'     => static::class,
                'model_id'  => $this->getKey(),
                'attribute' => $attribute,
                'path'      => $relativePath,
                'target'    => $targetPath,
                'disk'      => $disk,
                'error'     => $exception->getMessage(),
            ]);

            return $path;
        }
    }

    protected function buildManagedFilePath(
        string $directory,
        string $attribute,
        string $currentPath,
        int $index,
        int $totalPaths,
        string $disk,
    ): string {
        $extension = Str::lower(pathinfo($currentPath, PATHINFO_EXTENSION));
        $prefix = trim($directory, '/').'/'.$this->managedFilePrefix($attribute, $index, $totalPaths);

        do {
            $targetPath = $prefix.'-'.Str::lower(Str::random(6));

            if ($extension !== '') {
                $targetPath .= '.'.$extension;
            }
        } while (Storage::disk($disk)->exists($targetPath));

        return $targetPath;
    }

    protected function managedFilePrefix(string $attribute, int $index, int $totalPaths): string
    {
        $modelSlug = Str::kebab(class_basename($this));
        $recordKey = Str::slug($this->managedFileRecordKey(), '-');
        $attributeSlug = Str::of($attribute)
            ->replace(['_path', '_url'], '')
            ->replace('_', '-')
            ->toString();

        $sequence = $totalPaths > 1
            ? '-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)
            : '';

        return "{$modelSlug}-{$recordKey}-{$attributeSlug}{$sequence}";
    }

    protected function managedFileRecordKey(): string
    {
        foreach (['uuid', 'form_uid', 'reference_number', 'code', 'letter_number'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return (string) ($this->getKey() ?? Str::uuid());
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function deleteManagedFiles(array $paths): void
    {
        foreach (array_values(array_unique($paths)) as $path) {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                continue;
            }

            $relativePath = ltrim($path, '/');

            if ($relativePath === '') {
                continue;
            }

            $disk = $this->resolveManagedFileDisk($relativePath);

            if ($disk === null) {
                continue;
            }

            try {
                Storage::disk($disk)->delete($relativePath);
            } catch (\Throwable $exception) {
                logger()->warning('Failed to delete Shelf managed file.', [
                    'model'    => static::class,
                    'model_id' => $this->getKey(),
                    'path'     => $relativePath,
                    'disk'     => $disk,
                    'error'    => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function resolveManagedFileDisk(string $path): ?string
    {
        $candidateDisks = array_values(array_unique(array_filter([
            ShelfStorage::disk(),
            config('filament.default_filesystem_disk'),
            config('filesystems.default'),
            'local',
            'public',
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

    protected function absolutePathForManagedFile(string $path): ?string
    {
        $disk = $this->resolveManagedFileDisk($path);

        if ($disk === null) {
            return null;
        }

        try {
            return Storage::disk($disk)->path($path);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function managedFileIndexForPath(string $attribute, string $path): ?int
    {
        $normalizedPath = ltrim($path, '/');

        foreach ($this->managedFilePaths($attribute) as $index => $candidatePath) {
            if ($candidatePath === $normalizedPath) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeManagedFilePaths(mixed $value): array
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->all();
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return [];
            }

            $value = $this->decodeManagedFilePayload($trimmed);

            if (is_string($value)) {
                $value = [$value];
            }
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            Arr::wrap($value),
            fn (mixed $path): bool => is_string($path) && trim($path) !== '',
        ));
    }

    protected function decodeManagedFilePayload(string $value): array|string
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

    protected function encodeManagedFilePaths(array $paths, bool $isMultiple): mixed
    {
        if ($paths === []) {
            return null;
        }

        if (! $isMultiple) {
            return $paths[0];
        }

        return $paths;
    }
}
