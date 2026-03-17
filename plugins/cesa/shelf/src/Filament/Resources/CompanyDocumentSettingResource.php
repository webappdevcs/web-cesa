<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\CompanyDocumentSettingResource\Pages;
use Cesa\Shelf\Models\CompanyDocumentSetting;
use Cesa\Shelf\Support\ShelfAttachmentField;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Support\Models\Company;

class CompanyDocumentSettingResource extends ShelfResource
{
    protected static ?string $model = CompanyDocumentSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Company')
                    ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('format')
                    ->label('Format')
                    ->maxLength(50)
                    ->placeholder('Contoh: CSN-')
                    ->columnSpanFull(),
                ColorPicker::make('color')
                    ->label('Color')
                    ->hexColor()
                    ->columnSpanFull(),
                ShelfAttachmentField::make(
                    'letterhead_path',
                    'shelf/letterheads',
                    ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
                    5120,
                    false,
                    true,
                )
                    ->label('Letterhead')
                    ->helperText('Gunakan file gambar agar konsisten dengan template dokumen Shelf.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('format')
                    ->label('Format')
                    ->placeholder('-')
                    ->searchable(),
                ColorColumn::make('color')
                    ->label('Color'),
                ImageColumn::make('letterhead_path')
                    ->label('Letterhead')
                    ->getStateUsing(fn (CompanyDocumentSetting $record): ?string => $record->managedFileUrl('letterhead_path'))
                    ->checkFileExistence(false)
                    ->square(),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('md'),
                \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\RestoreAction::make(),
                \Filament\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanyDocumentSettings::route('/'),
        ];
    }
}
