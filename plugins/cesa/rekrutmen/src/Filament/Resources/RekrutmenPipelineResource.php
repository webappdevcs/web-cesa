<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource\Pages;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RekrutmenPipelineResource extends Resource
{
    protected static ?string $model = RekrutmenPipeline::class;

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::app.resources.rekrutmen_pipeline.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::app.resources.rekrutmen_pipeline.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::app.resources.rekrutmen_pipeline.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make(__('rekrutmen::app.resources.rekrutmen_pipeline.form.sections.pipeline_details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('rekrutmen::app.resources.rekrutmen_pipeline.form.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('rekrutmen::app.resources.rekrutmen_pipeline.form.fields.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                \Filament\Schemas\Components\Section::make(__('rekrutmen::app.resources.rekrutmen_pipeline.form.sections.stages'))
                    ->description(__('rekrutmen::app.resources.rekrutmen_pipeline.form.descriptions.stages'))
                    ->schema([
                        Forms\Components\Repeater::make('stages')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('rekrutmen::app.resources.rekrutmen_pipeline.form.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->orderColumn('order_column')
                            ->defaultItems(1)
                            ->addActionLabel(__('rekrutmen::app.resources.rekrutmen_pipeline.form.actions.add_stage'))
                            ->reorderableWithButtons()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('rekrutmen::app.resources.rekrutmen_pipeline.table.columns.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('stages_count')
                    ->counts('stages')
                    ->label(__('rekrutmen::app.resources.rekrutmen_pipeline.table.columns.stages_count'))
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
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
            'index'  => Pages\ListRekrutmenPipelines::route('/'),
            'create' => Pages\CreateRekrutmenPipeline::route('/create'),
            'edit'   => Pages\EditRekrutmenPipeline::route('/{record}/edit'),
        ];
    }
}
