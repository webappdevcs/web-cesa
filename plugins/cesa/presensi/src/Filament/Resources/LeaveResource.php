<?php

namespace Cesa\Presensi\Filament\Resources;

use Cesa\Presensi\Filament\Resources\LeaveResource\Pages;
use Cesa\Presensi\Models\Leave;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveResource extends PresensiResource
{
    protected static ?string $model = Leave::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('presensi::app.resources.leave.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('presensi::app.resources.leave.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('presensi::app.resources.leave.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Forms\Components\Select::make('user_id')
                ->label(__('presensi::app.resources.leave.form.fields.user_id'))
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('type')
                ->label(__('presensi::app.resources.leave.form.fields.type'))
                ->options([
                    'Izin'  => __('presensi::app.resources.leave.form.options.izin'),
                    'Sakit' => __('presensi::app.resources.leave.form.options.sakit'),
                    'Cuti'  => __('presensi::app.resources.leave.form.options.cuti'),
                ])
                ->default('Izin')
                ->required(),
            Forms\Components\DatePicker::make('start_date')
                ->label(__('presensi::app.resources.leave.form.fields.start_date'))
                ->required(),
            Forms\Components\DatePicker::make('end_date')
                ->label(__('presensi::app.resources.leave.form.fields.end_date'))
                ->required(),
            Forms\Components\Textarea::make('reason')
                ->label(__('presensi::app.resources.leave.form.fields.reason'))
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('attachment')
                ->label(__('presensi::app.resources.leave.form.fields.attachment'))
                ->directory('presensi/leaves')
                ->nullable()
                ->columnSpanFull(),
        ];

        if (static::userCan('update_presensi_leave')) {
            $components[] = Forms\Components\Select::make('status')
                ->options([
                    'pending'  => __('presensi::app.resources.leave.form.options.pending'),
                    'approved' => __('presensi::app.resources.leave.form.options.approved'),
                    'rejected' => __('presensi::app.resources.leave.form.options.rejected'),
                ])
                ->default('pending')
                ->required()
                ->label(__('presensi::app.resources.leave.form.fields.status'));

            $components[] = Forms\Components\Textarea::make('note')
                ->label(__('presensi::app.resources.leave.form.fields.note'))
                ->columnSpanFull();
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => static::applyAuthenticatedUserScope($query))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('presensi::app.resources.leave.table.columns.user'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('presensi::app.resources.leave.table.columns.type'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('presensi::app.resources.leave.table.columns.start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('presensi::app.resources.leave.table.columns.end_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('presensi::app.resources.leave.table.columns.reason'))
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('presensi::app.resources.leave.table.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger'
                    })
                    ->description(fn (Leave $record): ?string => $record->note ?? null)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListLeaves::route('/'),
        ];
    }
}
