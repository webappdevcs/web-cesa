<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Database\Seeders\Support\EmployeeSeedData;
use Tests\TestCase;

class EmployeeSeedDataTest extends TestCase
{
    public function test_it_builds_company_department_and_employee_seed_data_from_json(): void
    {
        $seedData = new EmployeeSeedData;

        $this->assertCount(496, $seedData->records());
        $this->assertCount(13, $seedData->companies());
        $this->assertCount(43, $seedData->departments());
        $this->assertCount(222, $seedData->positions());
        $this->assertCount(496, $seedData->employees());

        $firstEmployee = $seedData->employees()->first();
        $firstPosition = $seedData->positions()->first();

        $this->assertSame('2024.08.15.03', $firstEmployee['employee_code']);
        $this->assertSame('AAN FEBRIAN PRATAMA', $firstEmployee['name']);
        $this->assertSame('PT Media Selular Indonesia', $firstEmployee['branch']);
        $this->assertSame('DISTRIBUTION SALES', $firstEmployee['organization']);
        $this->assertSame('2024-08-15 00:00:00', $firstEmployee['employment_started_at']);
        $this->assertSame('COMPLETE SOLUSI NUSANTARA', $firstPosition['branch']);
        $this->assertSame('CSN RETAIL STORE', $firstPosition['organization']);
        $this->assertSame('FRONTLINER', $firstPosition['name']);
    }

    public function test_it_falls_back_to_join_date_when_employee_code_does_not_include_a_join_date(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-seed-data-');

        file_put_contents($path, json_encode([
            [
                'id_employee' => 'TMA-023',
                'first_name'  => 'Alda',
                'last_name'   => 'Fallback',
                'join_date'   => '13 Mar 2024',
            ],
            [
                'id_employee' => 'TMA-024',
                'first_name'  => 'No',
                'last_name'   => 'Date',
                'join_date'   => null,
            ],
        ], JSON_THROW_ON_ERROR));

        $seedData = new EmployeeSeedData($path);
        $employees = $seedData->employees()->values();

        unlink($path);

        $this->assertSame('2024-03-13 00:00:00', $employees[0]['employment_started_at']);
        $this->assertNull($employees[1]['employment_started_at']);
    }
}
