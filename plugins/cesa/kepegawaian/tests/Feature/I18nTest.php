<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Filament\Clusters\Configurations;
use Cesa\Kepegawaian\Filament\Resources\DepartmentResource;
use Cesa\Kepegawaian\Filament\Resources\EmployeeResource;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class I18nTest extends TestCase
{
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

    public function test_filament_resources_do_not_contain_hardcoded_user_facing_copy(): void
    {
        $patterns = [
            '/(?:->(?:label|helperText|hint|title|heading|description|modalHeading|modalDescription|modalSubmitActionLabel|placeholder)\(|(?:Tab|Section|Fieldset)::make\()\s*[\'"][^\'"]*[A-Za-z][^\'"]*[\'"]/',
            '/tooltip:\s*[\'"][^\'"]*[A-Za-z][^\'"]*[\'"]/',
        ];

        $offenders = [];

        foreach ($this->filamentFiles() as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);

            if ($lines === false) {
                continue;
            }

            foreach ($lines as $lineNumber => $line) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line) === 1) {
                        $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file).':'.($lineNumber + 1).' '.$line;

                        break;
                    }
                }
            }
        }

        $this->assertSame([], $offenders, "Hardcoded user-facing copy found:\n".implode("\n", $offenders));
    }

    public function test_main_navigation_labels_are_localized_for_en_and_id(): void
    {
        $expectations = [
            'en' => [
                'configurations' => 'Configurations',
                'employee'       => 'Karyawan',
                'department'     => 'Departments',
                'group'          => 'Kepegawaian',
            ],
            'id' => [
                'configurations' => 'Pengaturan',
                'employee'       => 'Karyawan',
                'department'     => 'Departemen',
                'group'          => 'Kepegawaian',
            ],
        ];

        foreach ($expectations as $locale => $labels) {
            app()->setLocale($locale);

            $this->assertSame($labels['configurations'], Configurations::getNavigationLabel());
            $this->assertSame($labels['employee'], EmployeeResource::getNavigationLabel());
            $this->assertSame($labels['department'], DepartmentResource::getNavigationLabel());
            $this->assertSame($labels['group'], EmployeeResource::getNavigationGroup());
        }
    }

    /**
     * @return array<string, string>
     */
    private function translationFiles(string $locale): array
    {
        $basePath = base_path("plugins/Cesa/Kepegawaian/resources/lang/{$locale}");
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
            new RecursiveDirectoryIterator(base_path('plugins/Cesa/Kepegawaian/src'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                continue;
            }

            preg_match_all('/(?:__|trans)\(\s*[\'"](kepegawaian::[^\'"]+)[\'"]/', $contents, $matches);

            foreach ($matches[1] as $translationKey) {
                $keys[$translationKey] = true;
            }
        }

        $keys = array_keys($keys);
        sort($keys);

        return $keys;
    }

    /**
     * @return array<int, string>
     */
    private function filamentFiles(): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('plugins/Cesa/Kepegawaian/src/Filament'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
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
