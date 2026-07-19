<?php

namespace Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\HrWorkflowRunResource;
use Cesa\Kepegawaian\Models\HrWorkflowRun;
use Cesa\Kepegawaian\Services\HrWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Security\Models\User;

class ViewHrWorkflowRun extends ViewRecord
{
    protected static string $resource = HrWorkflowRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label(__('kepegawaian::filament/resources/hr-workflow-run.actions.cancel'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->authorize('cancel')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')
                        ->label(__('kepegawaian::filament/resources/hr-workflow-run.fields.cancellation_reason'))
                        ->required()
                        ->minLength(5),
                ])
                ->action(function (array $data, HrWorkflowService $service): void {
                    /** @var HrWorkflowRun $run */
                    $run = $this->getRecord();
                    /** @var User $actor */
                    $actor = auth()->user();

                    $service->cancel($run, $actor, $data['reason']);

                    Notification::make()
                        ->title(__('kepegawaian::filament/resources/hr-workflow-run.notifications.cancelled'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'cancelled_at', 'cancellation_reason']);
                }),
        ];
    }
}
