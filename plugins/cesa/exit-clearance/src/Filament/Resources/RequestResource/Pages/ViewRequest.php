<?php

namespace Cesa\ExitClearance\Filament\Resources\RequestResource\Pages;

use Cesa\ExitClearance\Filament\Resources\RequestResource;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Services\ExitClearanceNotificationService;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Request $record): bool => $this->canModify($record)),
            Action::make('resend-pending-approvers')
                ->label('Kirim ulang ke approver pending')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kirim ulang notifikasi')
                ->modalDescription('Notifikasi akan dikirim ulang ke approver yang statusnya masih pending.')
                ->visible(fn (Request $record): bool => $this->hasPendingApprovers($record))
                ->action(function (Request $record): void {
                    $requestService = app(ExitClearanceRequestService::class);

                    if ($requestService->normalizeFormStatus($record->form_status) !== 'pending') {
                        Notification::make()
                            ->title('Form sudah selesai')
                            ->body('Status form sudah bukan pending.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $sentCount = app(ExitClearanceNotificationService::class)->notifyPendingApprovers($record);

                    if ($sentCount < 1) {
                        Notification::make()
                            ->title('Tidak ada approver pending')
                            ->body('Semua approver sudah diproses.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Notifikasi dikirim ulang')
                        ->body("Dikirim ke {$sentCount} approver pending.")
                        ->success()
                        ->send();
                }),
            DeleteAction::make()
                ->visible(fn (Request $record): bool => $this->canModify($record)),
        ];
    }

    protected function canModify(Request $record): bool
    {
        $requestService = app(ExitClearanceRequestService::class);

        return $requestService->normalizeFormStatus($record->form_status) !== 'approved';
    }

    protected function hasPendingApprovers(Request $record): bool
    {
        $record->loadMissing('approvers');

        $requestService = app(ExitClearanceRequestService::class);

        if ($requestService->normalizeFormStatus($record->form_status) !== 'pending') {
            return false;
        }

        foreach ($record->approvers as $approver) {
            $status = $requestService->normalizeApprovalStatus($approver->pivot?->status);

            if ($status === ExitClearanceRequestService::APPROVAL_PENDING) {
                return true;
            }
        }

        return false;
    }
}
