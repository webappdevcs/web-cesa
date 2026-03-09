<?php

namespace Cesa\FormTransfer\Tests\Feature\Seeders;

use Cesa\FormTransfer\Database\Seeders\DatabaseSeeder;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Support\Facades\DB;

class DatabaseSeederTest extends FormTransferTestCase
{
    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $firstBanks = DB::table('form_transfer_banks')->count();
        $firstForms = DB::table('form_transfers')->count();
        $firstWorkflows = DB::table('form_transfer_approval_workflows')->count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($firstBanks, DB::table('form_transfer_banks')->count());
        $this->assertSame($firstForms, DB::table('form_transfers')->count());
        $this->assertSame($firstWorkflows, DB::table('form_transfer_approval_workflows')->count());
    }
}
