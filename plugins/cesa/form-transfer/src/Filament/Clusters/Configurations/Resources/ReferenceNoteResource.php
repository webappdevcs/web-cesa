<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ReferenceNoteResource\Pages;
use Cesa\FormTransfer\Models\TransferReferenceNote;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferenceNoteResource extends Resource
{
    protected static ?string $model = TransferReferenceNote::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 130;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::app.config.reference_notes.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::app.config.reference_notes.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_transfer_id')
                    ->label(__('form-transfer::app.config.reference_notes.fields.form_transfer'))
                    ->relationship(
                        name: 'formTransfer',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereNull($query->qualifyColumn('deleted_at')),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                TextInput::make('label')
                    ->label(__('form-transfer::app.config.reference_notes.fields.label'))
                    ->maxLength(191)
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('form-transfer::app.config.reference_notes.fields.is_active'))
                    ->default(true),
                Textarea::make('description')
                    ->label(__('form-transfer::app.config.reference_notes.fields.description'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('form-transfer::app.config.reference_notes.columns.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('formTransfer.name')
                    ->label(__('form-transfer::app.config.reference_notes.columns.form_transfer'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::app.config.reference_notes.columns.is_active'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_transfer_id')
                    ->label(__('form-transfer::app.config.reference_notes.filters.form_transfer'))
                    ->relationship(
                        'formTransfer',
                        'name',
                        fn (Builder $query) => $query->whereNull($query->qualifyColumn('deleted_at')),
                    )
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('form-transfer::app.config.reference_notes.filters.is_active')),
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
            'index' => Pages\ListReferenceNotes::route('/'),
        ];
    }
}
