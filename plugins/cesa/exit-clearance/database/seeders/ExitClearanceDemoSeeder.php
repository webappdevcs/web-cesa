<?php

namespace Cesa\ExitClearance\Database\Seeders;

use Illuminate\Database\Seeder;

class ExitClearanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
            RequestSeeder::class,
        ]);
    }
}
