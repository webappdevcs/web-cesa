<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Cesa\Kepegawaian\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedData = new EmployeeSeedData;
        $creatorId = User::query()->value('id');

        DB::transaction(function () use ($seedData, $creatorId): void {
            DB::table('employees_departments')->delete();

            $companyIds = Company::query()
                ->whereIn('name', $seedData->companies()->pluck('name'))
                ->pluck('id', 'name');

            $seedData->departments()->each(function (array $departmentData) use ($companyIds, $creatorId): void {
                $companyId = $companyIds->get($departmentData['branch']);

                if (! $companyId) {
                    return;
                }

                Department::query()->create([
                    'company_id' => $companyId,
                    'creator_id' => $creatorId,
                    'name'       => $departmentData['name'],
                    'color'      => $departmentData['color'],
                    'manager_id' => null,
                ]);
            });
        });
    }
}
