<?php

namespace Cesa\Kepegawaian\Filament\Resources;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource\Pages;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource\RelationManagers\HrWorkflowTasksRelationManager;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HrWorkflowRunResource extends Resource
{
    protected static ?string $model = HrWorkflowRun::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('kepegawaian::filament/resources/hr-workflow-run.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('kepegawaian::filament/resources/hr-workflow-run.plural_title');
    }

    public static function getNavigationLabel(): string
    {
        return __('kepegawaian::filament/resources/hr-workflow-run.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('kepegawaian::filament/resources/employee.navigation.group');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.workflow'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.type'))
                    ->badge(),
                TextColumn::make('progress')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.progress'))
                    ->state(fn (HrWorkflowRun $record): string => $record->completed_tasks_count.'/'.$record->tasks_count),
                TextColumn::make('status')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.status'))
                    ->badge()
                    ->color(fn (HrWorkflowRunStatus $state): string => match ($state) {
                        HrWorkflowRunStatus::Completed  => 'success',
                        HrWorkflowRunStatus::Cancelled  => 'danger',
                        HrWorkflowRunStatus::InProgress => 'warning',
                    }),
                TextColumn::make('due_at')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.due_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.started_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        HrWorkflowRunStatus::InProgress->value => __('kepegawaian::filament/resources/hr-workflow-run.statuses.in_progress'),
                        HrWorkflowRunStatus::Completed->value  => __('kepegawaian::filament/resources/hr-workflow-run.statuses.completed'),
                        HrWorkflowRunStatus::Cancelled->value  => __('kepegawaian::filament/resources/hr-workflow-run.statuses.cancelled'),
                    ]),
                SelectFilter::make('type')->options(HrWorkflowTemplateResource::typeOptions()),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('started_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('kepegawaian::filament/resources/hr-workflow-run.sections.summary'))
                ->schema([
                    TextEntry::make('employee.name')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.employee')),
                    TextEntry::make('name')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.workflow')),
                    TextEntry::make('status')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.status'))->badge(),
                    TextEntry::make('progress')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.progress'))->state(fn (HrWorkflowRun $record): string => $record->completed_tasks_count.'/'.$record->tasks_count),
                    TextEntry::make('starter.name')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.started_by'))->placeholder('—'),
                    TextEntry::make('started_at')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.started_at'))->dateTime('d M Y H:i'),
                    TextEntry::make('due_at')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.due_at'))->dateTime('d M Y H:i'),
                    TextEntry::make('completed_at')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.completed_at'))->dateTime('d M Y H:i')->placeholder('—'),
                    TextEntry::make('cancellation_reason')->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.cancellation_reason'))->columnSpanFull()->placeholder('—'),
                ])->columns(2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['employee', 'starter'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn (Builder $query): Builder => $query->whereIn('status', ['completed', 'skipped']),
            ]);
    }

    public static function getRelations(): array
    {
        return [HrWorkflowTasksRelationManager::class];
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/workflows';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHrWorkflowRuns::route('/'),
            'view'  => Pages\ViewHrWorkflowRun::route('/{record}'),
        ];
    }
}
