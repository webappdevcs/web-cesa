<?php

namespace Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource\RelationManagers;

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Enums\HrWorkflowTaskStatus;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Models\HrWorkflowTask;
use Cesa\Kepegawaian\Services\HrWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Security\Models\User;

class HrWorkflowTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('name')->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.name'))->searchable()->wrap(),
                TextColumn::make('department')->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.department'))->badge(),
                TextColumn::make('assignee.name')->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.assignee'))->placeholder('—'),
                TextColumn::make('due_at')->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.due_at'))->dateTime('d M Y H:i')->color(fn (HrWorkflowTask $record): string => $record->due_at?->isPast() && ! in_array($record->status, [HrWorkflowTaskStatus::Completed, HrWorkflowTaskStatus::Skipped, HrWorkflowTaskStatus::Cancelled], true) ? 'danger' : 'gray'),
                IconColumn::make('requires_evidence')->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.evidence'))->boolean(),
                TextColumn::make('status')->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.status'))->badge(),
            ])
            ->recordActions([
                Action::make('assign')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.actions.assign'))
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (HrWorkflowTask $record): bool => $this->canManage($record))
                    ->fillForm(fn (HrWorkflowTask $record): array => ['assigned_to_id' => $record->assigned_to_id])
                    ->schema([
                        Select::make('assigned_to_id')
                            ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.assignee'))
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (HrWorkflowTask $record, array $data, HrWorkflowService $service): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        $assignee = User::query()->findOrFail($data['assigned_to_id']);
                        $service->assignTask($record, $assignee, $actor);
                        $this->success(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.notifications.assigned'));
                    }),
                Action::make('begin')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.actions.begin'))
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (HrWorkflowTask $record): bool => $this->canManage($record) && $record->status === HrWorkflowTaskStatus::Pending)
                    ->action(function (HrWorkflowTask $record, HrWorkflowService $service): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        $service->beginTask($record, $actor);
                        $this->success(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.notifications.started'));
                    }),
                Action::make('complete')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.actions.complete'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (HrWorkflowTask $record): bool => $this->canManage($record))
                    ->schema([
                        Textarea::make('note')
                            ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.note'))
                            ->rows(3),
                        FileUpload::make('evidence_path')
                            ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.evidence'))
                            ->disk('local')
                            ->directory('hr-workflow-evidence')
                            ->visibility('private')
                            ->required(fn (HrWorkflowTask $record): bool => $record->requires_evidence),
                    ])
                    ->action(function (HrWorkflowTask $record, array $data, HrWorkflowService $service): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        $service->completeTask($record, $actor, $data['note'] ?? null, $data['evidence_path'] ?? null);
                        $this->success(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.notifications.completed'));
                    }),
                Action::make('skip')
                    ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.actions.skip'))
                    ->icon('heroicon-o-forward')
                    ->color('gray')
                    ->visible(fn (HrWorkflowTask $record): bool => $this->canManage($record) && ! $record->is_required)
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.fields.reason'))
                            ->required(),
                    ])
                    ->action(function (HrWorkflowTask $record, array $data, HrWorkflowService $service): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        $service->skipTask($record, $actor, $data['reason']);
                        $this->success(__('kepegawaian::filament/resources/hr-workflow-run/relation-manager/task.notifications.skipped'));
                    }),
            ])
            ->defaultSort('sort_order');
    }

    private function canManage(HrWorkflowTask $task): bool
    {
        /** @var HrWorkflowRun $run */
        $run = $this->getOwnerRecord();

        return $run->status === HrWorkflowRunStatus::InProgress
            && in_array($task->status, [HrWorkflowTaskStatus::Pending, HrWorkflowTaskStatus::InProgress], true)
            && (auth()->user()?->can('manage_tasks_kepegawaian_hr::workflow::run') ?? false);
    }

    private function success(string $title): void
    {
        Notification::make()->title($title)->success()->send();
    }
}
