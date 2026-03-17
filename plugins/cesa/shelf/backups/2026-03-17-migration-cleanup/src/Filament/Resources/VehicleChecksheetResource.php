<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Filament\Resources\VehicleChecksheetResource\Pages;
use Cesa\Shelf\Models\VehicleChecksheet;
use Cesa\Shelf\Support\ShelfAttachmentField;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class VehicleChecksheetResource extends ShelfResource
{
    protected static ?string $model = VehicleChecksheet::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Informasi Kendaraan
                Section::make('Informasi Kendaraan')
                    ->schema([
                        // Forms\Components\Select::make('asset_id')
                        //     ->relationship('asset', 'name')
                        //     ->label('Nama Aset'),
                        Forms\Components\TextInput::make('reference_number')
                            ->required()
                            ->maxLength(255)
                            ->label('Nomor Referensi')
                            ->readOnly()
                            ->default(function () {
                                return VehicleChecksheetResource::generateReferenceNumber();
                            }),
                        Forms\Components\Select::make('license_plate')
                            ->label('Plat Nomor')
                            ->options(function () {
                                // Ambil data dari AssetAttribute yang terkait dengan CustomAssetAttribute "Plat Nomor"
                                return \Cesa\Shelf\Models\AssetAttribute::whereHas('customAttribute', function ($query) {
                                    $query->where('name', 'Plat Nomor');
                                })->pluck('attribute_value', 'attribute_value'); // Menggunakan attribute_value sebagai key dan value
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih Plat Nomor'),
                        Forms\Components\TextInput::make('pic')
                            ->maxLength(255)
                            ->label('PIC (Penanggung Jawab)')
                            ->required(),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->label('Lokasi Kendaraan')
                            ->placeholder('Contoh: Depo 1, Workshop, dll.')
                            ->required(),
                        Forms\Components\TextInput::make('destination')
                            ->maxLength(255)
                            ->label('Tujuan')
                            ->placeholder('Contoh: Depo 1, Workshop, dll.')
                            ->required(),
                    ]),

                // Informasi Keberangkatan
                Section::make('Informasi Keberangkatan')
                    ->schema([
                        Forms\Components\TextInput::make('start_km')
                            ->required()
                            ->numeric()
                            ->label('Kilometer Awal')
                            ->placeholder('Masukkan KM awal'),
                        Forms\Components\DateTimePicker::make('departure_time')
                            ->required()
                            ->label('Waktu Keberangkatan')
                            ->default(now()),
                        ShelfAttachmentField::make(
                            'departure_photo',
                            'shelf/vehicle-checksheets/departure-photos',
                            ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
                            4096,
                            false,
                            true,
                        )
                            ->required()
                            ->label('Foto Keberangkatan')
                            ->helperText('Unggah foto keberangkatan kendaraan.'),
                        ShelfAttachmentField::make(
                            'departure_damage_report',
                            'shelf/vehicle-checksheets/departure-damage-reports',
                            ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
                            4096,
                            false,
                            true,
                        )
                            ->required()
                            ->label('Laporan Kerusakan Saat Keberangkatan')
                            ->helperText('Unggah dokumentasi kondisi saat kendaraan berangkat.'),
                    ]),

                // Informasi Pengembalian
                Section::make('Informasi Pengembalian')
                    ->schema([
                        Forms\Components\TextInput::make('end_km')
                            ->required()
                            ->numeric()
                            ->label('Kilometer Akhir')
                            ->placeholder('Masukkan KM akhir'),
                        Forms\Components\DateTimePicker::make('return_time')
                            ->required()
                            ->label('Waktu Pengembalian'),
                        ShelfAttachmentField::make(
                            'return_photo',
                            'shelf/vehicle-checksheets/return-photos',
                            ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
                            4096,
                            false,
                            true,
                        )
                            ->required()
                            ->label('Foto Pengembalian')
                            ->helperText('Unggah foto kendaraan saat pengembalian.'),
                        ShelfAttachmentField::make(
                            'return_damage_report',
                            'shelf/vehicle-checksheets/return-damage-reports',
                            ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
                            4096,
                            false,
                            true,
                        )
                            ->required()
                            ->label('Laporan Kerusakan Saat Pengembalian')
                            ->helperText('Unggah dokumentasi kondisi saat kendaraan kembali.'),
                    ])
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                // Informasi Tambahan
                Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\TextInput::make('rental_duration')
                            ->numeric()
                            ->label('Durasi Sewa (jam)')
                            ->disabled(), // Set as read-only
                        Forms\Components\TextInput::make('distance_traveled')
                            ->numeric()
                            ->default(0.00)
                            ->label('Jarak Tempuh')
                            ->disabled(), // Set as read-only
                        Forms\Components\Textarea::make('remarks')
                            ->columnSpanFull()
                            ->label('Catatan Tambahan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->label('Nomor Referensi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pic')
                    ->searchable()
                    ->label('PIC')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('license_plate')
                    ->searchable()
                    ->label('Plat Nomor')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->label('Lokasi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('destination')
                    ->searchable()
                    ->label('Tujuan')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_km')
                    ->numeric()
                    ->label('Kilometer Awal')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('departure_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Waktu Keberangkatan')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('departure_photo')
                    ->getStateUsing(fn (VehicleChecksheet $record): ?string => $record->managedFileUrl('departure_photo'))
                    ->checkFileExistence(false)
                    ->label('Foto Keberangkatan')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('departure_damage_report')
                    ->getStateUsing(fn (VehicleChecksheet $record): ?string => $record->managedFileUrl('departure_damage_report'))
                    ->checkFileExistence(false)
                    ->label('Laporan Kerusakan Keberangkatan')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_km')
                    ->numeric()
                    ->label('Kilometer Akhir')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('return_time')
                    ->dateTime()
                    ->label('Waktu Kembali')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('return_photo')
                    ->getStateUsing(fn (VehicleChecksheet $record): ?string => $record->managedFileUrl('return_photo'))
                    ->checkFileExistence(false)
                    ->label('Foto Kembali')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('return_damage_report')
                    ->getStateUsing(fn (VehicleChecksheet $record): ?string => $record->managedFileUrl('return_damage_report'))
                    ->checkFileExistence(false)
                    ->label('Laporan Kerusakan Kembali')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rental_duration')
                    ->numeric()
                    ->label('Durasi Sewa')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('distance_traveled')
                    ->numeric()
                    ->label('Jarak Tempuh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Tanggal Dibuat'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Tanggal Diperbarui'),
            ])
            ->filters([
                SelectFilter::make('license_plate')
                    ->label('Plat Nomor')
                    ->options(fn () => VehicleChecksheet::query()
                        ->orderBy('license_plate')
                        ->distinct()
                        ->pluck('license_plate', 'license_plate')
                        ->all()),
                SelectFilter::make('pic')
                    ->label('PIC')
                    ->options(fn () => VehicleChecksheet::query()
                        ->orderBy('pic')
                        ->distinct()
                        ->pluck('pic', 'pic')
                        ->all()),
                SelectFilter::make('location')
                    ->label('Lokasi')
                    ->options(fn () => VehicleChecksheet::query()
                        ->orderBy('location')
                        ->distinct()
                        ->pluck('location', 'location')
                        ->all()),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }

    public static function generateReferenceNumber(bool $lockForUpdate = false): string
    {
        $year = date('Y');

        $latestRecordQuery = VehicleChecksheet::query()
            ->whereYear('created_at', $year)
            ->orderByRaw('CAST(SUBSTRING_INDEX(reference_number, "-", -1) AS UNSIGNED) DESC');

        if ($lockForUpdate) {
            $latestRecordQuery->lockForUpdate();
        }

        $latestRecord = $latestRecordQuery->first();

        if ($latestRecord) {
            $lastNumber = (int) Str::afterLast($latestRecord->reference_number, '-');
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "GA-{$year}-{$newNumber}";
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
            'index'  => Pages\ListVehicleChecksheets::route('/'),
            'create' => Pages\CreateVehicleChecksheet::route('/create'),
            'edit'   => Pages\EditVehicleChecksheet::route('/{record}/edit'),
        ];
    }
}
