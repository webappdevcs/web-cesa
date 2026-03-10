<?php

namespace Cesa\ExitClearance\Database\Seeders;

use Cesa\ExitClearance\Models\Approver;
use Illuminate\Database\Seeder;

class ApproverSeeder extends Seeder
{
    /**
     * Unique approvers extracted from cleaned app.gs FLOWS object
     * Each email has exactly one name and one title
     */
    private const APPROVERS = [
        ['name' => 'Arik', 'email' => 'arikfio@completeselular.com', 'title' => 'IT Manager'],
        ['name' => 'Evi', 'email' => 'evi.mkli@completeselular.com', 'title' => 'Finance Accounting'],
        ['name' => 'Tunisa', 'email' => 'nisa.armaju@gmail.com', 'title' => 'Account Receivable'],
        ['name' => 'Deby Susanto', 'email' => 'deby.oceanspace@gmail.com', 'title' => 'Internal Audit'],
        ['name' => 'Nadya', 'email' => 'nadya@completeselular.com', 'title' => 'GA Officer'],
        ['name' => 'Ester Septiany', 'email' => 'ester@completeselular.com', 'title' => 'HR Manager'],
        ['name' => 'SANDY RAMADHANI', 'email' => 'sandyramadhani0502@gmail.com', 'title' => 'Personalia'],
        ['name' => 'Hendra Setia Permana', 'email' => 'permanahendra.murni@gmail.com', 'title' => 'RGM'],
        ['name' => 'Jejen Mutakhir', 'email' => 'jejen@completeselular.com', 'title' => 'CSO'],
        ['name' => 'Firman Syahbana', 'email' => 'firman@completeselular.com', 'title' => 'COO'],
        ['name' => 'Robby Agustina', 'email' => 'Robbymsi19@gmail.com', 'title' => 'RGM'],
        ['name' => 'Toyo', 'email' => 'sutoyo.samsungmobile@gmail.com', 'title' => 'RGM'],
        ['name' => 'Agus Supangat', 'email' => 'agus.supangat@gmail.com', 'title' => 'CSO'],
        ['name' => 'Albertus Adi N.', 'email' => 'adi@completeselular.com', 'title' => 'CIA'],
        ['name' => 'Dian Fatmawati', 'email' => 'dian@completeselular.com', 'title' => 'Oprasional Manager'],
        ['name' => 'Erniati', 'email' => 'erny@completeselular.com', 'title' => 'Purchase Manager'],
        ['name' => 'Kevin', 'email' => 'vinzrvt@gmail.com', 'title' => 'Online Manager'],
        ['name' => 'William Surya Putra', 'email' => 'william@completeselular.com', 'title' => 'RSM'],
        ['name' => 'Firman Syahbana', 'email' => 'kecilnazira@gmail.com', 'title' => 'COO'],
        ['name' => 'Nurbaiti Riaseha', 'email' => 'kecilsabrina@gmail.com', 'title' => 'Finance Accounting'],
        ['name' => 'Nike Lavanti', 'email' => 'lavantinike@gmail.com', 'title' => 'Tax Supervisor'],
    ];

    public function run(): void
    {
        foreach (self::APPROVERS as $approverData) {
            Approver::query()->updateOrCreate(
                ['email' => $approverData['email']],
                [
                    'name'       => $approverData['name'],
                    'phone'      => null,
                    'title'      => $approverData['title'],
                    'created_by' => null,
                ]
            );
        }
    }
}
