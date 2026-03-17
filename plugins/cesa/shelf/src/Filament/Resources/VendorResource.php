<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\VendorResource\Pages;
use Cesa\Shelf\Models\Vendor;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VendorResource extends ShelfResource
{
    protected static ?string $model = Vendor::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('last_price')
                    ->label(__('shelf::app.labels.item_price'))
                    ->numeric() // Mengatur agar input hanya angka
                    ->prefix('Rp ') // Tambahkan prefix 'Rp ' di depan input
                    ->required()
                    ->placeholder(__('shelf::app.labels.item_price'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_price')
                    ->money('IDR'),
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
                //
            ])
            ->actions([
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVendors::route('/'),
        ];
    }
}
