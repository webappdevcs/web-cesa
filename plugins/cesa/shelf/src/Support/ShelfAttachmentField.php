<?php

namespace Cesa\Shelf\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;

class ShelfAttachmentField
{
    /**
     * @param  array<int, string>  $acceptedFileTypes
     */
    public static function make(
        string $name,
        string $directory,
        array $acceptedFileTypes,
        int $maxSize,
        bool $multiple = false,
        bool $image = false,
        ?int $maxFiles = null,
    ): FileUpload {
        $field = FileUpload::make($name)
            ->disk(ShelfStorage::disk())
            ->directory($directory)
            ->visibility('private')
            ->downloadable()
            ->openable()
            ->fetchFileInformation(false)
            ->acceptedFileTypes($acceptedFileTypes)
            ->maxSize($maxSize)
            ->getUploadedFileUsing(function (?Model $record, string $file, string|array|null $storedFileNames, FileUpload $component) use ($name): ?array {
                if (! $record || ! method_exists($record, 'managedFileUrlForPath')) {
                    return null;
                }

                $storage = $component->getDisk();
                $shouldFetchFileInformation = $component->shouldFetchFileInformation();

                if ($shouldFetchFileInformation) {
                    try {
                        if (! $storage->exists($file)) {
                            return null;
                        }
                    } catch (\Throwable) {
                        return null;
                    }
                }

                return [
                    'name' => method_exists($record, 'managedFileNameForPath')
                        ? ($record->managedFileNameForPath($name, $file) ?? basename($file))
                        : basename($file),
                    'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
                    'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
                    'url'  => $record->managedFileUrlForPath($name, $file),
                ];
            });

        if ($multiple) {
            $field->multiple();
        }

        if ($image) {
            $field->image();
        }

        if ($maxFiles !== null) {
            $field->maxFiles($maxFiles);
        }

        return $field;
    }
}
