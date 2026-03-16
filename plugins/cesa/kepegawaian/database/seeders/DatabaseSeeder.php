<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        $this->call([
            CompanySeeder::class,
            EmploymentTypeSeeder::class,
            WorkLocationSeeder::class,
            EmployeeCategorySeeder::class,
            DepartureReasonSeeder::class,
            CalendarSeeder::class,
            CalendarAttendanceSeeder::class,
            ActivityPlanTemplateSeeder::class,
            DepartmentSeeder::class,
            EmployeeJobPositionSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
