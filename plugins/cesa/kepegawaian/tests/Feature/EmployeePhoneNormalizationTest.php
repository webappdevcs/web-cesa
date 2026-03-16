<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmployeePhoneNormalizationTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function mobilePhoneProvider(): array
    {
        return [
            'blank'              => [null, null],
            'already 62'         => ['628123456789', '628123456789'],
            'plus 62'            => ['+62 812-3456-789', '628123456789'],
            'leading zero'       => ['0812 3456 789', '628123456789'],
            'leading eight'      => ['8123456789', '628123456789'],
            'other digits only'  => ['021-555-000', '6221555000'],
        ];
    }

    #[DataProvider('mobilePhoneProvider')]
    public function test_it_normalizes_mobile_phone_to_a_standard_format(?string $input, ?string $expected): void
    {
        $employee = new Employee([
            'mobile_phone' => $input,
        ]);

        $this->assertSame($expected, $employee->mobile_phone);
    }
}
