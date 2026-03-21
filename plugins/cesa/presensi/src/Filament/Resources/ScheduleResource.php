<?php

namespace Cesa\Presensi\Filament\Resources;

use Cesa\Presensi\Filament\Clusters\Configurations;
use Cesa\Presensi\Filament\Resources\ScheduleResource\Pages;
use Cesa\Presensi\Models\Schedule;
use Cesa\Presensi\Traits\HasPresensiResourceAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    use HasPresensiResourceAccess;

    protected static ?string $model = Schedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return __('presensi::app.resources.schedule.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('presensi::app.resources.schedule.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('presensi::app.resources.schedule.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label(__('presensi::app.resources.schedule.form.fields.user_id'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('shift_id')
                    ->label(__('presensi::app.resources.schedule.form.fields.shift_id'))
                    ->relationship('shift', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('office_id')
                    ->label(__('presensi::app.resources.schedule.form.fields.office_id'))
                    ->relationship('office', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_wfa')
                    ->label(__('presensi::app.resources.schedule.form.fields.is_wfa')),
                Forms\Components\Toggle::make('is_banned')
                    ->label(__('presensi::app.resources.schedule.form.fields.is_banned'))
                    ->hidden(fn (): bool => ! static::userCan('update_presensi_schedule')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => static::applyAuthenticatedUserScope($query))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('presensi::app.resources.schedule.table.columns.user_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label(__('presensi::app.resources.schedule.table.columns.user_email'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_banned')
                    ->hidden(fn (): bool => ! static::userCan('update_presensi_schedule')),
                Tables\Columns\IconColumn::make('is_wfa')
                    ->label(__('presensi::app.resources.schedule.table.columns.is_wfa'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('shift.name')
                    ->label(__('presensi::app.resources.schedule.table.columns.shift'))
                    ->description(fn (Schedule $record): string => $record->shift ? $record->shift->start_time.' - '.$record->shift->end_time : '')
                    ->sortable(),
                Tables\Columns\TextColumn::make('office.name')
                    ->label(__('presensi::app.resources.schedule.table.columns.office'))
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
                //
            ])
            ->actions([
                Actions\EditAction::make()
                    ->modal()
                    ->slideOver()
                    ->modalWidth('md')
                    ->schema(fn (Schema $schema): Schema => static::form($schema->columns(1))),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSchedules::route('/'),
        ];
    }
}
