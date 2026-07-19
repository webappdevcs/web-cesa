<?php

namespace Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers;

use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Cesa\Kepegawaian\Services\HrWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use LogicException;
use Webkul\Security\Models\User;

class EmployeeWorkflowRelationManager extends RelationManager
{
    protected static string $relationship = 'hrWorkflowRuns';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view_any_kepegawaian_hr::workflow::run') ?? false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('kepegawaian::filament/resources/employee/relation-manager/workflow.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.workflow'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.status'))
                    ->badge(),
                TextColumn::make('tasks_count')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.tasks'))
                    ->counts('tasks'),
                TextColumn::make('due_at')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.due_at'))
                    ->dateTime('d M Y H:i'),
            ])
            ->headerActions([
                Action::make('start')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.actions.start'))
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->can('start_kepegawaian_hr::workflow::run') ?? false)
                    ->schema([
                        Select::make('template_id')
                            ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.template'))
                            ->options(fn (): array => HrWorkflowTemplate::query()
                                ->where('is_active', true)
                                ->where(function (Builder $query): void {
                                    /** @var Employee $employee */
                                    $employee = $this->getOwnerRecord();
                                    $query->whereNull('company_id')->when(
                                        $employee->company_id,
                                        fn (Builder $query, int $companyId): Builder => $query->orWhere('company_id', $companyId),
                                    );
                                })
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('reference_number')
                            ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.reference_number'))
                            ->maxLength(191),
                        DatePicker::make('effective_date')
                            ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.effective_date')),
                        DatePicker::make('expiry_date')
                            ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.expiry_date'))
                            ->afterOrEqual('effective_date'),
                        Textarea::make('notes')
                            ->label(__('kepegawaian::filament/resources/employee/relation-manager/workflow.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, HrWorkflowService $service): void {
                        /** @var Employee $employee */
                        $employee = $this->getOwnerRecord();
                        /** @var User $actor */
                        $actor = auth()->user();
                        $template = HrWorkflowTemplate::query()->findOrFail($data['template_id']);

                        try {
                            $service->start($template, $employee, $actor, [
                                'source'           => 'employee_record',
                                'reference_number' => $data['reference_number'] ?? null,
                                'effective_date'   => $data['effective_date'] ?? null,
                                'expiry_date'      => $data['expiry_date'] ?? null,
                                'notes'            => $data['notes'] ?? null,
                            ]);

                            Notification::make()
                                ->title(__('kepegawaian::filament/resources/employee/relation-manager/workflow.notifications.started'))
                                ->success()
                                ->send();
                        } catch (LogicException $exception) {
                            Notification::make()
                                ->title(__('kepegawaian::filament/resources/employee/relation-manager/workflow.notifications.failed'))
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (HrWorkflowRun $record): string => Route::has('filament.admin.resources.employees.workflows.view')
                        ? HrWorkflowRunResource::getUrl('view', ['record' => $record])
                        : '#'),
            ])
            ->defaultSort('started_at', 'desc');
    }
}
