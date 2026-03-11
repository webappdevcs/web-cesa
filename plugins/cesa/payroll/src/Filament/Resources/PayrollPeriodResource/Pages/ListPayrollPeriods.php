<?php

namespace Cesa\Payroll\Filament\Resources\PayrollPeriodResource\Pages;

use Cesa\Payroll\Filament\Resources\PayrollPeriodResource;
use Cesa\Payroll\Services\GeneratePayrollService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle')
                ->modal()
                ->slideOver()
                ->modalWidth('md')
                ->schema(fn (Schema $schema): Schema => static::getResource()::form($schema->columns(1)))
                ->mutateFormDataUsing(function (array $data): array {
                    // Store auto_generate for later use
                    $autoGenerate = $data['auto_generate'] ?? false;
                    unset($data['auto_generate']);

                    // Store in session for afterCreate
                    session()->flash('auto_generate_payroll', $autoGenerate);

                    // Ensure status is set to 'open' for new records
                    if (! isset($data['status'])) {
                        $data['status'] = 'open';
                    }

                    return $data;
                })
                ->after(function ($record): void {
                    // Check if auto_generate was checked
                    if (session()->pull('auto_generate_payroll', false)) {
                        try {
                            $service = app(GeneratePayrollService::class);
                            $service->generate($record);

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
                }),
        ];
    }
}
