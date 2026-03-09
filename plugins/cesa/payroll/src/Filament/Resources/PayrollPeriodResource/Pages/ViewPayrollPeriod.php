<?php

namespace Cesa\Payroll\Filament\Resources\PayrollPeriodResource\Pages;

use Cesa\Payroll\Filament\Resources\PayrollPeriodResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPayrollPeriod extends ViewRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    public static function getNavigationLabel(): string
    {
        return __('payroll::app.resources.payroll_period.model.singular');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payroll::app.resources.payroll_period.form.sections.period_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('payroll::app.resources.payroll_period.form.fields.name')),
                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('payroll::app.resources.payroll_period.form.fields.status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'open'   => 'gray',
                                        'locked' => 'warning',
                                        'paid'   => 'success',
                                        default  => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('start_date')
                                    ->label(__('payroll::app.resources.payroll_period.form.fields.start_date'))
                                    ->date(),
                                Infolists\Components\TextEntry::make('end_date')
                                    ->label(__('payroll::app.resources.payroll_period.form.fields.end_date'))
                                    ->date(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('payroll::app.resources.payroll_period.table.columns.created_at'))
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->modal()
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}
