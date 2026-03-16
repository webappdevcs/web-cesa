<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Department;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeJobPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Enums\PermissionType;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class EmployeeSeeder extends Seeder
{
    private const string DEFAULT_EMPLOYEE_PASSWORD = 'password';

    private const string EMPLOYEE_ROLE_NAME = 'user';

    public function run(): void
    {
        $seedData = $this->seedData();
        $creatorId = User::query()->value('id');
        $employeeRole = $this->resolveEmployeeRole();

        DB::transaction(function () use ($seedData, $creatorId, $employeeRole): void {
            DB::table('employees_employees')->delete();
            DB::table('partners_partners')->where('sub_type', 'employee')->delete();

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

            $seedData->employees()->each(function (array $employeeData) use ($companyIds, $departments, $positions, $creatorId, $employeeRole): void {
                $companyId = $companyIds->get($employeeData['branch']);
                $departmentId = $departments->get($companyId.'|'.$employeeData['organization'])?->id;
                $jobId = $positions->get($companyId.'|'.$departmentId.'|'.$employeeData['job_title'])?->id;
                $loginEmail = $this->resolveLoginEmail($employeeData);
                $user = $this->createOrUpdateEmployeeUser(
                    name: $employeeData['name'],
                    email: $loginEmail,
                    companyId: $companyId,
                    creatorId: $creatorId,
                    employeeRole: $employeeRole,
                );

                $employee = new Employee([
                    'user_id'          => $user->id,
                    'creator_id'       => $creatorId,
                    'company_id'       => $companyId,
                    'department_id'    => $departmentId,
                    'job_id'           => $jobId,
                    'time_zone'        => config('app.timezone', 'UTC'),
                    'name'             => $employeeData['name'],
                    'employee_code'    => $employeeData['employee_code'],
                    'job_title'        => $employeeData['job_title'],
                    'work_email'       => $loginEmail,
                    'mobile_phone'     => $employeeData['mobile_phone'],
                    'work_phone'       => $employeeData['work_phone'],
                    'private_street1'  => $employeeData['private_street1'],
                    'birthday'         => $employeeData['birthday'],
                    'marital'          => $employeeData['marital'],
                    'gender'           => $employeeData['gender'],
                    'employee_type'    => 'employee',
                    'is_active'        => true,
                    'additional_note'  => $employeeData['additional_note'],
                ]);

                if (filled($employeeData['employment_started_at'] ?? null)) {
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
        \Illuminate\Support\Collection $companyIds,
        \Illuminate\Support\Collection $departments,
    ): void {
        $employees = Employee::query()
            ->whereIn('employee_code', $seedData->records()->pluck('employee_code'))
            ->get()
            ->keyBy('employee_code');

        $seedData->records()
            ->groupBy(fn (array $record): string => $record['branch'].'|'.$record['organization'])
            ->each(function (\Illuminate\Support\Collection $records, string $groupKey) use ($companyIds, $departments, $employees): void {
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

    private function resolveEmployeeRole(): Role
    {
        return Role::query()->firstOrCreate([
            'name'       => self::EMPLOYEE_ROLE_NAME,
            'guard_name' => 'web',
        ]);
    }

    private function createOrUpdateEmployeeUser(
        string $name,
        string $email,
        ?int $companyId,
        ?int $creatorId,
        Role $employeeRole,
    ): User {
        $timestamp = now();
        $existingUser = DB::table('users')
            ->where('email', $email)
            ->first();

        $payload = [
            'name'                => $name,
            'email'               => $email,
            'creator_id'          => $creatorId,
            'default_company_id'  => $companyId,
            'resource_permission' => PermissionType::INDIVIDUAL->value,
            'is_active'           => true,
            'email_verified_at'   => $existingUser?->email_verified_at ?? $timestamp,
            'deleted_at'          => null,
            'updated_at'          => $timestamp,
        ];

        if ($existingUser) {
            DB::table('users')
                ->where('id', $existingUser->id)
                ->update($payload);

            $userId = (int) $existingUser->id;
        } else {
            $userId = (int) DB::table('users')->insertGetId([
                ...$payload,
                'password'       => Hash::make(self::DEFAULT_EMPLOYEE_PASSWORD),
                'remember_token' => Str::random(10),
                'created_at'     => $timestamp,
            ]);
        }

        $partnerId = $this->createOrUpdateUserPartner(
            userId: $userId,
            name: $name,
            email: $email,
            companyId: $companyId,
            creatorId: $creatorId,
        );

        DB::table('users')
            ->where('id', $userId)
            ->update([
                'partner_id' => $partnerId,
                'updated_at' => $timestamp,
            ]);

        if ($companyId) {
            DB::table('user_allowed_companies')->updateOrInsert([
                'user_id'    => $userId,
                'company_id' => $companyId,
            ]);
        }

        $user = User::query()
            ->withTrashed()
            ->findOrFail($userId);

        if (! $user->roles()->whereKey($employeeRole->getKey())->exists()) {
            $user->assignRole($employeeRole);
        }

        return $user;
    }

    private function createOrUpdateUserPartner(
        int $userId,
        string $name,
        string $email,
        ?int $companyId,
        ?int $creatorId,
    ): int {
        $partner = Partner::query()
            ->withTrashed()
            ->where('user_id', $userId)
            ->where('sub_type', 'partner')
            ->first();

        if (! $partner) {
            $existingPartnerId = DB::table('users')
                ->where('id', $userId)
                ->value('partner_id');

            if ($existingPartnerId) {
                $partner = Partner::query()
                    ->withTrashed()
                    ->find($existingPartnerId);
            }
        }

        $partner ??= new Partner;

        $partner->fill([
            'account_type' => 'individual',
            'sub_type'     => 'partner',
            'name'         => $name,
            'email'        => $email,
            'company_id'   => $companyId,
            'creator_id'   => $creatorId ?? $userId,
            'user_id'      => $userId,
        ]);

        $partner->deleted_at = null;
        $partner->save();

        return (int) $partner->id;
    }

    private function resolveLoginEmail(array $employeeData): string
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
