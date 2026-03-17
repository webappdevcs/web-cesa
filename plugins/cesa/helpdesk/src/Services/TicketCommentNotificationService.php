<?php

namespace Cesa\Helpdesk\Services;

use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Models\Ticket;
use Filament\Notifications\Notification;
use Webkul\Security\Models\User;

class TicketCommentNotificationService
{
    public function sendForComment(User $actor, Ticket $ticket, Comment $comment): void
    {
        $ticket->loadMissing(['owner', 'responsible', 'unit.users']);

        $recipients = collect([
            $ticket->responsible,
        ])->filter();

        if ($comment->isPublic()) {
            $recipients->prepend($ticket->owner);
        }

        $recipients = $recipients
            ->merge($ticket->unit?->users ?? collect())
            ->filter()
            ->unique('id')
            ->reject(fn (User $user): bool => (int) $user->id === (int) $actor->id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($comment->isPublic() ? 'Komentar baru pada tiket' : 'Catatan internal baru pada tiket')
            ->body($comment->isPublic()
                ? 'Ada komentar baru yang perlu Anda cek.'
                : 'Ada catatan internal baru pada tiket.')
            ->sendToDatabase($recipients);
    }
}
