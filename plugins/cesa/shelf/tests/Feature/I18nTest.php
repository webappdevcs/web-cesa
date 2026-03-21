<?php

namespace Cesa\Shelf\Tests\Feature;

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\ApprovalLevelResource;
use Cesa\Shelf\Filament\Resources\AssetLocationResource;
use Cesa\Shelf\Filament\Resources\AssetRequestResource;
use Cesa\Shelf\Filament\Resources\AssetResource;
use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Cesa\Shelf\Filament\Resources\BrandResource;
use Cesa\Shelf\Filament\Resources\CategoryResource;
use Cesa\Shelf\Filament\Resources\CompanyDocumentSettingResource;
use Cesa\Shelf\Filament\Resources\CustomAssetAttributeResource;
use Cesa\Shelf\Filament\Resources\TaskResource;
use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource;
use Cesa\Shelf\Filament\Resources\VendorResource;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class I18nTest extends TestCase
{
    /**
     * @var array<int, class-string>
     */
    private const RESOURCE_CLASSES = [
        ApprovalLevelResource::class,
        AssetLocationResource::class,
        AssetResource::class,
        AssetTransferResource::class,
        BrandResource::class,
        CategoryResource::class,
        CompanyDocumentSettingResource::class,
        CustomAssetAttributeResource::class,
        AssetRequestResource::class,
        TaskResource::class,
        VehicleChecksheetResource::class,
        VendorResource::class,
    ];

    public function test_translation_files_are_consistent_between_en_and_id(): void
    {
        $englishFiles = $this->translationFiles('en');
        $indonesianFiles = $this->translationFiles('id');

        $this->assertSame(array_keys($englishFiles), array_keys($indonesianFiles));

        foreach ($englishFiles as $relativePath => $englishFile) {
            $english = require $englishFile;
            $indonesian = require $indonesianFiles[$relativePath];

            $englishKeys = $this->flattenKeys($english);
            $indonesianKeys = $this->flattenKeys($indonesian);

            sort($englishKeys);
            sort($indonesianKeys);

            $this->assertSame($englishKeys, $indonesianKeys, $relativePath);
        }
    }

    public function test_all_used_translation_keys_exist_for_en_and_id(): void
    {
        $translationKeys = $this->sourceTranslationKeys();
        $missingKeys = [];

        foreach (['en', 'id'] as $locale) {
            foreach ($translationKeys as $translationKey) {
                if (trans($translationKey, [], $locale) === $translationKey) {
                    $missingKeys[] = "{$locale}:{$translationKey}";
                }
            }
        }

        $this->assertSame([], $missingKeys, "Missing translation keys:\n".implode("\n", $missingKeys));
    }

    public function test_resource_translation_files_define_navigation_and_model_labels(): void
    {
        $missingKeys = [];

        foreach (['en', 'id'] as $locale) {
            foreach (self::RESOURCE_CLASSES as $resourceClass) {
                $prefix = $this->resourceTranslationPrefix($resourceClass);

                foreach (['navigation.title', 'navigation.group', 'singular', 'plural'] as $suffix) {
                    $translationKey = "{$prefix}.{$suffix}";

                    if (trans($translationKey, [], $locale) === $translationKey) {
                        $missingKeys[] = "{$locale}:{$translationKey}";
                    }
                }
            }
        }

        $this->assertSame([], $missingKeys, "Missing resource label keys:\n".implode("\n", $missingKeys));
    }

    public function test_resource_and_cluster_labels_are_localized_for_en_and_id(): void
    {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            $this->assertSame(
                trans('shelf::app.config.navigation.label', [], $locale),
                Configurations::getNavigationLabel()
            );

            foreach (self::RESOURCE_CLASSES as $resourceClass) {
                $prefix = $this->resourceTranslationPrefix($resourceClass);

                $this->assertSame(
                    trans("{$prefix}.navigation.title", [], $locale),
                    $resourceClass::getNavigationLabel(),
                    "{$locale}:{$resourceClass} navigation label mismatch"
                );

                $this->assertSame(
                    trans("{$prefix}.navigation.group", [], $locale),
                    $resourceClass::getNavigationGroup(),
                    "{$locale}:{$resourceClass} navigation group mismatch"
                );

                $this->assertSame(
                    trans("{$prefix}.singular", [], $locale),
                    $resourceClass::getModelLabel(),
                    "{$locale}:{$resourceClass} singular label mismatch"
                );

                $this->assertSame(
                    trans("{$prefix}.plural", [], $locale),
                    $resourceClass::getPluralModelLabel(),
                    "{$locale}:{$resourceClass} plural label mismatch"
                );
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function translationFiles(string $locale): array
    {
        $basePath = base_path("plugins/cesa/shelf/resources/lang/{$locale}");
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $files[$relativePath] = $file->getPathname();
        }

        ksort($files);

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function sourceTranslationKeys(): array
    {
        $keys = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('plugins/cesa/shelf/src'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                continue;
            }

            preg_match_all('/(?:__|trans|trans_choice)\(\s*[\'"](shelf::[^\'"]+)[\'"]/', $contents, $matches);

            foreach ($matches[1] as $translationKey) {
                $keys[$translationKey] = true;
            }
        }

        $keys = array_keys($keys);
        sort($keys);

        return $keys;
    }

    /**
     * @param  class-string  $resourceClass
     */
    private function resourceTranslationPrefix(string $resourceClass): string
    {
        $resourceKey = str(class_basename($resourceClass))
            ->beforeLast('Resource')
            ->kebab()
            ->toString();

        return "shelf::filament.resources.{$resourceKey}";
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<int, string>
     */
    private function flattenKeys(array $items, string $prefix = ''): array
    {
        $keys = [];

        foreach ($items as $key => $value) {
            $path = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $path));
            } else {
                $keys[] = $path;
            }
        }

        return $keys;
    }
}
