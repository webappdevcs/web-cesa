<?php

namespace Cesa\Payroll\Filament\Resources\PayrollPeriodResource\Pages;

use Cesa\Payroll\Filament\Resources\PayrollPeriodResource;
use Cesa\Payroll\Services\GeneratePayrollService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollPeriod extends CreateRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove auto_generate from data before saving to database
        // It's only used for UI logic, not a database field
        unset($data['auto_generate']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Check if auto_generate checkbox was checked
        if ($this->data['auto_generate'] ?? false) {
            try {
                $service = app(GeneratePayrollService::class);
                $service->generate($this->record);

                Notification::make()
                    ->title(__('payroll::app.notifications.payroll_generated.title'))
                    ->body(__('payroll::app.notifications.payroll_generated.body'))
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error')
                    ->body('Failed to generate payroll: '.$e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
}
