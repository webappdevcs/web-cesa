<?php

namespace Cesa\ExitClearance\Database\Seeders;

use Cesa\ExitClearance\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Departments extracted from app.gs FLOWS object (single source of truth)
     * Note: defaultFlow is excluded as it's for testing purposes
     * Codes use exact flow keys from app.gs to ensure 1:1 mapping
     */
    private const DEPARTMENTS = [
        ['code' => 'HR', 'name' => 'HR', 'description' => null],
        ['code' => 'SALES_AREA_PAK_HENDRA', 'name' => 'SALES_AREA_PAK_HENDRA', 'description' => null],
        ['code' => 'SALES_AREA_PAK_JEJEN', 'name' => 'SALES_AREA_PAK_JEJEN', 'description' => null],
        ['code' => 'SALES_AREA_PAK_ROBBY', 'name' => 'SALES_AREA_PAK_ROBBY', 'description' => null],
        ['code' => 'SALES_AREA_PAK_TOYO', 'name' => 'SALES_AREA_PAK_TOYO', 'description' => null],
        ['code' => 'IT', 'name' => 'IT', 'description' => null],
        ['code' => 'DISTRIBUTION', 'name' => 'DISTRIBUTION', 'description' => null],
        ['code' => 'FINANCE', 'name' => 'FINANCE', 'description' => null],
        ['code' => 'AUDIT', 'name' => 'AUDIT', 'description' => null],
        ['code' => 'WAREHOUSE', 'name' => 'WAREHOUSE', 'description' => null],
        ['code' => 'SCM', 'name' => 'SCM', 'description' => null],
        ['code' => 'ONLINE', 'name' => 'ONLINE', 'description' => null],
        ['code' => 'MARKOM', 'name' => 'MARKOM', 'description' => null],
        ['code' => 'DATA', 'name' => 'DATA', 'description' => null],
        ['code' => 'Online', 'name' => 'Online', 'description' => null],
        ['code' => 'Retail', 'name' => 'Retail', 'description' => null],
        ['code' => 'Data', 'name' => 'Data', 'description' => null],
        ['code' => 'IA', 'name' => 'IA', 'description' => null],
        ['code' => 'AP', 'name' => 'AP', 'description' => null],
        ['code' => 'Testing', 'name' => 'Testing', 'description' => null],
        ['code' => 'Pajak', 'name' => 'Pajak', 'description' => null],
        ['code' => 'Transfer', 'name' => 'Transfer', 'description' => null],
        ['code' => 'Kas', 'name' => 'Kas', 'description' => null],
        ['code' => 'Mitra', 'name' => 'Mitra', 'description' => null],
        ['code' => 'Accounting', 'name' => 'Accounting', 'description' => null],
        ['code' => 'Busdev', 'name' => 'Busdev', 'description' => null],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::DEPARTMENTS as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                [
                    'name'        => $department['name'],
                    'description' => $department['description'],
                    'created_by'  => null,
                ]
            );
        }
    }
}
