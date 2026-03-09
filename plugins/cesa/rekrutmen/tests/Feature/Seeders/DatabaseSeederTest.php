<?php

namespace Cesa\Rekrutmen\Tests\Feature\Seeders;

use Cesa\Rekrutmen\Database\Seeders\DatabaseSeeder;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use Illuminate\Support\Facades\DB;

class DatabaseSeederTest extends RekrutmenTestCase
{
    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $firstPipelineCount = DB::table('rekrutmen_pipelines')->count();
        $firstStageCount = DB::table('rekrutmen_stages')->count();

        $this->seed(DatabaseSeeder::class);

        $secondPipelineCount = DB::table('rekrutmen_pipelines')->count();
        $secondStageCount = DB::table('rekrutmen_stages')->count();

        $this->assertSame($firstPipelineCount, $secondPipelineCount);
        $this->assertSame($firstStageCount, $secondStageCount);
        $this->assertSame(1, $secondPipelineCount);
        $this->assertSame(5, $secondStageCount);
    }
}
