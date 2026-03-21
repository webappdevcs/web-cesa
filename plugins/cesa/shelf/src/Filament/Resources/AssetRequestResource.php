<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Enums\ApprovalStatus;
use Cesa\Shelf\Enums\RequestStatus;
use Cesa\Shelf\Filament\Resources\AssetRequestResource\Pages;
use Cesa\Shelf\Models\ApprovalLevel;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Services\PublicAssetRequestService;
use Cesa\Shelf\Support\ShelfStorage;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AssetRequestResource extends ShelfResource
{
    protected static ?string $model = AssetRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->options(AssetRequest::requestTypeOptions())
                    ->required()
                    ->native(false),
                TextInput::make('requester_name')
                    ->label('Nama Pemohon')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('division')
                    ->label('Divisi')
                    ->required()
                    ->maxLength(255),
                TextInput::make('placement')
                    ->label('Penempatan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('item_name')
                    ->label('Nama Barang')
                    ->required()
                    ->maxLength(255),
                TextInput::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                FileUpload::make('attachment_path')
                    ->label('Lampiran')
                    ->disk(ShelfStorage::disk())
                    ->directory('shelf/asset-requests/attachments')
                    ->visibility('private')
                    ->downloadable()
                    ->openable()
                    ->fetchFileInformation(false)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/jpg',
                        'image/webp',
                    ])
                    ->maxSize(5120)
                    ->storeFileNamesIn('attachment_original_name')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Status')
                    ->options(RequestStatus::options())
                    ->required()
                    ->default(RequestStatus::Pending->value)
                    ->native(false),
                Select::make('user_id')
                    ->label('Ref. User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('asset_id')
                    ->label('Ref. Asset')
                    ->relationship('asset', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('admin_notes')
                    ->label('Catatan Admin')
                    ->rows(4)
                    ->columnSpanFull(),
                Placeholder::make('attachment_file')
                    ->label('Lampiran Saat Ini')
                    ->visible(fn (?AssetRequest $record): bool => filled($record?->attachment_url))
                    ->content(function (?AssetRequest $record): HtmlString|string {
                        if (! $record?->attachment_url) {
                            return '-';
                        }

                        return new HtmlString(
                            '<a href="'.$record->attachment_url.'" target="_blank" rel="noopener noreferrer">'.
                            e($record->attachment_label).
                            '</a>'
                        );
                    })
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'user:id,name',
                'asset:id,name',
                'approvals',
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::getRequestTypeLabel($state))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('requester_name')
                    ->label('Pemohon')
                    ->searchable(['requester_name', 'email'])
                    ->description(fn (AssetRequest $record): string => $record->email)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->searchable()
                    ->description(fn (AssetRequest $record): string => $record->placement)
                    ->toggleable(),
                TextColumn::make('item_name')
                    ->label('Item')
                    ->searchable()
                    ->description(fn (AssetRequest $record): string => 'Qty: '.$record->qty)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('attachment_original_name')
                    ->label('Lampiran')
                    ->getStateUsing(fn (AssetRequest $record): string => $record->attachment_label ? 'Buka Lampiran' : '-')
                    ->url(fn (AssetRequest $record): ?string => $record->attachment_url, true)
                    ->color(fn (AssetRequest $record): ?string => $record->attachment_url ? 'primary' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (RequestStatus $state): string => $state->label())
                    ->color(fn (RequestStatus $state): string => $state->color())
                    ->description(fn (AssetRequest $record): string => self::getApprovalSummary($record))
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Ref. User')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('asset.name')
                    ->label('Ref. Asset')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->description(fn (AssetRequest $record): ?string => $record->created_at?->diffForHumans())
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->options(AssetRequest::requestTypeOptions()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(RequestStatus::options()),
                TrashedFilter::make(),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->defaultPaginationPageOption(25)
            ->actions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('md')
                    ->mutateDataUsing(function (array $data, AssetRequest $record): array {
                        return self::mutateRequestData($data, $record);
                    })
                    ->after(function (AssetRequest $record): void {
                        self::syncApprovalFlow($record);
                    }),
                ViewAction::make()
                    ->url(fn (AssetRequest $record): string => static::getUrl('view', ['record' => $record])),
                DeleteAction::make()
                    ->visible(fn (AssetRequest $record): bool => ! $record->trashed() && self::canDeleteRecord($record)),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->visible(fn (AssetRequest $record): bool => $record->trashed() && self::canDeleteRecord($record)),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'xl'      => 3,
                ])
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Section::make('Informasi Pengajuan')
                                    ->description('Data pemohon dan detail kebutuhan yang diisi dari form publik.')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'md'      => 2,
                                        ])
                                            ->schema([
                                                TextEntry::make('requester_name')
                                                    ->label('Nama Pemohon')
                                                    ->placeholder('-'),
                                                TextEntry::make('email')
                                                    ->label('Email')
                                                    ->placeholder('-'),
                                                TextEntry::make('division')
                                                    ->label('Divisi')
                                                    ->placeholder('-'),
                                                TextEntry::make('placement')
                                                    ->label('Penempatan')
                                                    ->placeholder('-'),
                                                TextEntry::make('item_name')
                                                    ->label('Nama Barang')
                                                    ->placeholder('-'),
                                                TextEntry::make('qty')
                                                    ->label('Qty')
                                                    ->placeholder('-'),
                                                TextEntry::make('admin_notes')
                                                    ->label('Catatan Proses')
                                                    ->placeholder('-')
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->columns(1),
                                Section::make('Approval')
                                    ->description('Status approval per level.')
                                    ->schema([
                                        RepeatableEntry::make('approvals')
                                            ->label('')
                                            ->grid(1)
                                            ->schema([
                                                Grid::make([
                                                    'default' => 1,
                                                    'md'      => 2,
                                                ])
                                                    ->schema([
                                                        TextEntry::make('status')
                                                            ->label('Status')
                                                            ->badge()
                                                            ->formatStateUsing(fn (ApprovalStatus $state): string => $state->label())
                                                            ->color(fn (ApprovalStatus $state): string => $state->color()),
                                                        TextEntry::make('responded_at')
                                                            ->label('Waktu Respons')
                                                            ->placeholder('Belum ada respons')
                                                            ->dateTime('d M Y H:i'),
                                                        TextEntry::make('approver_name')
                                                            ->label('Penyetuju'),
                                                        TextEntry::make('approver_email')
                                                            ->label('Email')
                                                            ->columnSpan([
                                                                'default' => 1,
                                                                'md'      => 2,
                                                            ])
                                                            ->extraAttributes(['class' => 'break-words']),
                                                        TextEntry::make('notes')
                                                            ->label('Catatan')
                                                            ->placeholder('-')
                                                            ->columnSpanFull(),
                                                    ]),
                                            ])
                                            ->visible(fn (AssetRequest $record): bool => $record->approvals->isNotEmpty()),
                                        TextEntry::make('approval_history_empty')
                                            ->label('Approval')
                                            ->state('Belum ada approval.')
                                            ->visible(fn (AssetRequest $record): bool => $record->approvals->isEmpty()),
                                    ])
                                    ->columns(1),
                            ])
                            ->columnSpan(2),
                        Grid::make(1)
                            ->schema([
                                Section::make('Ringkasan Pengajuan')
                                    ->description('Informasi utama yang paling sering dibutuhkan saat meninjau pengajuan.')
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                TextEntry::make('request_type')
                                                    ->label('Jenis Pengajuan')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state): string => self::getRequestTypeLabel($state)),
                                                TextEntry::make('status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn (RequestStatus $state): string => $state->label())
                                                    ->color(fn (RequestStatus $state): string => $state->color()),
                                            ]),
                                    ])
                                    ->columns(1),
                                Section::make('Informasi Pendukung')
                                    ->description('Lampiran, referensi internal, dan timestamp yang mendukung proses review.')
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                TextEntry::make('attachment_original_name')
                                                    ->label('Lampiran')
                                                    ->getStateUsing(fn (AssetRequest $record): string => $record->attachment_label ?? 'Tidak ada lampiran')
                                                    ->url(fn (AssetRequest $record): ?string => $record->attachment_url)
                                                    ->openUrlInNewTab(),
                                                TextEntry::make('user_reference')
                                                    ->label('Referensi User')
                                                    ->state(fn (AssetRequest $record): string => $record->user
                                                        ? "{$record->user->name} (ID {$record->user->id})"
                                                        : '-'),
                                                TextEntry::make('asset_reference')
                                                    ->label('Referensi Asset')
                                                    ->state(fn (AssetRequest $record): string => $record->asset
                                                        ? "{$record->asset->name} (ID {$record->asset->id})"
                                                        : '-'),
                                            ]),
                                    ])
                                    ->columns(1),
                                Section::make('Data Teknis')
                                    ->description('Identitas sistem dan metadata teknis yang jarang dipakai, tapi tetap tersedia.')
                                    ->schema([
                                        Grid::make([
                                            'default' => 1,
                                            'md'      => 2,
                                            'xl'      => 1,
                                        ])
                                            ->schema([
                                                TextEntry::make('uuid')
                                                    ->label('UUID')
                                                    ->copyable()
                                                    ->copyMessage('UUID disalin')
                                                    ->copyMessageDuration(1500)
                                                    ->extraAttributes(['class' => 'break-all']),
                                                TextEntry::make('created_at')
                                                    ->label('Diajukan')
                                                    ->dateTime('d M Y H:i'),
                                                TextEntry::make('updated_at')
                                                    ->label('Diperbarui')
                                                    ->dateTime('d M Y H:i'),
                                                TextEntry::make('deleted_at')
                                                    ->label('Dihapus')
                                                    ->placeholder('-')
                                                    ->dateTime('d M Y H:i'),
                                                TextEntry::make('attachment_path')
                                                    ->label('Path Lampiran')
                                                    ->placeholder('-')
                                                    ->extraAttributes(['class' => 'break-all'])
                                                    ->columnSpanFull(),
                                            ]),
                                        RepeatableEntry::make('approvals')
                                            ->label('Metadata Approval')
                                            ->grid(1)
                                            ->schema([
                                                Grid::make([
                                                    'default' => 1,
                                                    'md'      => 2,
                                                ])
                                                    ->schema([
                                                        TextEntry::make('level')
                                                            ->label('Level')
                                                            ->badge(),
                                                        TextEntry::make('approval_level_id')
                                                            ->label('Approval Level ID')
                                                            ->placeholder('-'),
                                                        TextEntry::make('created_at')
                                                            ->label('Dibuat')
                                                            ->dateTime('d M Y H:i'),
                                                        TextEntry::make('responded_at')
                                                            ->label('Direspons')
                                                            ->placeholder('Belum direspons')
                                                            ->dateTime('d M Y H:i')
                                                            ->columnSpanFull(),
                                                    ]),
                                            ])
                                            ->visible(fn (AssetRequest $record): bool => $record->approvals->isNotEmpty()),
                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->columns(1),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user:id,name',
                'asset:id,name',
                'approvals.approvalLevel',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAssetRequests::route('/'),
            'view'  => Pages\ViewAssetRequest::route('/{record}'),
        ];
    }

    private static function getRequestTypeLabel(string $state): string
    {
        return AssetRequest::getRequestTypeLabel($state);
    }

    private static function getApprovalSummary(AssetRequest $record): string
    {
        $currentApproval = $record->approvals
            ->first(fn ($approval) => $approval->status === ApprovalStatus::Pending);

        if ($currentApproval) {
            return "Level {$currentApproval->level} - {$currentApproval->approver_name}";
        }

        return $record->approvals->isNotEmpty()
            ? 'Semua approval telah diproses'
            : 'Tanpa approval';
    }

    public static function canDeleteRecord(AssetRequest $record): bool
    {
        return $record->status === RequestStatus::Pending;
    }

    public static function getCreateAction(): CreateAction
    {
        return CreateAction::make()
            ->slideOver()
            ->modalWidth('md')
            ->mutateDataUsing(function (array $data): array {
                return self::mutateRequestData($data);
            })
            ->after(function (AssetRequest $record): void {
                self::syncApprovalFlow($record);
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function mutateRequestData(array $data, ?AssetRequest $record = null): array
    {
        $data['uuid'] = $record?->uuid ?? ($data['uuid'] ?? Str::uuid()->toString());
        $data['approval_track'] = self::resolveApprovalTrack(
            (string) ($data['request_type'] ?? $record?->request_type ?? ''),
            (string) ($data['division'] ?? $record?->division ?? ''),
        );
        $data['status'] = (string) ($data['status'] ?? $record?->status?->value ?? RequestStatus::Pending->value);

        return $data;
    }

    protected static function syncApprovalFlow(AssetRequest $record): void
    {
        $record->refresh();
        app(PublicAssetRequestService::class)->syncApprovalFlow($record);
    }

    protected static function resolveApprovalTrack(string $requestType, string $division): ?string
    {
        $normalizedDivision = ApprovalLevel::normalizeDivision($division);

        if (
            $requestType !== ''
            && ApprovalLevel::query()->forTrack($requestType, $normalizedDivision)->exists()
        ) {
            return $normalizedDivision;
        }

        if (
            $requestType !== ''
            && ApprovalLevel::query()->forTrack($requestType, ApprovalLevel::ALL_DIVISIONS)->exists()
        ) {
            return ApprovalLevel::ALL_DIVISIONS;
        }

        return null;
    }
}
