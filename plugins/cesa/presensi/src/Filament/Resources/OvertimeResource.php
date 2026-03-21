<?php

namespace Cesa\Presensi\Filament\Resources;

use Cesa\Presensi\Filament\Resources\OvertimeResource\Pages;
use Cesa\Presensi\Models\Overtime;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OvertimeResource extends PresensiResource
{
    protected static ?string $model = Overtime::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('presensi::app.resources.overtime.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('presensi::app.resources.overtime.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('presensi::app.resources.overtime.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Forms\Components\Select::make('user_id')
                ->label(__('presensi::app.resources.overtime.form.fields.user_id'))
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('date')
                ->label(__('presensi::app.resources.overtime.form.fields.date'))
                ->required(),
            Forms\Components\TimePicker::make('start_time')
                ->label(__('presensi::app.resources.overtime.form.fields.start_time'))
                ->required()
                ->seconds(false),
            Forms\Components\TimePicker::make('end_time')
                ->label(__('presensi::app.resources.overtime.form.fields.end_time'))
                ->required()
                ->seconds(false),
            Forms\Components\Textarea::make('reason')
                ->label(__('presensi::app.resources.overtime.form.fields.reason'))
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('attachment')
                ->label(__('presensi::app.resources.overtime.form.fields.attachment'))
                ->directory('presensi/overtimes')
                ->nullable()
                ->columnSpanFull(),
        ];

        if (static::userCan('update_presensi_overtime')) {
            $components[] = Forms\Components\Select::make('status')
                ->options([
                    'pending'  => __('presensi::app.resources.overtime.form.options.pending'),
                    'approved' => __('presensi::app.resources.overtime.form.options.approved'),
                    'rejected' => __('presensi::app.resources.overtime.form.options.rejected'),
                ])
                ->default('pending')
                ->required(fn (string $operation): bool => $operation === 'edit')
                ->label(__('presensi::app.resources.overtime.form.fields.status'))
                ->visible(fn (string $operation): bool => $operation === 'edit');

            $components[] = Forms\Components\Textarea::make('note')
                ->label(__('presensi::app.resources.overtime.form.fields.note'))
                ->columnSpanFull()
                ->visible(fn (string $operation): bool => $operation === 'edit');
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => static::applyAuthenticatedUserScope($query))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('presensi::app.resources.overtime.table.columns.user'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('presensi::app.resources.overtime.table.columns.date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('presensi::app.resources.overtime.table.columns.start_time'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(__('presensi::app.resources.overtime.table.columns.end_time'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label(__('presensi::app.resources.overtime.table.columns.reason'))
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('presensi::app.resources.overtime.table.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->description(fn (Overtime $record): ?string => $record->note ?? null)
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
            'index' => Pages\ListOvertimes::route('/'),
        ];
    }
}
