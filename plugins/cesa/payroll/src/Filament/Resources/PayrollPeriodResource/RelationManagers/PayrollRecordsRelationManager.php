<?php

namespace Cesa\Payroll\Filament\Resources\PayrollPeriodResource\RelationManagers;

use Cesa\Payroll\Filament\Resources\PayrollRecordResource;
use Cesa\Payroll\Models\PayrollPeriod;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('payroll::app.resources.payroll_record.model.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('payroll::app.resources.payroll_record.table.columns.employee'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_attendance_days')
                    ->label('Hari Hadir')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_overtime_hours')
                    ->label('Jam Lembur')
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->label(__('payroll::app.resources.payroll_record.table.columns.base_salary'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_penalties')
                    ->label(__('payroll::app.resources.payroll_record.table.columns.late_penalty'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->label(__('payroll::app.resources.payroll_record.table.columns.net_salary'))
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label(__('filament::resources/pages/view_record.title'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn ($record) => PayrollRecordResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
