<?php

namespace Cesa\Presensi\Filament\Resources;

use Auth;
use Cesa\Presensi\Filament\Resources\AttendanceResource\Pages;
use Cesa\Presensi\Models\Attendance;
use Filament\Actions;
use Filament\Forms;
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
                Section::make(__('presensi::app.resources.attendance.form.sections.user'))
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('presensi::app.resources.attendance.form.fields.user_id'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Toggle::make('is_leave')
                            ->label(__('presensi::app.resources.attendance.form.fields.is_leave'))
                            ->default(false)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('presensi::app.resources.attendance.form.sections.schedule'))
                    ->schema([
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
                        Forms\Components\TimePicker::make('schedule_start_time')
                            ->label(__('presensi::app.resources.attendance.form.fields.schedule_start_time'))
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('schedule_end_time')
                            ->label(__('presensi::app.resources.attendance.form.fields.schedule_end_time'))
                            ->required()
                            ->seconds(false),
                    ])
                    ->columns(2),

                Section::make(__('presensi::app.resources.attendance.form.sections.check_in'))
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
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make(__('presensi::app.resources.attendance.form.sections.check_out'))
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
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => static::applyUserScope($query))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('presensi::app.resources.attendance.table.columns.created_at'))
                    ->date()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('presensi::app.resources.attendance.table.columns.user'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_late')
                    ->label(__('presensi::app.resources.attendance.table.columns.status'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->isLate()
                            ? __('presensi::app.resources.attendance.table.statuses.late')
                            : __('presensi::app.resources.attendance.table.statuses.on_time');
                    })
                    ->color(fn (string $state): string => match ($state) {
                        __('presensi::app.resources.attendance.table.statuses.on_time') => 'success',
                        __('presensi::app.resources.attendance.table.statuses.late')    => 'danger',
                    })
                    ->description(fn (Attendance $record): string => __('presensi::app.resources.attendance.table.description.work_duration', ['value' => $record->workDuration()])),

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
            ->defaultSort('created_at', 'desc')
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
        $authenticatedUser = Auth::user();

        if ($authenticatedUser && ! $authenticatedUser->hasRole('super_admin')) {
            $query->where($query->getModel()->qualifyColumn('user_id'), $authenticatedUser->id);
        }

        return $query;
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
