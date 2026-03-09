<?php

namespace Cesa\FormTransfer\Database\Seeders;

use Cesa\FormTransfer\Models\TransferBank;
use Illuminate\Database\Seeder;

class TransferBankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['code' => 'BCA', 'name' => 'Bank Central Asia', 'short_name' => 'BCA', 'sort_order' => 1],
            ['code' => 'BRI', 'name' => 'Bank Rakyat Indonesia', 'short_name' => 'BRI', 'sort_order' => 2],
            ['code' => 'MANDIRI', 'name' => 'Bank Mandiri', 'short_name' => 'MANDIRI', 'sort_order' => 3],
            ['code' => 'OCBC', 'name' => 'Bank OCBC NISP', 'short_name' => 'OCBC', 'sort_order' => 4],
            ['code' => 'DANAMON', 'name' => 'Bank Danamon', 'short_name' => 'DANAMON', 'sort_order' => 5],
            ['code' => 'BNI', 'name' => 'Bank Negara Indonesia', 'short_name' => 'BNI', 'sort_order' => 6],
            ['code' => 'CITIBANK', 'name' => 'Citibank', 'short_name' => 'CITIBANK', 'sort_order' => 7],
            ['code' => 'UOB', 'name' => 'Bank UOB Indonesia', 'short_name' => 'UOB', 'sort_order' => 8],
            ['code' => 'MAYBANK', 'name' => 'Bank Maybank Indonesia', 'short_name' => 'MAYBANK', 'sort_order' => 9],
            ['code' => 'PANIN', 'name' => 'Bank Panin', 'short_name' => 'PANIN', 'sort_order' => 10],
            ['code' => 'BSI', 'name' => 'Bank Syariah Indonesia', 'short_name' => 'BSI', 'sort_order' => 11],
            ['code' => 'LAINNYA', 'name' => 'Lain-lain', 'short_name' => 'LAIN-LAIN', 'sort_order' => 12],
        ];

        foreach ($banks as $bank) {
            TransferBank::updateOrCreate(
                ['code' => $bank['code']],
                [
                    'name'       => $bank['name'],
                    'short_name' => $bank['short_name'],
                    'sort_order' => $bank['sort_order'],
                    'is_active'  => true,
                ]
            );
        }
    }
}
