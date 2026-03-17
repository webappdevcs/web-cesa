<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\ApprovalLevelResource\Pages;
use Cesa\Shelf\Models\ApprovalLevel;
use Cesa\Shelf\Models\AssetRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApprovalLevelResource extends ShelfResource
{
    protected static ?string $model = ApprovalLevel::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?string $navigationLabel = 'Konfigurasi Approval';

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->options(AssetRequest::requestTypeOptions())
                    ->required()
                    ->searchable()
                    ->columnSpanFull(),
                TextInput::make('division')
                    ->label('Divisi')
                    ->maxLength(255)
                    ->placeholder('Contoh: Finance, Operations')
                    ->helperText('Isi sesuai nilai divisi di form asset-requests. Kosongkan jika berlaku untuk semua divisi.')
                    ->afterStateHydrated(function (TextInput $component, ?string $state): void {
                        $component->state($state === ApprovalLevel::ALL_DIVISIONS ? '' : $state);
                    })
                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                    ->columnSpanFull(),
                TextInput::make('level')
                    ->label('Level Approval')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText('Urutan persetujuan (1 = pertama, 2 = kedua, dst.)')
                    ->columnSpanFull(),
                TextInput::make('approver_name')
                    ->label('Nama / Jabatan Approver')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Manager Operasional')
                    ->columnSpanFull(),
                TextInput::make('approver_email')
                    ->label('Email Approver')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('approver@perusahaan.com')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->orderBy('request_type')
                ->orderBy('division')
                ->orderBy('level'))
            ->defaultGroup('request_type')
            ->columns([
                TextColumn::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AssetRequest::getRequestTypeLabel($state)),
                TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === ApprovalLevel::ALL_DIVISIONS ? 'Semua Divisi' : $state)
                    ->searchable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('approver_name')
                    ->label('Nama Approver')
                    ->searchable(),
                TextColumn::make('approver_email')
                    ->label('Email')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->options(AssetRequest::requestTypeOptions()),
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
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
            'index' => Pages\ManageApprovalLevels::route('/'),
        ];
    }
}
