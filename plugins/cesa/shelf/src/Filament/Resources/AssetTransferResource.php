<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Enums\AssetCondition;
use Cesa\Shelf\Filament\Resources\AssetTransferResource\Pages;
use Cesa\Shelf\Models\Asset;
use Cesa\Shelf\Models\AssetTransfer;
use Cesa\Shelf\Models\CompanyDocumentSetting;
use Cesa\Shelf\Models\User;
use Cesa\Shelf\Support\ShelfAttachmentField;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Section as ComponentSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Webkul\Support\Models\Company;

class AssetTransferResource extends ShelfResource
{
    protected static ?string $model = AssetTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    /**
     * @return array<int, string>
     */
    protected static function userOptions(?int $exceptUserId = null): array
    {
        $supportsJobTitles = User::supportsJobTitles();

        $query = User::selectableQuery($exceptUserId);

        if ($supportsJobTitles) {
            $query->with('jobTitle');
        }

        return $query
            ->get()
            ->mapWithKeys(function (User $user) use ($supportsJobTitles): array {
                $jobTitle = $supportsJobTitles
                    ? ($user->jobTitle?->title ?? 'Tanpa Jabatan')
                    : 'Tanpa Jabatan';

                return [$user->id => "{$user->name} - {$jobTitle}"];
            })
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        $user = Auth::user();
        $canEditTransfer = $user?->can('update_shelf_asset::transfer') ?? false;

        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('letter_number')
                                    ->translateLabel()
                                    ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer)
                                    ->extraInputAttributes(['readonly' => true]),
                                Select::make('company_id')
                                    ->label('Badan Usaha')
                                    ->options(fn () => Cache::remember('asset_transfer_company_options', 300, fn () => Company::orderBy('name')->pluck('name', 'id')))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set(
                                        'letter_number',
                                        self::generateLetterNumber(Company::withTrashed()->find($state))
                                    )),
                                Select::make('from_user_id')
                                    ->relationship('fromUser', 'name', modifyQueryUsing: fn (Builder $query): Builder => User::applySelectableScope($query))
                                    ->required()
                                    ->translateLabel()
                                    ->reactive()
                                    ->searchable()
                                    ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer)
                                    ->options(fn (): array => self::userOptions())
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $set('to_user_id', null);
                                        $set('details', null);
                                        self::syncTransferDetails($set, $get);
                                    }),
                                Select::make('to_user_id')
                                    ->translateLabel()
                                    ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer)
                                    ->options(fn (callable $get): array => self::userOptions(
                                        filled($get('from_user_id')) ? (int) $get('from_user_id') : null
                                    ))
                                    ->helperText('Pengguna baru dikelola dari menu user inti CESA.')
                                    ->searchable()
                                    ->required(),
                                Select::make('transfer_type')
                                    ->label('Jenis Dokumen')
                                    ->options(AssetTransfer::transferTypeOptions())
                                    ->required()
                                    ->native(false)
                                    ->helperText('Pilih konteks dokumen transfer. Jenis transfer menjadi sumber penentuan alur aset.')
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get): void {
                                        self::syncTransferDetails($set, $get);
                                    })
                                    ->afterStateHydrated(function (Select $component, mixed $state, ?AssetTransfer $record): void {
                                        if (filled($state)) {
                                            return;
                                        }

                                        $component->state($record?->transfer_type ?? AssetTransfer::TYPE_HANDOVER);
                                    }),
                                DatePicker::make('transfer_date')
                                    ->native(false)
                                    ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer)
                                    ->required(),
                            ])
                            ->columnSpan(1),
                        ShelfAttachmentField::make(
                            'document',
                            'shelf/asset-transfers/documents',
                            [
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/jpg',
                            ],
                            5120,
                        )
                            ->columnSpan(1)
                            ->hidden(fn ($context) => $context === 'create'),
                    ])
                    ->columns(1)
                    ->columnSpan(1),
                Repeater::make('details')
                    ->relationship('details')
                    ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer)
                    ->schema([
                        Select::make('asset_id')
                            ->reactive()
                            ->required()
                            ->translateLabel()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer)
                            ->options(function (callable $get) {
                                $fromUserId = $get('../../from_user_id');
                                $transferType = $get('../../transfer_type');
                                $selectedAssets = collect($get('../../details'))->pluck('asset_id')->filter()->all();
                                $query = self::availableAssetsQuery($fromUserId ? (int) $fromUserId : null, $transferType);

                                if (! empty($selectedAssets)) {
                                    $query->whereNotIn('id', $selectedAssets);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                return Asset::withTrashed()->find($value)?->name;
                            }),
                        TextInput::make('equipment')
                            ->translateLabel()
                            ->disabled(fn ($context) => $context === 'edit' && ! $canEditTransfer),
                    ])
                    ->translateLabel()
                    ->required()
                    ->hidden(fn (callable $get) => ! $get('from_user_id'))
                    ->columns(2)
                    ->columnSpan(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder => $query->with([
                'company',
                'fromUser',
                'toUser',
            ]))
            ->columns([
                TextColumn::make('company.name')
                    ->label('Badan Usaha')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary'   => AssetTransfer::STATUS_HANDOVER,
                        'success'   => AssetTransfer::STATUS_REASSIGNMENT,
                        'danger'    => AssetTransfer::STATUS_RETURN,
                        'secondary' => AssetTransfer::STATUS_UNKNOWN,
                    ])
                    ->getStateUsing(function ($record) {
                        return $record->status;
                    })
                    ->toggleable(),
                TextColumn::make('letter_number')
                    ->translateLabel()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('fromUser.name')
                    ->translateLabel()
                    ->badge()
                    ->color('danger')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('toUser.name')
                    ->translateLabel()
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('transfer_date')->translateLabel()->date()->toggleable(),
                TextColumn::make('document')
                    ->url(fn (AssetTransfer $record): ?string => $record->managedFileUrl('document'), true)
                    ->openUrlInNewTab()
                    ->translateLabel()
                    ->getStateUsing(fn (AssetTransfer $record): string => $record->document ? 'Dokumen' : '-')
                    ->icon('heroicon-o-document-text')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('company')->relationship('company', 'name')->label('Badan Usaha'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AssetTransfer::statusOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->statusLabel($data['value'] ?? null)),
                SelectFilter::make('fromUser')
                    ->relationship('fromUser', 'name', modifyQueryUsing: fn (Builder $query): Builder => User::applySelectableScope($query))
                    ->label('Dari Pengguna')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('toUser')
                    ->relationship('toUser', 'name', modifyQueryUsing: fn (Builder $query): Builder => User::applySelectableScope($query))
                    ->label('Ke Pengguna')
                    ->searchable()
                    ->preload(),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->actions([
                Action::make('download')
                    ->label('Template')
                    ->url(fn (AssetTransfer $record): string => route('asset-transfer.download', $record))
                    ->visible(fn (AssetTransfer $record): bool => $record->document === null)
                    ->color('success'),
                ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
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

    protected static function syncTransferDetails(callable $set, callable $get): void
    {
        $fromUserId = filled($get('from_user_id')) ? (int) $get('from_user_id') : null;
        $transferType = $get('transfer_type');

        if ($fromUserId === null || ! filled($transferType)) {
            $set('details', null);

            return;
        }

        $details = self::availableAssetsQuery($fromUserId, $transferType)
            ->pluck('id')
            ->map(fn (int $assetId): array => ['asset_id' => $assetId, 'equipment' => ''])
            ->values()
            ->all();

        $set('details', $details);
    }

    protected static function availableAssetsQuery(?int $fromUserId, ?string $transferType): Builder
    {
        $query = Asset::query()
            ->select(['id', 'name'])
            ->orderBy('name');

        if ($fromUserId === null || ! filled($transferType)) {
            return $query->whereRaw('1 = 0');
        }

        if ($transferType === AssetTransfer::TYPE_HANDOVER) {
            return $query->where('condition_status', AssetCondition::Available->value);
        }

        return $query
            ->where('recipient_id', $fromUserId)
            ->whereNotIn('condition_status', [
                AssetCondition::Lost->value,
                AssetCondition::Damaged->value,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAssetTransfers::route('/'),
            'create' => Pages\CreateAssetTransfer::route('/create'),
            'edit'   => Pages\EditAssetTransfer::route('/{record}/edit'),
            'view'   => Pages\ViewAssetTransfer::route('/{record}'),
        ];
    }

    public static function generateLetterNumber(?Company $company, bool $lockForUpdate = false): string
    {
        if (! $company) {
            return '';
        }

        $format = CompanyDocumentSetting::resolveFormat(
            $company,
            CompanyDocumentSetting::query()->withTrashed()->where('company_id', $company->id)->first(),
        );

        $lastTransferQuery = AssetTransfer::query()
            ->where('company_id', $company->id)
            ->latest('id');

        if ($lockForUpdate) {
            $lastTransferQuery->lockForUpdate();
        }

        $lastTransfer = $lastTransferQuery->first();
        $lastNumber = $lastTransfer ? (int) preg_replace('/\D/', '', substr($lastTransfer->letter_number, -6)) : 0;
        $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

        return "{$format}{$newNumber}";
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ComponentsGrid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        Group::make([
                            ComponentSection::make('Informasi Transfer Aset')
                                ->schema([
                                    ComponentsGrid::make(2)
                                        ->schema([
                                            TextEntry::make('letter_number')
                                                ->label('Nomor Surat')
                                                ->extraAttributes(['class' => 'text-lg font-bold text-primary-600 dark:text-primary-400'])
                                                ->icon('heroicon-m-document-text')
                                                ->columnSpan(2),
                                            ComponentsGrid::make(1)
                                                ->schema([
                                                    TextEntry::make('fromUser.name')
                                                        ->label('Dari Pengguna')
                                                        ->icon('heroicon-m-arrow-right-circle')
                                                        ->color('danger'),
                                                    TextEntry::make('toUser.name')
                                                        ->label('Ke Pengguna')
                                                        ->icon('heroicon-m-arrow-down-circle')
                                                        ->color('success'),
                                                ])->columnSpan(1),
                                            ComponentsGrid::make(1)
                                                ->schema([
                                                    TextEntry::make('transfer_date')
                                                        ->label('Tanggal Transfer')
                                                        ->date()
                                                        ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d M Y'))
                                                        ->icon('heroicon-m-calendar-days'),
                                                    TextEntry::make('company.name')
                                                        ->label('Badan Usaha')
                                                        ->icon('heroicon-m-building-office-2'),
                                                ])->columnSpan(1),
                                        ]),
                                ]),

                            ComponentSection::make('Detail Aset yang Ditransfer')
                                ->schema([
                                    RepeatableEntry::make('details')
                                        ->hiddenLabel()
                                        ->schema([
                                            ComponentsGrid::make(2)
                                                ->schema([
                                                    TextEntry::make('asset.name')
                                                        ->label('Nama Aset')
                                                        ->icon('heroicon-m-cube')
                                                        ->extraAttributes(['class' => 'font-semibold text-gray-800 dark:text-gray-200']),
                                                    TextEntry::make('equipment')
                                                        ->label('Keterangan Peralatan')
                                                        ->icon('heroicon-m-information-circle'),
                                                ]),
                                        ])
                                        ->columns(1),
                                ])
                                ->collapsible(),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 2]),

                        Group::make([
                            ComponentSection::make('Status & Dokumen')
                                ->schema([
                                    TextEntry::make('status')
                                        ->label('Status Transfer')
                                        ->badge()
                                        ->colors([
                                            'primary'   => AssetTransfer::STATUS_HANDOVER,
                                            'success'   => AssetTransfer::STATUS_REASSIGNMENT,
                                            'danger'    => AssetTransfer::STATUS_RETURN,
                                            'secondary' => AssetTransfer::STATUS_UNKNOWN,
                                        ]),
                                    TextEntry::make('document')
                                        ->label('Dokumen Lampiran')
                                        ->url(fn (AssetTransfer $record): ?string => $record->managedFileUrl('document'), true)
                                        ->openUrlInNewTab()
                                        ->icon('heroicon-m-document-arrow-down')
                                        ->getStateUsing(fn (AssetTransfer $record): string => $record->document ? 'Unduh Dokumen' : 'Tidak Ada Dokumen')
                                        ->extraAttributes(['class' => 'mt-4 text-primary-600 hover:underline']),
                                ])->columns(1),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 1]),
                    ]),
            ])
            ->columns(1);
    }
}
