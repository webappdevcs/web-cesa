<?php

namespace Cesa\FormTransfer\Database\Seeders;

use Illuminate\Database\Seeder;

class FormTransferDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
            TransferWorkflowSeeder::class,
            TransferRequestSeeder::class,
        ]);
    }
}
