<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers;

use Filament\Actions\CreateAction;
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
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalWorkflowsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalWorkflows';

    protected static ?string $title = 'Approval Workflows';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('form-transfer::app.relation_managers.approval_workflows');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('division_id')
                ->label(__('form-transfer::app.config.workflows.fields.division'))
                ->relationship(
                    name: 'division',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query
                        ->where('form_transfer_id', $this->getOwnerRecord()->getKey())
                        ->whereNull($query->qualifyColumn('deleted_at')),
                )
                ->searchable()
                ->preload()
                ->helperText(__('form-transfer::app.config.workflows.fields.division_hint'))
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
                        ->required()
                        ->maxLength(191),
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
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()
                    ->modalHeading(__('form-transfer::app.config.workflows.navigation.label')),
            ])
            ->columns([
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
            ])
            ->filters([
                TrashedFilter::make(),
            ]);
    }
}
