<?php

namespace Cesa\Kepegawaian\Database\Seeders;

use Illuminate\Database\Seeder;
use LogicException;

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
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException(
                'Kepegawaian demo seeders may only run in local or testing environments.'
            );
        }

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
