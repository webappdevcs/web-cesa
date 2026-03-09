<?php

namespace Cesa\FormTransfer\Database\Factories;

use Cesa\FormTransfer\Models\TransferBank;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferBankFactory extends Factory
{
    protected $model = TransferBank::class;

    public function definition(): array
    {
        $bankCodes = [
            'BCA'      => 'Bank Central Asia',
            'BRI'      => 'Bank Rakyat Indonesia',
            'MANDIRI'  => 'Bank Mandiri',
            'OCBC'     => 'Bank OCBC NISP',
            'DANAMON'  => 'Bank Danamon',
            'BNI'      => 'Bank Negara Indonesia',
            'CITIBANK' => 'Citibank',
            'UOB'      => 'Bank UOB Indonesia',
            'MAYBANK'  => 'Bank Maybank Indonesia',
            'PANIN'    => 'Bank Panin',
            'BSI'      => 'Bank Syariah Indonesia',
            'LAINNYA'  => 'Lain-lain',
        ];

        // Use unique suffix to avoid collision across tests
        $codes = array_keys($bankCodes);
        $baseCode = $this->faker->randomElement($codes);
        $suffix = substr(uniqid(), -4);
        $code = $baseCode.'-'.$suffix;

        $name = $bankCodes[$baseCode];

        return [
            'code'       => $code,
            'name'       => $name,
            'short_name' => $baseCode === 'LAINNYA' ? 'LAIN-LAIN' : $baseCode,
            'is_active'  => true,
            'sort_order' => array_search($baseCode, array_keys($bankCodes)) + 1,
        ];
    }
}
