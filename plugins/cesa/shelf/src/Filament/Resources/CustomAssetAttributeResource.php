<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\CustomAssetAttributeResource\Pages;
use Cesa\Shelf\Models\Category;
use Cesa\Shelf\Models\CustomAssetAttribute;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CustomAssetAttributeResource extends ShelfResource
{
    protected static ?string $model = CustomAssetAttribute::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 50;

    public static function getCategoryOptions(): array
    {
        $categories = Category::with('children')->get();

        $options = [];
        foreach ($categories as $category) {
            if ($category->children->isNotEmpty()) {
                $subcategories = $category->children->pluck('name', 'id')->toArray();
                $options[$category->name] = $subcategories;
            }
        }

        return $options;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        Group::make([
                            // Informasi Dasar
                            Section::make('Informasi Dasar')
                                ->description('Detail utama seputar atribut baru.')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nama Atribut')
                                        ->placeholder('Masukkan nama atribut')
                                        ->required()
                                        ->maxLength(255)
                                        ->prefixIcon('heroicon-m-tag'),

                                    Forms\Components\Select::make('type')
                                        ->label('Tipe Input')
                                        ->required()
                                        ->options([
                                            'text'     => 'Text Input',
                                            'number'   => 'Number Input',
                                            'textarea' => 'Textarea',
                                            'date'     => 'Date Picker',
                                        ])
                                        ->searchable()
                                        ->placeholder('Pilih tipe input')
                                        ->prefixIcon('heroicon-m-cursor-arrow-rays')
                                        ->reactive(),

                                    Forms\Components\Select::make('category_id')
                                        ->label('Kategori')
                                        ->options(self::getCategoryOptions())
                                        ->multiple()
                                        ->searchable()
                                        ->placeholder('Pilih kategori yang relevan')
                                        ->prefixIcon('heroicon-m-folder')
                                        ->afterStateHydrated(function ($state, callable $set) {
                                            if ($state) {
                                                $set('category_id', array_map('intval', $state)); // Konversi ke integer saat dihydrate
                                            }
                                        })
                                        ->columnSpanFull(),
                                ])->columns(2),

                            // Pengaturan Notifikasi
                            Section::make('Pengaturan Notifikasi')
                                ->description('Konfigurasi peringatan terkait tanggal.')
                                ->schema([
                                    Forms\Components\Select::make('notification_type')
                                        ->label('Tipe Notifikasi')
                                        ->options([
                                            'fixed_date'    => 'Fixed Date',
                                            'relative_date' => 'Relative Date',
                                        ])
                                        ->placeholder('Pilih tipe notifikasi')
                                        ->prefixIcon('heroicon-m-bell-alert')
                                        ->required()
                                        ->reactive()
                                        ->visible(fn (callable $get) => $get('is_notifiable')),

                                    // Pengaturan yang akan tampil jika 'relative_date' dipilih
                                    Forms\Components\TextInput::make('notification_offset')
                                        ->label('Offset Notifikasi')
                                        ->placeholder('Masukkan offset notifikasi (dalam hari)')
                                        ->numeric()
                                        ->prefixIcon('heroicon-m-clock')
                                        ->visible(fn (callable $get) => $get('notification_type') === 'relative_date'),

                                    // Pengaturan yang akan tampil jika 'fixed_date' dipilih
                                    Forms\Components\DatePicker::make('fixed_notification_date')
                                        ->label('Tanggal Notifikasi Tetap')
                                        ->placeholder('Pilih tanggal tetap untuk notifikasi')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->visible(fn (callable $get) => $get('notification_type') === 'fixed_date'),
                                ])
                                ->visible(fn (callable $get) => $get('type') === 'date' && $get('is_notifiable'))
                                ->columns(2),

                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 2]),

                        Group::make([
                            // Status Atribut
                            Section::make('Status Atribut')
                                ->schema([
                                    Forms\Components\Toggle::make('required')
                                        ->label('Wajib Diisi')
                                        ->inline(false)
                                        ->default(false),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Aktif')
                                        ->inline(false)
                                        ->default(true),

                                    Forms\Components\Toggle::make('is_notifiable')
                                        ->label('Aktifkan Notifikasi')
                                        ->inline(false)
                                        ->default(false)
                                        ->visible(fn (callable $get) => $get('type') === 'date')
                                        ->reactive(),
                                ])
                                ->columns(3),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 1]),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('required')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category_id')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function ($state) {
                        $categories = Category::whereIn('id', is_array($state) ? $state : [$state])->pluck('name')->toArray();

                        return implode(', ', $categories);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_notifiable')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notification_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notification_offset')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fixed_notification_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Input')
                    ->options([
                        'text'     => 'Text Input',
                        'number'   => 'Number Input',
                        'textarea' => 'Textarea',
                        'date'     => 'Date Picker',
                    ]),
                Tables\Filters\TernaryFilter::make('required')
                    ->label('Wajib Diisi'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),
                Tables\Filters\TernaryFilter::make('is_notifiable')
                    ->label('Notifikasi'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomAssetAttributes::route('/'),
            'create' => Pages\CreateCustomAssetAttribute::route('/create'),
            'edit'   => Pages\EditCustomAssetAttribute::route('/{record}/edit'),
        ];
    }
}
