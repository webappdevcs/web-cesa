<?php

namespace Cesa\Presensi\Filament\Resources;

use Cesa\Presensi\Filament\Resources\AttendanceResource\Pages;
use Cesa\Presensi\Models\Attendance;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceResource extends PresensiResource
{
    protected static ?string $model = Attendance::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('presensi::app.resources.attendance.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('presensi::app.resources.attendance.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('presensi::app.resources.attendance.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        // Left Column
                        Group::make([
                            Section::make('Informasi Kehadiran')
                                ->description('Data utama presensi pegawai.')
                                ->schema([
                                    Forms\Components\Select::make('user_id')
                                        ->label(__('presensi::app.resources.attendance.form.fields.user_id'))
                                        ->relationship('user', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Waktu Mulai')
                                ->schema([
                                    Forms\Components\TimePicker::make('start_time')
                                        ->label(__('presensi::app.resources.attendance.form.fields.start_time'))
                                        ->required()
                                        ->seconds(false),
                                    Forms\Components\TextInput::make('start_latitude')
                                        ->label(__('presensi::app.resources.attendance.form.fields.start_latitude'))
                                        ->required()
                                        ->numeric()
                                        ->step(0.0000001),
                                    Forms\Components\TextInput::make('start_longitude')
                                        ->label(__('presensi::app.resources.attendance.form.fields.start_longitude'))
                                        ->required()
                                        ->numeric()
                                        ->step(0.0000001),
                                    Forms\Components\FileUpload::make('start_photo_path')
                                        ->label(__('presensi::app.resources.attendance.form.fields.start_photo_path'))
                                        ->image()
                                        ->directory('presensi/attendances/start')
                                        ->nullable()
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Section::make('Waktu Selesai')
                                ->schema([
                                    Forms\Components\TimePicker::make('end_time')
                                        ->label(__('presensi::app.resources.attendance.form.fields.end_time'))
                                        ->nullable()
                                        ->seconds(false),
                                    Forms\Components\TextInput::make('end_latitude')
                                        ->label(__('presensi::app.resources.attendance.form.fields.end_latitude'))
                                        ->nullable()
                                        ->numeric()
                                        ->step(0.0000001),
                                    Forms\Components\TextInput::make('end_longitude')
                                        ->label(__('presensi::app.resources.attendance.form.fields.end_longitude'))
                                        ->nullable()
                                        ->numeric()
                                        ->step(0.0000001),
                                    Forms\Components\FileUpload::make('end_photo_path')
                                        ->label(__('presensi::app.resources.attendance.form.fields.end_photo_path'))
                                        ->image()
                                        ->directory('presensi/attendances/end')
                                        ->nullable()
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->collapsed(),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 2]),

                        // Right Column
                        Group::make([
                            Section::make('Referensi Jadwal')
                                ->description('Jadwal yang seharusnya diikuti.')
                                ->schema([
                                    Forms\Components\TimePicker::make('schedule_start_time')
                                        ->label(__('presensi::app.resources.attendance.form.fields.schedule_start_time'))
                                        ->required()
                                        ->seconds(false),
                                    Forms\Components\TimePicker::make('schedule_end_time')
                                        ->label(__('presensi::app.resources.attendance.form.fields.schedule_end_time'))
                                        ->required()
                                        ->seconds(false),
                                    Forms\Components\TextInput::make('schedule_latitude')
                                        ->label(__('presensi::app.resources.attendance.form.fields.schedule_latitude'))
                                        ->required()
                                        ->numeric()
                                        ->step(0.0000001),
                                    Forms\Components\TextInput::make('schedule_longitude')
                                        ->label(__('presensi::app.resources.attendance.form.fields.schedule_longitude'))
                                        ->required()
                                        ->numeric()
                                        ->step(0.0000001),
                                ])
                                ->columns(1),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 1]),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => static::applyAuthenticatedUserScope($query)->orderByAttendanceDate())
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Attendance Date')
                    ->date()
                    ->getStateUsing(fn (Attendance $record): ?string => $record->attendanceDate()?->toDateString())
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByAttendanceDate($direction)),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('presensi::app.resources.attendance.table.columns.user'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Attendance Status')
                    ->badge()
                    ->getStateUsing(fn (Attendance $record): string => $record->resolvedAttendanceStatus())
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? str($state)->replace('_', ' ')->title()->toString()
                        : 'Pending')
                    ->color(fn (?string $state): string => match ($state) {
                        Attendance::STATUS_CLOSED   => 'success',
                        Attendance::STATUS_OPEN     => 'warning',
                        Attendance::STATUS_ON_LEAVE => 'gray',
                        default                     => 'gray',
                    })
                    ->description(fn (Attendance $record): string => __('presensi::app.resources.attendance.table.description.work_duration', ['value' => $record->workDuration()])),
                Tables\Columns\TextColumn::make('attendance_flags')
                    ->label('Flags')
                    ->getStateUsing(fn (Attendance $record): string => $record->resolvedAttendanceFlagLabel())
                    ->badge()
                    ->color(fn (string $state): string => $state === 'None' ? 'gray' : 'warning'),

                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('presensi::app.resources.attendance.table.columns.start_time')),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(__('presensi::app.resources.attendance.table.columns.end_time'))
                    ->placeholder(__('presensi::app.resources.attendance.table.placeholders.end_time')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function applyUserScope(Builder $query): Builder
    {
        return static::applyAuthenticatedUserScope($query);
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
            'index'  => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit'   => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
