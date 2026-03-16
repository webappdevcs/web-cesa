<?php

namespace Cesa\Kepegawaian\Database\Seeders\Support;

use Carbon\CarbonImmutable;
use Cesa\Kepegawaian\Enums\Gender;
use Cesa\Kepegawaian\Enums\MaritalStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class EmployeeSeedData
{
    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    private ?Collection $records = null;

    public function __construct(
        private readonly ?string $path = null,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function records(): Collection
    {
        if ($this->records instanceof Collection) {
            return $this->records;
        }

        $path = $this->path ?? base_path('plugins/cesa/kepegawaian/database/data/list-employees.json');

        if (! is_file($path)) {
            throw new RuntimeException("Employee seed data file not found: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read employee seed data file: {$path}");
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid employee seed data JSON: {$path}");
        }

        $this->records = collect($decoded)
            ->filter(fn ($record) => is_array($record))
            ->map(fn (array $record): array => [
                'branch'         => $this->normalizeText($record['branch'] ?? null),
                'organization'   => $this->normalizeText($record['organization'] ?? null),
                'source_id'      => $this->normalizeText($record['id'] ?? null),
                'employee_code'  => $this->normalizeText($record['id_employee'] ?? null),
                'job'            => $this->normalizeText($record['job'] ?? null),
                'title'          => $this->normalizeText($record['title'] ?? null),
                'name'           => $this->buildName($record),
                'email'          => $this->normalizeText($record['email'] ?? null),
                'mobile_phone'   => $this->normalizeText($record['mobile_phone'] ?? null),
                'phone'          => $this->normalizeText($record['phone'] ?? null),
                'current_address'=> $this->normalizeText($record['current_address'] ?? null),
                'address'        => $this->normalizeText($record['address'] ?? null),
                'tax_status'     => $this->normalizeText($record['tax_status'] ?? null),
                'marital'        => $this->normalizeMaritalStatus($record['marital_status'] ?? null),
                'religion'       => $this->normalizeText($record['religion'] ?? null),
                'gender'         => $this->normalizeGender($record['gender'] ?? null),
                'blood_type'     => $this->normalizeText($record['blood_type'] ?? null),
                'birth_date'     => $this->normalizeDate($record['birth_date'] ?? null),
                'join_date'      => $this->normalizeDate($record['join_date'] ?? null),
                'grade'          => $this->normalizeText($record['grade'] ?? null),
                'class'          => $this->normalizeText($record['class'] ?? null),
            ])
            ->filter(fn (array $record) => filled($record['employee_code']) && filled($record['name']))
            ->values();

        return $this->records;
    }

    /**
     * @return Collection<int, array{name: string, company_id: string, color: string}>
     */
    public function companies(): Collection
    {
        return $this->records()
            ->pluck('branch')
            ->filter(fn ($branch) => filled($branch))
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $branch): array => [
                'name'       => $branch,
                'company_id' => $this->buildCompanyCode($branch),
                'color'      => $this->buildColor($branch),
            ]);
    }

    /**
     * @return Collection<int, array{branch: string, name: string, color: string}>
     */
    public function departments(): Collection
    {
        return $this->records()
            ->filter(fn (array $record) => filled($record['branch']) && filled($record['organization']))
            ->map(fn (array $record): array => [
                'branch' => $record['branch'],
                'name'   => $record['organization'],
                'color'  => $this->buildColor($record['branch'].'|'.$record['organization']),
            ])
            ->unique(fn (array $record): string => $record['branch'].'|'.$record['name'])
            ->sortBy([
                ['branch', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function employees(): Collection
    {
        return $this->records()
            ->map(fn (array $record): array => [
                'employee_code'         => $record['employee_code'],
                'branch'                => $record['branch'],
                'organization'          => $record['organization'],
                'name'                  => $record['name'],
                'job_title'             => $record['job'],
                'work_email'            => $record['email'],
                'mobile_phone'          => $record['mobile_phone'],
                'work_phone'            => $record['phone'],
                'private_street1'       => $record['current_address'] ?? $record['address'],
                'birthday'              => $record['birth_date'],
                'marital'               => $record['marital'],
                'gender'                => $record['gender'],
                'additional_note'       => $this->buildAdditionalNote($record),
                'employment_started_at' => $this->resolveEmploymentTimestamp($record)?->toDateTimeString(),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function positions(): Collection
    {
        return $this->records()
            ->filter(fn (array $record) => filled($record['branch']) && filled($record['organization']) && filled($record['job']))
            ->groupBy(fn (array $record): string => $record['branch'].'|'.$record['organization'].'|'.$record['job'])
            ->map(function (Collection $records, string $key): array {
                [$branch, $organization, $job] = explode('|', $key, 3);

                return [
                    'branch'         => $branch,
                    'organization'   => $organization,
                    'name'           => $job,
                    'employee_count' => $records->count(),
                    'leader_title'   => $records
                        ->pluck('title')
                        ->filter(fn ($title) => filled($title))
                        ->countBy()
                        ->sortDesc()
                        ->keys()
                        ->first(),
                ];
            })
            ->sortBy([
                ['branch', 'asc'],
                ['organization', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    private function buildName(array $record): ?string
    {
        $name = collect([
            $this->normalizeText($record['first_name'] ?? null),
            $this->normalizeText($record['last_name'] ?? null),
        ])->filter()->implode(' ');

        return filled($name) ? $name : null;
    }

    private function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = Str::squish($value);

        return $value === '' ? null : $value;
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = $this->normalizeText($value);

        if (! filled($value)) {
            return null;
        }

        return rescue(
            fn (): string => CarbonImmutable::createFromFormat('d M Y', $value)->toDateString(),
            null,
            report: false,
        );
    }

    private function normalizeMaritalStatus(?string $value): string
    {
        return match (Str::lower($this->normalizeText($value) ?? '')) {
            'married'  => MaritalStatus::Married->value,
            'divorced' => MaritalStatus::Divorced->value,
            'widow', 'widower' => MaritalStatus::Widowed->value,
            default => MaritalStatus::Single->value,
        };
    }

    private function normalizeGender(?string $value): string
    {
        return match (Str::lower($this->normalizeText($value) ?? '')) {
            'male'   => Gender::Male->value,
            'female' => Gender::Female->value,
            default  => Gender::Other->value,
        };
    }

    private function buildCompanyCode(string $branch): string
    {
        return 'CMP-'.Str::upper(substr(sha1($branch), 0, 8));
    }

    private function buildColor(string $value): string
    {
        return '#'.substr(md5($value), 0, 6);
    }

    private function buildAdditionalNote(array $record): ?string
    {
        $notes = collect([
            filled($record['title']) ? "Title: {$record['title']}" : null,
            filled($record['tax_status']) ? "Tax Status: {$record['tax_status']}" : null,
            filled($record['religion']) ? "Religion: {$record['religion']}" : null,
            filled($record['blood_type']) ? "Blood Type: {$record['blood_type']}" : null,
            filled($record['join_date']) ? "Join Date: {$record['join_date']}" : null,
            filled($record['grade']) ? "Grade: {$record['grade']}" : null,
            filled($record['class']) ? "Class: {$record['class']}" : null,
        ])->filter();

        return $notes->isEmpty() ? null : $notes->implode(PHP_EOL);
    }

    private function resolveEmploymentTimestamp(array $record): ?CarbonImmutable
    {
        $employmentTimestamp = $this->resolveEmploymentTimestampFromEmployeeCode($record['employee_code'] ?? null);

        if ($employmentTimestamp instanceof CarbonImmutable) {
            return $employmentTimestamp;
        }

        if (! filled($record['join_date'] ?? null)) {
            return null;
        }

        return rescue(
            fn (): CarbonImmutable => CarbonImmutable::createFromFormat('Y-m-d', $record['join_date'])->startOfDay(),
            null,
            report: false,
        );
    }

    private function resolveEmploymentTimestampFromEmployeeCode(?string $employeeCode): ?CarbonImmutable
    {
        $employeeCode = $this->normalizeText($employeeCode);

        if (! filled($employeeCode)) {
            return null;
        }

        if (! preg_match('/^(?<date>\d{4}\.\d{2}\.\d{2})\.\d+$/', $employeeCode, $matches)) {
            return null;
        }

        return rescue(
            fn (): CarbonImmutable => CarbonImmutable::createFromFormat('Y.m.d', $matches['date'])->startOfDay(),
            null,
            report: false,
        );
    }
}
