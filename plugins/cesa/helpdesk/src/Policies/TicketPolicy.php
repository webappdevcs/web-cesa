<?php

namespace Cesa\Helpdesk\Policies;

use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;

class TicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_helpdesk_ticket')
            || $user->can('view_helpdesk_ticket')
            || $user->can('update_helpdesk_ticket')
            || $user->can('create_helpdesk_ticket');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->can('view_any_helpdesk_ticket')) {
            return true;
        }

        if ($user->getKey() === $ticket->owner_id || $user->getKey() === $ticket->responsible_id) {
            return true;
        }

        return $this->canAccessTicketUnitInbox($user) && $this->belongsToTicketUnit($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->can('create_helpdesk_ticket');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($user->getKey() === $ticket->owner_id) {
            return (int) $ticket->ticket_status_id === TicketStatus::OPEN
                && ($user->can('update_helpdesk_ticket') || $user->can('create_helpdesk_ticket'));
        }

        if ($user->getKey() === $ticket->responsible_id && $user->can('update_helpdesk_ticket')) {
            return true;
        }

        return $user->can('update_helpdesk_ticket')
            && ($user->can('view_any_helpdesk_ticket') || $this->belongsToTicketUnit($user, $ticket));
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        return $this->view($user, $ticket);
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        if ($ticket->isStatus(TicketStatus::OPEN) && $user->getKey() === $ticket->owner_id) {
            return $user->can('create_helpdesk_ticket') || $user->can('update_helpdesk_ticket');
        }

        if (! in_array((int) $ticket->ticket_status_id, [
            TicketStatus::OPEN,
            TicketStatus::IN_PROGRESS,
        ], true)) {
            return false;
        }

        return $user->can('update_helpdesk_ticket')
            && ($user->can('view_any_helpdesk_ticket') || $user->getKey() === $ticket->responsible_id || $this->belongsToTicketUnit($user, $ticket));
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if (! $ticket->isStatus(TicketStatus::IN_PROGRESS)) {
            return false;
        }

        return $user->can('update_helpdesk_ticket')
            && ($user->can('view_any_helpdesk_ticket') || $user->getKey() === $ticket->responsible_id || $this->belongsToTicketUnit($user, $ticket));
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        if (! $ticket->isStatus(TicketStatus::CLOSED)) {
            return false;
        }

        if ($user->getKey() !== $ticket->owner_id) {
            return false;
        }

        return $user->can('create_helpdesk_ticket') || $user->can('update_helpdesk_ticket');
    }

    public function viewInternalNotes(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $this->canAccessTicketUnitInbox($user);
    }

    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $this->comment($user, $ticket)
            && $user->can('update_helpdesk_ticket')
            && ($user->can('view_any_helpdesk_ticket') || $this->belongsToTicketUnit($user, $ticket) || $user->getKey() === $ticket->responsible_id);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        if ($user->getKey() === $ticket->owner_id && (int) $ticket->ticket_status_id === TicketStatus::OPEN) {
            return $user->can('delete_helpdesk_ticket') || $user->can('create_helpdesk_ticket');
        }

        return $user->can('delete_helpdesk_ticket') && $this->belongsToTicketUnit($user, $ticket);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_helpdesk_ticket');
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->can('force_delete_helpdesk_ticket');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_helpdesk_ticket');
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->can('restore_helpdesk_ticket');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_helpdesk_ticket');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_helpdesk_ticket');
    }

    protected function belongsToTicketUnit(User $user, Ticket $ticket): bool
    {
        return DB::table('helpdesk_unit_user')
            ->where('user_id', $user->getKey())
            ->where('unit_id', $ticket->unit_id)
            ->exists();
    }

    protected function canAccessTicketUnitInbox(User $user): bool
    {
        return $user->can('view_any_helpdesk_ticket')
            || $user->can('view_helpdesk_ticket')
            || $user->can('update_helpdesk_ticket');
    }
}
