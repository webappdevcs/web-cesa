<?php

namespace Cesa\Helpdesk\Services;

use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Webkul\Security\Models\User;

class TicketWorkflowService
{
    public function updateTicket(User $actor, Ticket $ticket, array $attributes): Ticket
    {
        $targetStatusId = Arr::pull($attributes, 'ticket_status_id');
        $closeReason = $this->normalizeReason(Arr::pull($attributes, 'close_reason'));
        $cancelReason = $this->normalizeReason(Arr::pull($attributes, 'cancel_reason'));
        $reopenReason = $this->normalizeReason(Arr::pull($attributes, 'reopen_reason'));

        $this->ensureReasonPayloadIsValid(
            ticket: $ticket,
            targetStatusId: $targetStatusId,
            closeReason: $closeReason,
            cancelReason: $cancelReason,
            reopenReason: $reopenReason,
        );

        return DB::transaction(function () use (
            $actor,
            $ticket,
            $attributes,
            $targetStatusId,
            $closeReason,
            $cancelReason,
            $reopenReason,
        ): Ticket {
            if ($attributes !== []) {
                $ticket->fill($attributes);
                $ticket->save();
                $ticket = $ticket->fresh();
            }

            if ($targetStatusId === null || (int) $targetStatusId === (int) $ticket->ticket_status_id) {
                return $ticket->fresh();
            }

            return $this->transition(
                actor: $actor,
                ticket: $ticket,
                targetStatusId: (int) $targetStatusId,
                closeReason: $closeReason,
                cancelReason: $cancelReason,
                reopenReason: $reopenReason,
            );
        });
    }

    public function transition(
        User $actor,
        Ticket $ticket,
        int $targetStatusId,
        ?string $closeReason = null,
        ?string $cancelReason = null,
        ?string $reopenReason = null,
    ): Ticket {
        if ($targetStatusId === (int) $ticket->ticket_status_id) {
            return $ticket->fresh();
        }

        return match (true) {
            $targetStatusId === TicketStatus::IN_PROGRESS                                     => $this->moveToInProgress($actor, $ticket),
            $targetStatusId === TicketStatus::CANCELLED                                       => $this->cancel($actor, $ticket, $cancelReason),
            $targetStatusId === TicketStatus::CLOSED                                          => $this->close($actor, $ticket, $closeReason),
            $targetStatusId === TicketStatus::OPEN && $ticket->isStatus(TicketStatus::CLOSED) => $this->reopen($actor, $ticket, $reopenReason),
            default                                                                           => throw ValidationException::withMessages([
                'ticket_status_id' => 'Transisi status tiket tidak valid.',
            ]),
        };
    }

    protected function moveToInProgress(User $actor, Ticket $ticket): Ticket
    {
        Gate::forUser($actor)->authorize('update', $ticket);

        if (! $ticket->isStatus(TicketStatus::OPEN)) {
            throw ValidationException::withMessages([
                'ticket_status_id' => 'Tiket hanya dapat diproses dari status Open.',
            ]);
        }

        $ticket->forceFill([
            'ticket_status_id' => TicketStatus::IN_PROGRESS,
        ])->save();

        return $ticket->fresh();
    }

    protected function cancel(User $actor, Ticket $ticket, ?string $cancelReason): Ticket
    {
        Gate::forUser($actor)->authorize('cancel', $ticket);

        if ($cancelReason === null) {
            throw ValidationException::withMessages([
                'cancel_reason' => 'Alasan pembatalan tiket wajib diisi.',
            ]);
        }

        $ticket->forceFill([
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => $cancelReason,
            'close_reason'     => null,
        ])->save();

        return $ticket->fresh();
    }

    protected function close(User $actor, Ticket $ticket, ?string $closeReason): Ticket
    {
        Gate::forUser($actor)->authorize('close', $ticket);

        if ($closeReason === null) {
            throw ValidationException::withMessages([
                'close_reason' => 'Alasan penutupan tiket wajib diisi.',
            ]);
        }

        $ticket->forceFill([
            'ticket_status_id' => TicketStatus::CLOSED,
            'close_reason'     => $closeReason,
            'cancel_reason'    => null,
        ])->save();

        return $ticket->fresh();
    }

    protected function reopen(User $actor, Ticket $ticket, ?string $reopenReason): Ticket
    {
        Gate::forUser($actor)->authorize('reopen', $ticket);

        if ($reopenReason === null) {
            throw ValidationException::withMessages([
                'reopen_reason' => 'Alasan membuka kembali tiket wajib diisi.',
            ]);
        }

        $ticket->forceFill([
            'ticket_status_id' => TicketStatus::OPEN,
            'reopen_reason'    => $reopenReason,
            'close_reason'     => null,
            'solved_at'        => null,
        ])->save();

        return $ticket->fresh();
    }

    protected function ensureReasonPayloadIsValid(
        Ticket $ticket,
        mixed $targetStatusId,
        ?string $closeReason,
        ?string $cancelReason,
        ?string $reopenReason,
    ): void {
        $hasReasonPayload = $closeReason !== null || $cancelReason !== null || $reopenReason !== null;

        if ($targetStatusId === null) {
            if ($hasReasonPayload) {
                throw ValidationException::withMessages([
                    'ticket_status_id' => 'Alasan perubahan status hanya dapat dikirim saat melakukan transisi status.',
                ]);
            }

            return;
        }

        $targetStatusId = (int) $targetStatusId;

        if ($targetStatusId === (int) $ticket->ticket_status_id && $hasReasonPayload) {
            throw ValidationException::withMessages([
                'ticket_status_id' => 'Status tujuan harus berbeda dari status saat ini.',
            ]);
        }

        if ($targetStatusId !== TicketStatus::CLOSED && $closeReason !== null) {
            throw ValidationException::withMessages([
                'close_reason' => 'Alasan penutupan hanya berlaku untuk transisi ke status Closed.',
            ]);
        }

        if ($targetStatusId !== TicketStatus::CANCELLED && $cancelReason !== null) {
            throw ValidationException::withMessages([
                'cancel_reason' => 'Alasan pembatalan hanya berlaku untuk transisi ke status Cancelled.',
            ]);
        }

        if (! ($targetStatusId === TicketStatus::OPEN && $ticket->isStatus(TicketStatus::CLOSED)) && $reopenReason !== null) {
            throw ValidationException::withMessages([
                'reopen_reason' => 'Alasan membuka kembali hanya berlaku untuk transisi Closed ke Open.',
            ]);
        }
    }

    protected function normalizeReason(mixed $reason): ?string
    {
        $reason = trim((string) ($reason ?? ''));

        return $reason !== '' ? $reason : null;
    }
}
