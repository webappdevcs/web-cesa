<?php

namespace Cesa\ExitClearance\Tests\Feature\Seeders;

use Cesa\ExitClearance\Database\Seeders\DatabaseSeeder;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Illuminate\Support\Facades\DB;

class DatabaseSeederTest extends ExitClearanceTestCase
{
    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $firstDepartments = DB::table('exit_clearance_departments')->count();
        $firstApprovers = DB::table('exit_clearance_approvers')->count();
        $firstPivot = DB::table('exit_clearance_department_approver')->count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($firstDepartments, DB::table('exit_clearance_departments')->count());
        $this->assertSame($firstApprovers, DB::table('exit_clearance_approvers')->count());
        $this->assertSame($firstPivot, DB::table('exit_clearance_department_approver')->count());

        $this->assertSame(
            DB::table('exit_clearance_approvers')->count(),
            DB::table('exit_clearance_approvers')->distinct('email')->count('email')
        );
    }
}
