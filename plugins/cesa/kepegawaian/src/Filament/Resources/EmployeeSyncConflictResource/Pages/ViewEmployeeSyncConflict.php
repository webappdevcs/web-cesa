<?php

namespace Cesa\Kepegawaian\Filament\Resources\EmployeeSyncConflictResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\EmployeeSyncConflictResource;
use Cesa\Kepegawaian\Models\EmployeeSyncConflict;
use Cesa\Kepegawaian\Services\EmployeeSyncConflictResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use Throwable;
use Webkul\Security\Models\User;

class ViewEmployeeSyncConflict extends ViewRecord
{
    protected static string $resource = EmployeeSyncConflictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recheck')
                ->label(__('kepegawaian::filament/resources/employee-sync-conflict.actions.recheck'))
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->visible(fn (EmployeeSyncConflict $record): bool => Gate::allows('resolve', $record))
                ->action(function (Action $action, EmployeeSyncConflict $record): void {
                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        $action->failure();

                        return;
                    }

                    try {
                        $resolved = app(EmployeeSyncConflictResolver::class)->recheck($record, $actor);
                    } catch (Throwable $throwable) {
                        report($throwable);
                        $this->sendFailureNotification();
                        $action->failure();

                        return;
                    }

                    if (! $resolved) {
                        Notification::make()
                            ->title(__('kepegawaian::filament/resources/employee-sync-conflict.notifications.still_ambiguous'))
                            ->warning()
                            ->send();
                        $action->failure();

                        return;
                    }

                    Notification::make()
                        ->title(__('kepegawaian::filament/resources/employee-sync-conflict.notifications.rechecked'))
                        ->success()
                        ->send();
                }),
            Action::make('reject_source')
                ->label(__('kepegawaian::filament/resources/employee-sync-conflict.actions.reject_source'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (EmployeeSyncConflict $record): bool => Gate::allows('resolve', $record))
                ->schema([
                    Select::make('reason_code')
                        ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.rejection_reason'))
                        ->options([
                            'duplicate_source_record' => __('kepegawaian::filament/resources/employee-sync-conflict.rejection_reasons.duplicate_source_record'),
                            'invalid_source_record'   => __('kepegawaian::filament/resources/employee-sync-conflict.rejection_reasons.invalid_source_record'),
                            'out_of_scope'            => __('kepegawaian::filament/resources/employee-sync-conflict.rejection_reasons.out_of_scope'),
                        ])
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('kepegawaian::filament/resources/employee-sync-conflict.fields.resolution_notes'))
                        ->required()
                        ->maxLength(2000),
                ])
                ->action(function (Action $action, EmployeeSyncConflict $record, array $data): void {
                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        $action->failure();

                        return;
                    }

                    try {
                        app(EmployeeSyncConflictResolver::class)->rejectSource(
                            conflict: $record,
                            actor: $actor,
                            reasonCode: (string) $data['reason_code'],
                            notes: (string) $data['notes'],
                        );
                    } catch (Throwable $throwable) {
                        report($throwable);
                        $this->sendFailureNotification();
                        $action->failure();

                        return;
                    }

                    Notification::make()
                        ->title(__('kepegawaian::filament/resources/employee-sync-conflict.notifications.rejected'))
                        ->success()
                        ->send();
                }),
        ];
    }

    private function sendFailureNotification(): void
    {
        Notification::make()
            ->title(__('kepegawaian::filament/resources/employee-sync-conflict.notifications.failed'))
            ->danger()
            ->send();
    }
}
