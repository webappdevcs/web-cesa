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
        return $user->can('view_any_helpdesk_ticket') || $user->can('create_helpdesk_ticket');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->getKey() === $ticket->owner_id || $user->getKey() === $ticket->responsible_id) {
            return true;
        }

        if (! ($user->can('view_any_helpdesk_ticket') || $user->can('view_helpdesk_ticket'))) {
            return false;
        }

        return $this->belongsToTicketUnit($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->can('create_helpdesk_ticket');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ((int) $ticket->ticket_status_id === TicketStatus::CANCELLED || (int) $ticket->ticket_status_id === TicketStatus::CLOSED) {
            return false;
        }

        if ($user->getKey() === $ticket->owner_id) {
            return (int) $ticket->ticket_status_id === TicketStatus::OPEN
                && ($user->can('update_helpdesk_ticket') || $user->can('create_helpdesk_ticket'));
        }

        if ($user->getKey() === $ticket->responsible_id && $user->can('update_helpdesk_ticket')) {
            return true;
        }

        return $user->can('update_helpdesk_ticket') && $this->belongsToTicketUnit($user, $ticket);
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
}
