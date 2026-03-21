<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ApprovalWorkflowResource\Pages;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferDivision;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalWorkflowResource extends Resource
{
    protected static ?string $model = TransferApprovalWorkflow::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?int $navigationSort = 120;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::app.config.workflows.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::app.config.workflows.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('form_transfer_id')
                ->label(__('form-transfer::app.config.workflows.fields.form_transfer'))
                ->relationship(
                    name: 'formTransfer',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->whereNull($query->qualifyColumn('deleted_at')),
                )
                ->required()
                ->live()
                ->searchable()
                ->preload()
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    $set('division_id', null);
                })
                ->columnSpanFull(),
            Select::make('division_id')
                ->label(__('form-transfer::app.config.workflows.fields.division'))
                ->options(function (Get $get): array {
                    $formTransferId = $get('form_transfer_id');

                    if (! $formTransferId) {
                        return [];
                    }

                    return TransferDivision::query()
                        ->where('form_transfer_id', $formTransferId)
                        ->whereNull('deleted_at')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->searchable()
                ->preload()
                ->helperText(__('form-transfer::app.config.workflows.fields.division_hint'))
                ->disabled(fn (Get $get): bool => ! $get('form_transfer_id'))
                ->native(false)
                ->columnSpanFull(),
            Textarea::make('description')
                ->label(__('form-transfer::app.config.workflows.fields.description'))
                ->rows(3)
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label(__('form-transfer::app.config.workflows.fields.is_active'))
                ->default(true)
                ->columnSpanFull(),
            Repeater::make('steps')
                ->label(__('form-transfer::app.config.workflows.fields.steps'))
                ->columns(2)
                ->default([])
                ->schema([
                    TextInput::make('label')
                        ->label(__('form-transfer::app.config.workflows.fields.step_label'))
                        ->maxLength(191)
                        ->required(),
                    TextInput::make('default_name')
                        ->label(__('form-transfer::app.config.workflows.fields.step_default_name'))
                        ->maxLength(191),
                    TextInput::make('default_email')
                        ->label(__('form-transfer::app.config.workflows.fields.step_default_email'))
                        ->email()
                        ->maxLength(191),
                    TextInput::make('default_title')
                        ->label(__('form-transfer::app.config.workflows.fields.step_default_title'))
                        ->maxLength(191),
                    TextInput::make('default_phone')
                        ->label(__('form-transfer::app.config.workflows.fields.step_default_phone'))
                        ->maxLength(30),
                    Toggle::make('is_mandatory')
                        ->label(__('form-transfer::app.config.workflows.fields.step_is_mandatory'))
                        ->default(true),
                ])
                ->addActionLabel(__('form-transfer::app.config.workflows.actions.add_step'))
                ->reorderable()
                ->columnSpanFull()
                ->minItems(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('formTransfer.name')
                    ->label(__('form-transfer::app.config.workflows.columns.form_transfer'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('division.name')
                    ->label(__('form-transfer::app.config.workflows.columns.division'))
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('step_count')
                    ->label(__('form-transfer::app.config.workflows.columns.steps'))
                    ->sortable(),
                TextColumn::make('step_summary')
                    ->label(__('form-transfer::app.config.workflows.columns.step_summary'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::app.config.workflows.columns.is_active'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_transfer_id')
                    ->label(__('form-transfer::app.config.workflows.filters.form_transfer'))
                    ->relationship(
                        'formTransfer',
                        'name',
                        fn (Builder $query) => $query->whereNull($query->qualifyColumn('deleted_at')),
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('division_id')
                    ->label(__('form-transfer::app.config.workflows.filters.division'))
                    ->relationship(
                        'division',
                        'name',
                        fn (Builder $query) => $query->whereNull($query->qualifyColumn('deleted_at')),
                    )
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('form-transfer::app.config.workflows.filters.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovalWorkflows::route('/'),
        ];
    }
}
