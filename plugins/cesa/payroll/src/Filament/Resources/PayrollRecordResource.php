<?php

namespace Cesa\Payroll\Filament\Resources;

use Cesa\Payroll\Filament\Resources\PayrollRecordResource\Pages;
use Cesa\Payroll\Models\PayrollRecord;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollRecordResource extends PayrollResource
{
    protected static ?string $model = PayrollRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.user_id'))
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\Select::make('payroll_period_id')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.payroll_period_id'))
                    ->relationship('period', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('total_attendance_days')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.total_attendance_days'))
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('total_overtime_hours')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.total_overtime_hours'))
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('total_late_minutes')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.total_late_minutes'))
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('gross_salary')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.gross_salary'))
                    ->numeric()
                    ->prefix('IDR')
                    ->disabled(),
                Forms\Components\TextInput::make('total_penalties')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.total_penalties'))
                    ->numeric()
                    ->prefix('IDR')
                    ->disabled(),
                Forms\Components\TextInput::make('net_salary')
                    ->label(__('payroll::app.resources.payroll_record.form.fields.net_salary'))
                    ->numeric()
                    ->prefix('IDR')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_penalties')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payroll_period_id')
                    ->relationship('period', 'name')
                    ->label('Period'),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payroll::app.resources.payroll_record.infolist.sections.record_details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.employee')),
                        Infolists\Components\TextEntry::make('period.name')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.period')),
                        Infolists\Components\TextEntry::make('total_attendance_days')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.attendance_days')),
                        Infolists\Components\TextEntry::make('total_overtime_hours')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.overtime_hours')),
                        Infolists\Components\TextEntry::make('total_late_minutes')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.late_minutes')),
                    ])->columns(2),
                Section::make(__('payroll::app.resources.payroll_record.infolist.sections.financials'))
                    ->schema([
                        Infolists\Components\TextEntry::make('gross_salary')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.gross_salary'))
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('total_penalties')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.total_penalties'))
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('net_salary')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.net_salary'))
                            ->money('IDR')
                            ->weight('bold')
                            ->color('success'),
                    ])->columns(3),
                Section::make(__('payroll::app.resources.payroll_record.infolist.sections.calculation_details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('details.daily_wage')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.daily_wage'))
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('details.overtime_rate')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.overtime_rate'))
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('details.basic_salary')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.basic_salary'))
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('details.overtime_salary')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.overtime_salary'))
                            ->money('IDR'),
                    ])->columns(2),
                Section::make(__('payroll::app.resources.payroll_record.infolist.sections.penalties_breakdown'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details.penalties_breakdown')
                            ->label(__('payroll::app.resources.payroll_record.infolist.entries.late_penalties'))
                            ->schema([
                                Infolists\Components\TextEntry::make('date')
                                    ->label(__('payroll::app.resources.payroll_record.infolist.entries.date'))
                                    ->date(),
                                Infolists\Components\TextEntry::make('minutes_late')
                                    ->label(__('payroll::app.resources.payroll_record.infolist.entries.minutes_late')),
                                Infolists\Components\TextEntry::make('penalty_amount')
                                    ->label(__('payroll::app.resources.payroll_record.infolist.entries.penalty_amount'))
                                    ->money('IDR'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn ($record) => !empty($record->details['penalties_breakdown'])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollRecords::route('/'),
            'view'  => Pages\ViewPayrollRecord::route('/{record}'),
        ];
    }
}
