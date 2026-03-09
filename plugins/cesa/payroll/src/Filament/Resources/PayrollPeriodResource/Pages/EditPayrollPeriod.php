<?php

namespace Cesa\Payroll\Filament\Resources\PayrollPeriodResource\Pages;

use Cesa\Payroll\Filament\Resources\PayrollPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayrollPeriod extends EditRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
