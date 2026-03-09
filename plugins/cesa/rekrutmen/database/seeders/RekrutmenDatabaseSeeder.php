<?php

namespace Cesa\Rekrutmen\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Use DatabaseSeeder for production-safe seeds and RekrutmenDemoSeeder for sample data.
 */
class RekrutmenDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
        ]);
    }
}
