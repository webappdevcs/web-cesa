<?php

namespace Cesa\FormTransfer\Filament\Resources\TransferRequestResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Services\ApprovalWorkflowService;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Support\TransferRequestAttachmentField;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;

class ViewTransferRequest extends ViewRecord
{
    protected static string $resource = \Cesa\FormTransfer\Filament\Resources\TransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->slideOver(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
            Action::make('download-pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (TransferRequest $record) {
                    $record->loadMissing(['bank', 'division', 'company', 'approvalWorkflow', 'formTransfer']);

                    $pdf = Pdf::loadView('form-transfer::pdf.transfer-request', [
                        'record' => $record,
                    ])->setPaper('a4', 'portrait');

                    $fileName = 'pengajuan-transfer-'.($record->uid ?: $record->id).'.pdf';

                    return response()->streamDownload(function () use ($pdf): void {
                        echo $pdf->output();
                    }, $fileName);
                }),
            Action::make('resend-pending-approver')
                ->label('Kirim ulang ke approver pending')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kirim ulang notifikasi')
                ->modalDescription('Notifikasi akan dikirim ulang ke approver pending di tahap saat ini.')
                ->visible(fn (TransferRequest $record): bool => $this->hasPendingApprover($record))
                ->action(function (TransferRequest $record): void {
                    if ($record->approval_status !== TransferRequestApprovalStatus::PENDING) {
                        Notification::make()
                            ->title('Approval sudah selesai')
                            ->body('Status approval sudah bukan pending.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $pending = $this->getCurrentPendingApproval($record);

                    if (! $pending) {
                        Notification::make()
                            ->title('Tidak ada approver pending')
                            ->body('Tidak ditemukan approver yang sedang pending.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $approval = $pending['approval'] ?? [];
                    $approverEmail = $approval['email'] ?? null;
                    $approverName = $approval['name'] ?? 'Approver';

                    if (! $approverEmail) {
                        Notification::make()
                            ->title('Email approver kosong')
                            ->body('Notifikasi tidak dapat dikirim karena email approver belum diisi.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $approvals = $record->approvals ?? [];
                    app(TransferApprovalNotificationService::class)->notifyApprover($record, $approval, $approvals);

                    if (isset($pending['index'], $approvals[$pending['index']])) {
                        $approvals[$pending['index']]['notified_at'] = now()->toISOString();
                        $record->approvals = $approvals;
                        $record->save();
                    }

                    Notification::make()
                        ->title('Notifikasi dikirim ulang')
                        ->body("Dikirim ke {$approverName}.")
                        ->success()
                        ->send();
                }),
            Action::make('realize-transfer')
                ->label(fn (TransferRequest $record): string => $record->realization_status === TransferRequestRealizationStatus::DONE
                    ? __('form-transfer::app.actions.edit_realization')
                    : __('form-transfer::app.actions.realize_transfer'))
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->slideOver()
                ->modalWidth('md')
                ->visible(fn (TransferRequest $record): bool => in_array($record->realization_status, [
                    TransferRequestRealizationStatus::PENDING,
                    TransferRequestRealizationStatus::DONE,
                    TransferRequestRealizationStatus::CANCELLED,
                ], true))
                ->form([
                    Select::make('realization_status')
                        ->label(__('form-transfer::app.fields.realization_status'))
                        ->options([
                            TransferRequestRealizationStatus::DONE->value      => TransferRequestRealizationStatus::DONE->getLabel(),
                            TransferRequestRealizationStatus::CANCELLED->value => TransferRequestRealizationStatus::CANCELLED->getLabel(),
                        ])
                        ->default(TransferRequestRealizationStatus::DONE->value)
                        ->required()
                        ->live(),
                    DatePicker::make('realized_at')
                        ->label(__('form-transfer::app.fields.realized_at'))
                        ->native(false)
                        ->required(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::DONE->value)
                        ->visible(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::DONE->value),
                    Textarea::make('realization_notes')
                        ->label(__('form-transfer::app.fields.realization_notes'))
                        ->rows(3)
                        ->required(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::CANCELLED->value),
                    TransferRequestAttachmentField::makeRealizationProof()
                        ->visible(fn (Get $get): bool => $get('realization_status') === TransferRequestRealizationStatus::DONE->value),
                ])
                ->fillForm(fn (TransferRequest $record): array => [
                    'realization_status'      => $record->realization_status === TransferRequestRealizationStatus::CANCELLED
                        ? TransferRequestRealizationStatus::CANCELLED->value
                        : TransferRequestRealizationStatus::DONE->value,
                    'realized_at'             => $record->realized_at,
                    'realization_proof_path'  => $record->realization_proof_path,
                    'realization_notes'       => $record->realization_notes,
                ])
                ->action(function (TransferRequest $record, array $data): void {
                    $targetStatus = TransferRequestRealizationStatus::tryFrom((string) ($data['realization_status'] ?? ''));

                    if ($targetStatus === TransferRequestRealizationStatus::CANCELLED) {
                        $record->fill([
                            'realization_notes'  => $data['realization_notes'] ?? $record->realization_notes,
                            'realization_status' => TransferRequestRealizationStatus::CANCELLED,
                        ]);

                        $record->save();

                        return;
                    }

                    $wasDone = $record->realization_status === TransferRequestRealizationStatus::DONE;

                    $record->fill([
                        'realized_at'            => $data['realized_at'],
                        'realization_proof_path' => $data['realization_proof_path'] ?? $record->realization_proof_path,
                        'realization_notes'      => $data['realization_notes'] ?? $record->realization_notes,
                        'realization_status'     => TransferRequestRealizationStatus::DONE,
                    ]);

                    if (! $wasDone && Auth::id()) {
                        $record->user_id = Auth::id();
                    }

                    $record->save();
                })
                ->modalHeading(fn (TransferRequest $record): string => $record->realization_status === TransferRequestRealizationStatus::DONE
                    ? __('form-transfer::app.actions.edit_realization')
                    : __('form-transfer::app.actions.realize_transfer')),
        ];
    }

    protected function hasPendingApprover(TransferRequest $record): bool
    {
        if ($record->approval_status !== TransferRequestApprovalStatus::PENDING) {
            return false;
        }

        return $this->getCurrentPendingApproval($record) !== null;
    }

    /**
     * @return array{index: int, approval: array}|null
     */
    protected function getCurrentPendingApproval(TransferRequest $record): ?array
    {
        $approvals = $record->approvals ?? [];

        if ($approvals === []) {
            return null;
        }

        $pending = app(ApprovalWorkflowService::class)->getCurrentPendingApproval($approvals);

        if (! $pending || ! isset($pending['approval'])) {
            return null;
        }

        $status = $pending['approval']['status'] ?? null;

        if ($status !== ApprovalStatus::PENDING->value) {
            return null;
        }

        return $pending;
    }
}
