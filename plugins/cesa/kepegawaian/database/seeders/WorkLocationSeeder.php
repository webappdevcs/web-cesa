<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('employees_work_locations')->delete();

        $company = $this->resolveDefaultCompany();

        $user = User::first();

        $workLocations = [
            [
                'name'               => 'Home',
                'company_id'         => $company?->id,
                'location_type'      => 'home',
                'is_active'          => 1,
                'creator_id'         => $user?->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Building 1, Second Floor',
                'company_id'         => $company?->id,
                'location_type'      => 'office',
                'is_active'          => 1,
                'creator_id'         => $user?->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'name'               => 'Other',
                'company_id'         => $company?->id,
                'location_type'      => 'other',
                'is_active'          => 1,
                'creator_id'         => $user?->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ];

        DB::table('employees_work_locations')->insert($workLocations);
    }

    private function resolveDefaultCompany(): ?Company
    {
        $placeholderCompanyName = Str::of(config('app.name', ''))->squish()->value();
        $seedData = new EmployeeSeedData;
        $preferredCompanyName = $seedData->records()
            ->pluck('branch')
            ->filter(fn (?string $branch): bool => filled($branch))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        if (filled($preferredCompanyName)) {
            $company = Company::query()->where('name', $preferredCompanyName)->first();

            if ($company instanceof Company) {
                return $company;
            }
        }

        if (filled($placeholderCompanyName)) {
            $company = Company::query()
                ->where('name', '!=', $placeholderCompanyName)
                ->orderBy('id')
                ->first();

            if ($company instanceof Company) {
                return $company;
            }
        }

        return Company::query()->orderBy('id')->first();
    }
}
