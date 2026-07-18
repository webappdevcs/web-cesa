<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Department;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeJobPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $seedData = $this->seedData();
        $creatorId = User::query()->value('id');

        DB::transaction(function () use ($seedData, $creatorId): void {
            $companyIds = Company::query()
                ->whereIn('name', $seedData->companies()->pluck('name'))
                ->pluck('id', 'name');

            $departments = Department::query()
                ->whereIn('company_id', $companyIds->values())
                ->get()
                ->keyBy(fn (Department $department): string => $department->company_id.'|'.$department->name);

            $positions = EmployeeJobPosition::query()
                ->whereIn('company_id', $companyIds->values())
                ->get()
                ->keyBy(fn (EmployeeJobPosition $position): string => $position->company_id.'|'.$position->department_id.'|'.$position->name);

            $seedData->employees()->each(function (array $employeeData) use ($companyIds, $departments, $positions, $creatorId): void {
                $companyId = $companyIds->get($employeeData['branch']);
                $departmentId = $departments->get($companyId.'|'.$employeeData['organization'])?->id;
                $jobId = $positions->get($companyId.'|'.$departmentId.'|'.$employeeData['job_title'])?->id;
                $workEmail = $this->resolveEmployeeEmail($employeeData);

                if (Employee::onlyTrashed()->where('employee_code', $employeeData['employee_code'])->exists()) {
                    return;
                }

                $employee = Employee::query()->firstOrNew([
                    'employee_code' => $employeeData['employee_code'],
                ]);

                $isNewEmployee = ! $employee->exists;

                $employee->fill([
                    'company_id'       => $companyId,
                    'department_id'    => $departmentId,
                    'job_id'           => $jobId,
                    'time_zone'        => config('app.timezone', 'UTC'),
                    'name'             => $employeeData['name'],
                    'job_title'        => $employeeData['job_title'],
                    'work_email'       => $workEmail,
                    'mobile_phone'     => $employeeData['mobile_phone'],
                    'work_phone'       => $employeeData['work_phone'],
                    'private_street1'  => $employeeData['private_street1'],
                    'birthday'         => $employeeData['birthday'],
                    'marital'          => $employeeData['marital'],
                    'gender'           => $employeeData['gender'],
                    'employee_type'    => 'employee',
                    'additional_note'  => $employeeData['additional_note'],
                ]);

                if ($isNewEmployee) {
                    $employee->creator_id = $creatorId;
                    $employee->is_active = true;
                }

                if ($isNewEmployee && filled($employeeData['employment_started_at'] ?? null)) {
                    $employee->created_at = $employeeData['employment_started_at'];
                    $employee->updated_at = $employeeData['employment_started_at'];
                }

                $employee->save();
            });

            $this->assignDepartmentManagers($seedData, $companyIds, $departments);
        });
    }

    protected function seedData(): EmployeeSeedData
    {
        return new EmployeeSeedData;
    }

    private function assignDepartmentManagers(
        EmployeeSeedData $seedData,
        Collection $companyIds,
        Collection $departments,
    ): void {
        $employees = Employee::query()
            ->whereIn('employee_code', $seedData->records()->pluck('employee_code'))
            ->get()
            ->keyBy('employee_code');

        $seedData->records()
            ->groupBy(fn (array $record): string => $record['branch'].'|'.$record['organization'])
            ->each(function (Collection $records, string $groupKey) use ($companyIds, $departments, $employees): void {
                [$branch, $organization] = explode('|', $groupKey, 2);

                $companyId = $companyIds->get($branch);
                $department = $departments->get($companyId.'|'.$organization);

                if (! $department) {
                    return;
                }

                $managerRecord = $records
                    ->sortByDesc(fn (array $record): int => $this->resolveManagerPriority($record['title'], $record['job']))
                    ->first(fn (array $record): bool => $this->resolveManagerPriority($record['title'], $record['job']) > 0);

                if (! $managerRecord) {
                    return;
                }

                $manager = $employees->get($managerRecord['employee_code']);

                if (! $manager) {
                    return;
                }

                $department->forceFill([
                    'manager_id' => $manager->id,
                ])->save();
            });
    }

    private function resolveManagerPriority(?string $title, ?string $jobTitle): int
    {
        $value = Str::upper(trim(implode(' ', array_filter([$title, $jobTitle]))));

        return match (true) {
            str_contains($value, 'BOD')         => 60,
            str_contains($value, 'CHIEF')       => 50,
            str_contains($value, 'MANAGER')     => 40,
            str_contains($value, 'TEAM LEADER') => 30,
            str_contains($value, 'LEADER')      => 25,
            str_contains($value, 'COORDINATOR') => 20,
            default                             => 0,
        };
    }

    private function resolveEmployeeEmail(array $employeeData): string
    {
        $email = Str::lower(trim((string) ($employeeData['work_email'] ?? '')));

        if ($email !== '') {
            return $email;
        }

        $localPart = Str::slug(
            (string) ($employeeData['employee_code'] ?? $employeeData['name'] ?? 'employee'),
            '.',
        );

        return ($localPart !== '' ? $localPart : 'employee').'@employee.seed.local';
    }
}
