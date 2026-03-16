<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Department;
use Cesa\Kepegawaian\Models\EmployeeJobPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class EmployeeJobPositionSeeder extends Seeder
{
    public function run(): void
    {
        $seedData = new EmployeeSeedData;
        $creatorId = User::query()->value('id');

        DB::transaction(function () use ($seedData, $creatorId): void {
            DB::table('employees_job_positions')->delete();

            $companyIds = Company::query()
                ->whereIn('name', $seedData->companies()->pluck('name'))
                ->pluck('id', 'name');

            $departments = Department::query()
                ->whereIn('company_id', $companyIds->values())
                ->get()
                ->keyBy(fn (Department $department): string => $department->company_id.'|'.$department->name);

            $seedData->positions()
                ->values()
                ->each(function (array $positionData, int $index) use ($companyIds, $departments, $creatorId): void {
                    $companyId = $companyIds->get($positionData['branch']);
                    $departmentId = $departments->get($companyId.'|'.$positionData['organization'])?->id;

                    EmployeeJobPosition::query()->create([
                        'sort'               => $index + 1,
                        'name'               => $positionData['name'],
                        'description'        => $positionData['leader_title']
                            ? 'Imported title group: '.$positionData['leader_title']
                            : null,
                        'requirements'       => 'Imported from list-employees.json',
                        'is_active'          => true,
                        'expected_employees' => $positionData['employee_count'],
                        'no_of_employee'     => $positionData['employee_count'],
                        'no_of_recruitment'  => 0,
                        'department_id'      => $departmentId,
                        'company_id'         => $companyId,
                        'creator_id'         => $creatorId,
                        'employment_type_id' => null,
                    ]);
                });
        });
    }
}
