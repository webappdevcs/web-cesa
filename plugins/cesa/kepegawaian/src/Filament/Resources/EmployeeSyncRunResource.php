<?php

namespace Cesa\Kepegawaian\Filament\Resources;

use Cesa\Kepegawaian\Filament\Resources\EmployeeSyncRunResource\Pages\ListEmployeeSyncRuns;
use Cesa\Kepegawaian\Filament\Resources\EmployeeSyncRunResource\Pages\ViewEmployeeSyncRun;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
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

class EmployeeSyncRunResource extends Resource
{
    protected static ?string $model = EmployeeSyncRun::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'uuid';

    public static function getModelLabel(): string
    {
        return __('kepegawaian::filament/resources/employee-sync-run.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('kepegawaian::filament/resources/employee-sync-run.plural_title');
    }

    public static function getNavigationLabel(): string
    {
        return __('kepegawaian::filament/resources/employee-sync-run.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('kepegawaian::filament/resources/employee.navigation.group');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.uuid'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('source_system')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.source_system'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source_instance')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.source_instance'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mode')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.mode'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'commit' ? 'warning' : 'gray'),
                TextColumn::make('status')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed'    => 'danger',
                        'running'   => 'warning',
                        default     => 'gray',
                    }),
                TextColumn::make('total_records')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.total_records'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('conflict_count')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.conflict_count'))
                    ->numeric()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.started_at'))
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('mode')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.mode'))
                    ->options([
                        'dry_run' => __('kepegawaian::filament/resources/employee-sync-run.values.dry_run'),
                        'commit'  => __('kepegawaian::filament/resources/employee-sync-run.values.commit'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.status'))
                    ->options([
                        'running'   => __('kepegawaian::filament/resources/employee-sync-run.values.running'),
                        'completed' => __('kepegawaian::filament/resources/employee-sync-run.values.completed'),
                        'failed'    => __('kepegawaian::filament/resources/employee-sync-run.values.failed'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('kepegawaian::filament/resources/employee-sync-run.sections.source'))
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('uuid')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.uuid'))
                            ->copyable(),
                        TextEntry::make('file_checksum')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.file_checksum'))
                            ->copyable(),
                        TextEntry::make('source_system')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.source_system'))
                            ->badge(),
                        TextEntry::make('source_instance')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.source_instance')),
                        TextEntry::make('file_name')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.file_name')),
                        TextEntry::make('initiator.name')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.initiator'))
                            ->placeholder('—'),
                        TextEntry::make('mode')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.mode'))
                            ->badge(),
                        TextEntry::make('status')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.status'))
                            ->badge(),
                        TextEntry::make('started_at')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.started_at'))
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('completed_at')
                            ->label(__('kepegawaian::filament/resources/employee-sync-run.fields.completed_at'))
                            ->dateTime('d M Y H:i:s')
                            ->placeholder('—'),
                    ]),
                ]),
            Section::make(__('kepegawaian::filament/resources/employee-sync-run.sections.counts'))
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('total_records')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.total_records')),
                        TextEntry::make('matched_count')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.matched_count')),
                        TextEntry::make('linked_count')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.linked_count')),
                        TextEntry::make('created_count')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.created_count')),
                        TextEntry::make('would_link_count')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.would_link_count')),
                        TextEntry::make('would_create_count')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.would_create_count')),
                        TextEntry::make('conflict_count')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.conflict_count')),
                        TextEntry::make('invalid_count')->label(__('kepegawaian::filament/resources/employee-sync-run.fields.invalid_count')),
                    ]),
                ]),
        ]);
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/sync-runs';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeSyncRuns::route('/'),
            'view'  => ViewEmployeeSyncRun::route('/{record}'),
        ];
    }
}
