<?php

namespace Cesa\Kepegawaian\Filament\Resources;

use Cesa\Kepegawaian\Filament\Resources\EmployeeSyncConflictResource\Pages\ListEmployeeSyncConflicts;
use Cesa\Kepegawaian\Filament\Resources\EmployeeSyncConflictResource\Pages\ViewEmployeeSyncConflict;
use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeSyncConflictResource extends Resource
{
    protected static ?string $model = EmployeeSyncConflict::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('kepegawaian::filament/resources/employee-sync-conflict.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('kepegawaian::filament/resources/employee-sync-conflict.plural_title');
    }

    public static function getNavigationLabel(): string
    {
        return __('kepegawaian::filament/resources/employee-sync-conflict.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('kepegawaian::filament/resources/employee.navigation.group');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.type'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'open' ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('sourceRecord.syncRun.source_system')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.source_system'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('sourceRecord.syncRun.source_instance')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.source_instance')),
                TextColumn::make('sourceRecord.row_number')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.row_number'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sourceRecord.external_id')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.external_id'))
                    ->formatStateUsing(fn (?string $state): string => self::mask($state)),
                TextColumn::make('sourceRecord.employee_code')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.employee_code'))
                    ->formatStateUsing(fn (?string $state): string => self::mask($state)),
                TextColumn::make('employee.employee_code')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.canonical_employee'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.created_at'))
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.status'))
                    ->options([
                        'open'     => __('kepegawaian::filament/resources/employee-sync-conflict.values.open'),
                        'resolved' => __('kepegawaian::filament/resources/employee-sync-conflict.values.resolved'),
                    ]),
                SelectFilter::make('type')
                    ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.type'))
                    ->options([
                        'invalid_record'                    => __('kepegawaian::filament/resources/employee-sync-conflict.types.invalid_record'),
                        'missing_external_id'               => __('kepegawaian::filament/resources/employee-sync-conflict.types.missing_external_id'),
                        'identifier_employee_code_mismatch' => __('kepegawaian::filament/resources/employee-sync-conflict.types.identifier_employee_code_mismatch'),
                        'employee_code_change'              => __('kepegawaian::filament/resources/employee-sync-conflict.types.employee_code_change'),
                        'retired_employee_match'            => __('kepegawaian::filament/resources/employee-sync-conflict.types.retired_employee_match'),
                        'retired_identifier_match'          => __('kepegawaian::filament/resources/employee-sync-conflict.types.retired_identifier_match'),
                        'multiple_source_record_ids'        => __('kepegawaian::filament/resources/employee-sync-conflict.types.multiple_source_record_ids'),
                        'employee_code_candidate_match'     => __('kepegawaian::filament/resources/employee-sync-conflict.types.employee_code_candidate_match'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('kepegawaian::filament/resources/employee-sync-conflict.sections.review'))
                ->description(__('kepegawaian::filament/resources/employee-sync-conflict.sections.review_help'))
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('type')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.type'))
                            ->badge(),
                        TextEntry::make('status')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.status'))
                            ->badge(),
                        TextEntry::make('sourceRecord.syncRun.uuid')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.run_uuid'))
                            ->copyable(),
                        TextEntry::make('sourceRecord.syncRun.mode')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.run_mode'))
                            ->badge(),
                        TextEntry::make('sourceRecord.syncRun.source_system')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.source_system')),
                        TextEntry::make('sourceRecord.syncRun.source_instance')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.source_instance')),
                        TextEntry::make('sourceRecord.row_number')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.row_number')),
                        TextEntry::make('sourceRecord.external_id')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.external_id'))
                            ->copyable(),
                        TextEntry::make('sourceRecord.employee_code')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.employee_code'))
                            ->copyable(),
                        TextEntry::make('employee.employee_code')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.canonical_employee'))
                            ->placeholder('—'),
                        TextEntry::make('resolution')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.resolution'))
                            ->placeholder('—'),
                        TextEntry::make('resolver.name')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.resolver'))
                            ->placeholder('—'),
                        TextEntry::make('resolution_notes')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.resolution_notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('resolved_at')
                            ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.resolved_at'))
                            ->dateTime('d M Y H:i:s')
                            ->placeholder('—'),
                    ]),
                ]),
        ]);
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/sync-conflicts';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeSyncConflicts::route('/'),
            'view'  => ViewEmployeeSyncConflict::route('/{record}'),
        ];
    }

    private static function mask(?string $value): string
    {
        $value = trim((string) $value);
        $length = mb_strlen($value);

        if ($length === 0) {
            return '—';
        }

        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return mb_substr($value, 0, 2)
            .str_repeat('•', min($length - 4, 12))
            .mb_substr($value, -2);
    }
}
