<?php

namespace Cesa\ExitClearance\Database\Seeders;

use Cesa\ExitClearance\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentApproverSeeder extends Seeder
{
    /**
     * Exact FLOWS structure from app.gs (single source of truth)
     * Note: defaultFlow is excluded as it's for testing purposes
     */
    private const FLOW_APPROVERS = [
        'HR' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'SALES_AREA_PAK_HENDRA' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'permanahendra.murni@gmail.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'SALES_AREA_PAK_JEJEN' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'jejen@completeselular.com',
            'firman@completeselular.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'SALES_AREA_PAK_ROBBY' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'firman@completeselular.com',
            'nadya@completeselular.com',
            'Robbymsi19@gmail.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'SALES_AREA_PAK_TOYO' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'firman@completeselular.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'sutoyo.samsungmobile@gmail.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'IT' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'DISTRIBUTION' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'agus.supangat@gmail.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'FINANCE' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'AUDIT' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'adi@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'WAREHOUSE' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'firman@completeselular.com',
            'dian@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'SCM' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'erny@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'ONLINE' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'vinzrvt@gmail.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'MARKOM' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'DATA' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
            'nisa.armaju@gmail.com',
            'deby.oceanspace@gmail.com',
            'nadya@completeselular.com',
            'ester@completeselular.com',
            'sandyramadhani0502@gmail.com',
        ],
        'Online' => [
            'arikfio@completeselular.com',
            'evi.mkli@completeselular.com',
        ],
        'Retail' => [
            'william@completeselular.com',
            'evi.mkli@completeselular.com',
        ],
        'Data' => [
            'firman@completeselular.com',
            'evi.mkli@completeselular.com',
        ],
        'IA' => [
            'adi@completeselular.com',
            'evi.mkli@completeselular.com',
        ],
        'AP' => [
            'evi.mkli@completeselular.com',
        ],
        'Testing' => [
            'kecilsabrina@gmail.com',
            'kecilnazira@gmail.com',
        ],
        'Pajak' => [
            'lavantinike@gmail.com',
            'evi.mkli@completeselular.com',
        ],
        'Transfer' => [
            'evi.mkli@completeselular.com',
        ],
        'Kas' => [
            'evi.mkli@completeselular.com',
        ],
        'Mitra' => [
            'evi.mkli@completeselular.com',
        ],
        'Accounting' => [
            'evi.mkli@completeselular.com',
        ],
        'Busdev' => [
            'arikfio@completeselular.com',
            'firman@completeselular.com',
            'evi.mkli@completeselular.com',
        ],
    ];

    public function run(): void
    {
        foreach (self::FLOW_APPROVERS as $flowName => $emails) {
            $department = Department::query()->where('code', $flowName)->first();

            if (! $department) {
                continue;
            }

            $approverIds = DB::table('exit_clearance_approvers')
                ->whereIn('email', $emails)
                ->pluck('id')
                ->all();

            if ($approverIds === []) {
                continue;
            }

            $department->approvers()->syncWithoutDetaching($approverIds);
        }
    }
}
