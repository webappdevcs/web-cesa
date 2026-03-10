<?php

namespace Cesa\ExitClearance\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            ApproverSeeder::class,
            DepartmentApproverSeeder::class,
        ]);
    }
}
