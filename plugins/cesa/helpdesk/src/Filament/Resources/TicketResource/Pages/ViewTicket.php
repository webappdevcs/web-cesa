<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\Pages;

use Cesa\Helpdesk\Filament\Resources\TicketResource;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Cesa\Helpdesk\Services\TicketWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use Webkul\Security\Models\User;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('start_progress')
                ->label('Start Progress')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Ticket $record): bool => $record->isStatus(TicketStatus::OPEN) && Gate::allows('update', $record))
                ->action(function (Ticket $record, TicketWorkflowService $ticketWorkflowService): void {
                    $updatedTicket = $ticketWorkflowService->transition($this->resolveAuthenticatedUser(), $record, TicketStatus::IN_PROGRESS);

                    $this->record = $updatedTicket;
                    $this->refreshFormData(['ticket_status_id', 'approved_at', 'solved_at']);
                }),
            Action::make('close_ticket')
                ->label('Close Ticket')
                ->color('success')
                ->visible(fn (Ticket $record): bool => Gate::allows('close', $record))
                ->form([
                    Textarea::make('close_reason')
                        ->label('Close Reason')
                        ->required(),
                ])
                ->action(function (Ticket $record, array $data, TicketWorkflowService $ticketWorkflowService): void {
                    $updatedTicket = $ticketWorkflowService->transition(
                        $this->resolveAuthenticatedUser(),
                        $record,
                        TicketStatus::CLOSED,
                        closeReason: $data['close_reason'],
                    );

                    $this->record = $updatedTicket;
                    $this->refreshFormData(['ticket_status_id', 'approved_at', 'solved_at', 'close_reason', 'cancel_reason', 'reopen_reason']);
                }),
            Action::make('cancel_ticket')
                ->label('Cancel Ticket')
                ->color('danger')
                ->visible(fn (Ticket $record): bool => Gate::allows('cancel', $record))
                ->form([
                    Textarea::make('cancel_reason')
                        ->label('Cancel Reason')
                        ->required(),
                ])
                ->action(function (Ticket $record, array $data, TicketWorkflowService $ticketWorkflowService): void {
                    $updatedTicket = $ticketWorkflowService->transition(
                        $this->resolveAuthenticatedUser(),
                        $record,
                        TicketStatus::CANCELLED,
                        cancelReason: $data['cancel_reason'],
                    );

                    $this->record = $updatedTicket;
                    $this->refreshFormData(['ticket_status_id', 'approved_at', 'solved_at', 'close_reason', 'cancel_reason', 'reopen_reason']);
                }),
            Action::make('reopen_ticket')
                ->label('Reopen Ticket')
                ->color('info')
                ->visible(fn (Ticket $record): bool => Gate::allows('reopen', $record))
                ->form([
                    Textarea::make('reopen_reason')
                        ->label('Reopen Reason')
                        ->required(),
                ])
                ->action(function (Ticket $record, array $data, TicketWorkflowService $ticketWorkflowService): void {
                    $updatedTicket = $ticketWorkflowService->transition(
                        $this->resolveAuthenticatedUser(),
                        $record,
                        TicketStatus::OPEN,
                        reopenReason: $data['reopen_reason'],
                    );

                    $this->record = $updatedTicket;
                    $this->refreshFormData(['ticket_status_id', 'approved_at', 'solved_at', 'close_reason', 'cancel_reason', 'reopen_reason']);
                }),
            DeleteAction::make(),
        ];
    }

    protected function resolveAuthenticatedUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403, 'Authenticated user is invalid.');

        return $user;
    }
}
