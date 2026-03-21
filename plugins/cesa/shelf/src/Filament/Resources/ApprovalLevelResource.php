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
use Webkul\Employee\Models\Employee;

class ApprovalLevelResource extends ShelfResource
{
    protected static ?string $model = ApprovalLevel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

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
                Select::make('approver_employee_id')
                    ->label('Nama / Jabatan Approver')
                    ->relationship(
                        name: 'approverEmployee',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->whereNotNull('user_id')
                            ->where('is_active', true)
                            ->whereNull($query->qualifyColumn('deleted_at'))
                            ->whereHas('user', fn (Builder $userQuery): Builder => $userQuery
                                ->where('is_active', true)
                                ->whereNull($userQuery->qualifyColumn('deleted_at'))),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Employee $record): string => ApprovalLevel::formatApproverOptionLabel($record))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Pilih employee yang terhubung ke user aktif. Level approval dibuat otomatis dari urutan data, lalu bisa digeser dengan tombol naik / turun di tabel.')
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
                TextColumn::make('approver_connection')
                    ->label('Relasi Approver')
                    ->state(fn (ApprovalLevel $record): string => $record->hasActiveApprover() ? 'Aktif' : 'Terputus')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->options(AssetRequest::requestTypeOptions()),
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\Action::make('move_up')
                    ->label('Naik')
                    ->icon('heroicon-m-arrow-up')
                    ->color('gray')
                    ->visible(fn (ApprovalLevel $record): bool => ! $record->trashed() && $record->canMoveUpInTrack())
                    ->action(function (ApprovalLevel $record): void {
                        $record->moveUpInTrack();
                    }),
                \Filament\Actions\Action::make('move_down')
                    ->label('Turun')
                    ->icon('heroicon-m-arrow-down')
                    ->color('gray')
                    ->visible(fn (ApprovalLevel $record): bool => ! $record->trashed() && $record->canMoveDownInTrack())
                    ->action(function (ApprovalLevel $record): void {
                        $record->moveDownInTrack();
                    }),
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
