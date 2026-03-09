<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Enums\RequestManPowerStatus;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages;
use Cesa\Rekrutmen\Models\RequestManPower;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RequestManPowerResource extends Resource
{
    protected static ?string $model = RequestManPower::class;

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::app.resources.request_man_power.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::app.resources.request_man_power.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::app.resources.request_man_power.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('rekrutmen::app.resources.request_man_power.form.sections.applicant_information'))
                    ->schema([
                        TextInput::make('nama_pengaju')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.nama_pengaju'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('posisi_pengaju')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.posisi_pengaju'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email_address')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.email_address'))
                            ->email()
                            ->nullable()
                            ->maxLength(255),
                        DatePicker::make('tanggal_pengajuan')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.tanggal_pengajuan'))
                            ->required()
                            ->default(now()),
                        TextInput::make('divisi')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.divisi'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('badan_usaha')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.badan_usaha'))
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make(__('rekrutmen::app.resources.request_man_power.form.sections.requirement_details'))
                    ->schema([
                        TextInput::make('posisi_dibutuhkan')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.posisi_dibutuhkan'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('lokasi_penempatan')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.lokasi_penempatan'))
                            ->required()
                            ->maxLength(255),
                        Select::make('status_kebutuhan')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.status_kebutuhan'))
                            ->required()
                            ->options(StatusKebutuhan::class)
                            ->default(StatusKebutuhan::NEW_HIRING)
                            ->live(),
                        Select::make('level_pekerjaan')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.level_pekerjaan'))
                            ->required()
                            ->options(RequestManPower::getTranslatedLevelPekerjaanOptions()),
                        TextInput::make('jumlah_karyawan_dibutuhkan')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.jumlah_karyawan_dibutuhkan'))
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        DatePicker::make('estimasi_tanggal_join')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.estimasi_tanggal_join'))
                            ->required(),
                        TextInput::make('nama_karyawan_replacement')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.nama_karyawan_replacement'))
                            ->maxLength(255)
                            ->nullable()
                            ->required(fn (callable $get) => self::isReplacementStatus($get('status_kebutuhan')))
                            ->helperText(__('rekrutmen::app.resources.request_man_power.form.helper_texts.nama_karyawan_replacement'))
                            ->visible(fn (callable $get) => self::isReplacementStatus($get('status_kebutuhan'))),
                    ])->columns(2),

                Section::make(__('rekrutmen::app.resources.request_man_power.form.sections.qualifications'))
                    ->schema([
                        RichEditor::make('requirements_kualifikasi')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.requirements_kualifikasi'))
                            ->required()
                            ->columnSpanFull(),
                        RichEditor::make('job_description')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.job_description'))
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('keterangan')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.keterangan'))
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('rekrutmen::app.resources.request_man_power.form.sections.approval_status'))
                    ->schema([
                        Select::make('status')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.status'))
                            ->required()
                            ->options(RequestManPowerStatus::class)
                            ->default(RequestManPowerStatus::PENDING)
                            ->disabled(fn () => ! self::currentUserCanManageApproval()),
                        Select::make('approved_by')
                            ->relationship('approver', 'name')
                            ->label(__('rekrutmen::app.resources.request_man_power.form.fields.approved_by'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && $record->approved_by),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pengaju')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.nama_pengaju'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posisi_dibutuhkan')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.posisi_dibutuhkan'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('divisi')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.divisi'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_kebutuhan')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.status_kebutuhan'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_karyawan_replacement')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.nama_karyawan_replacement'))
                    ->placeholder(__('rekrutmen::app.resources.request_man_power.table.placeholders.nama_karyawan_replacement'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('jumlah_karyawan_dibutuhkan')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.jumlah_karyawan_dibutuhkan'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.tanggal_pengajuan'))
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.columns.status'))
                    ->badge()
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.filters.status'))
                    ->options(RequestManPowerStatus::class),
                Tables\Filters\SelectFilter::make('status_kebutuhan')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.filters.status_kebutuhan'))
                    ->options(StatusKebutuhan::class),
                Tables\Filters\SelectFilter::make('divisi')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.filters.divisi'))
                    ->options(fn () => RequestManPower::query()->distinct()->pluck('divisi', 'divisi')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RequestManPower $record) => self::canApproveOrReject($record))
                    ->action(function (RequestManPower $record) {
                        $record->approveBy(Auth::id());
                    }),
                Action::make('reject')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RequestManPower $record) => self::canApproveOrReject($record))
                    ->requiresConfirmation()
                    ->action(function (RequestManPower $record) {
                        $previousStatus = $record->status;

                        $record->update([
                            'status'      => RequestManPowerStatus::REJECTED,
                            'approved_by' => Auth::id(),
                        ]);

                        $record->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::REJECTED);
                    }),
                Action::make('set_pending')
                    ->label(__('rekrutmen::app.resources.request_man_power.table.actions.set_pending'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (RequestManPower $record) => self::canSetPending($record))
                    ->requiresConfirmation()
                    ->action(function (RequestManPower $record) {
                        $previousStatus = $record->status;

                        $record->update([
                            'status'      => RequestManPowerStatus::PENDING,
                            'approved_by' => null,
                        ]);

                        $record->sendStatusChangedNotification($previousStatus, RequestManPowerStatus::PENDING);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index'  => Pages\ListRequestManPowers::route('/'),
            'create' => Pages\CreateRequestManPower::route('/create'),
            'edit'   => Pages\EditRequestManPower::route('/{record}/edit'),
        ];
    }

    protected static function isReplacementStatus(mixed $statusKebutuhan): bool
    {
        if ($statusKebutuhan instanceof StatusKebutuhan) {
            return $statusKebutuhan === StatusKebutuhan::REPLACEMENT;
        }

        if (! is_string($statusKebutuhan)) {
            return false;
        }

        return in_array($statusKebutuhan, [StatusKebutuhan::REPLACEMENT->value, StatusKebutuhan::REPLACEMENT->name], true);
    }

    public static function canApproveOrReject(RequestManPower $record): bool
    {
        return self::statusIsPending($record->status) && self::currentUserCanManageApproval($record);
    }

    public static function canSetPending(RequestManPower $record): bool
    {
        return ! self::statusIsPending($record->status) && self::currentUserCanManageApproval($record);
    }

    protected static function statusIsPending(mixed $status): bool
    {
        if ($status instanceof RequestManPowerStatus) {
            return $status === RequestManPowerStatus::PENDING;
        }

        if (! is_string($status)) {
            return false;
        }

        return strtolower($status) === RequestManPowerStatus::PENDING->value;
    }

    protected static function currentUserCanManageApproval(?RequestManPower $record = null): bool
    {
        $user = Auth::user();

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        if ($record) {
            return $user->can('update', $record);
        }

        return $user->can('update_rekrutmen_request::man::power');
    }
}
