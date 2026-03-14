<?php

namespace Cesa\Payroll\Filament\Resources;

use Cesa\Payroll\Filament\Resources\PayrollPeriodResource\Pages;
use Cesa\Payroll\Filament\Resources\PayrollPeriodResource\RelationManagers\PayrollRecordsRelationManager;
use Cesa\Payroll\Models\PayrollPeriod;
use Cesa\Payroll\Services\GeneratePayrollService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollPeriodResource extends PayrollResource
{
    protected static ?string $model = PayrollPeriod::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('payroll::app.resources.payroll_period.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('payroll::app.resources.payroll_period.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll::app.resources.payroll_period.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('payroll::app.resources.payroll_period.form.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('payroll::app.resources.payroll_period.form.fields.start_date'))
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('payroll::app.resources.payroll_period.form.fields.end_date'))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('payroll::app.resources.payroll_period.form.fields.status'))
                    ->options([
                        'open'   => __('payroll::app.enums.status.open'),
                        'locked' => __('payroll::app.enums.status.locked'),
                        'paid'   => __('payroll::app.enums.status.paid'),
                    ])
                    ->required()
                    ->default('open')
                    ->hiddenOn('create')
                    ->dehydrated(),
                Forms\Components\Toggle::make('auto_generate')
                    ->label(__('payroll::app.resources.payroll_period.form.fields.auto_generate'))
                    ->helperText(__('payroll::app.resources.payroll_period.form.fields.auto_generate_helper'))
                    ->default(false)
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('payroll::app.resources.payroll_period.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('payroll::app.resources.payroll_period.table.columns.start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('payroll::app.resources.payroll_period.table.columns.end_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('payroll::app.resources.payroll_period.table.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open'   => 'gray',
                        'locked' => 'warning',
                        'paid'   => 'success',
                        default  => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('payroll::app.resources.payroll_period.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn (PayrollPeriod $record): string => static::getUrl('view', ['record' => $record]))
            ->actions([
                Actions\EditAction::make()
                    ->modal()
                    ->slideOver()
                    ->modalWidth('md')
                    ->schema(fn (Schema $schema): Schema => static::form($schema->columns(1))),
                Actions\Action::make('generate_payroll')
                    ->label(__('payroll::app.resources.payroll_period.table.actions.generate_payroll'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Payroll')
                    ->modalDescription('Are you sure? This will calculate payroll only for employees who have attendance or approved overtime data in this period. Existing payroll records for this period will be regenerated using the latest data.')
                    ->visible(fn (PayrollPeriod $record): bool => $record->status === 'open')
                    ->action(function (PayrollPeriod $record, GeneratePayrollService $service) {
                        try {
                            $service->generate($record);

                            Notification::make()
                                ->title(__('payroll::app.notifications.payroll_generated.title'))
                                ->body(__('payroll::app.notifications.payroll_generated.body'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('mark_as_paid')
                    ->label(__('payroll::app.resources.payroll_period.table.actions.mark_as_paid'))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('payroll::app.resources.payroll_period.table.actions.mark_as_paid'))
                    ->modalDescription(__('payroll::app.resources.payroll_period.table.actions.mark_as_paid_description'))
                    ->visible(fn (PayrollPeriod $record): bool => $record->status === 'locked')
                    ->action(function (PayrollPeriod $record) {
                        $record->update(['status' => 'paid']);

                        Notification::make()
                            ->title(__('payroll::app.notifications.marked_as_paid.title'))
                            ->body(__('payroll::app.notifications.marked_as_paid.body'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PayrollRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollPeriods::route('/'),
            'view'  => Pages\ViewPayrollPeriod::route('/{record}'),
        ];
    }
}
