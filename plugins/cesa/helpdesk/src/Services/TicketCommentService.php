<?php

namespace Cesa\Helpdesk\Services;

use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Webkul\Security\Models\User;

class TicketCommentService
{
    public function __construct(
        protected TicketWorkflowService $ticketWorkflowService,
        protected TicketCommentNotificationService $ticketCommentNotificationService,
    ) {}

    public function create(User $actor, Ticket $ticket, array $attributes): Comment
    {
        Gate::forUser($actor)->authorize('comment', $ticket);

        $visibility = (string) (Arr::get($attributes, 'visibility') ?: Comment::VISIBILITY_PUBLIC);

        if ($visibility === Comment::VISIBILITY_INTERNAL) {
            Gate::forUser($actor)->authorize('addInternalNote', $ticket);
        }

        /** @var Comment $comment */
        $comment = DB::transaction(function () use ($actor, $ticket, $attributes, $visibility): Comment {
            $comment = Comment::query()->create([
                'ticket_id'   => $ticket->id,
                'user_id'     => $actor->id,
                'comment'     => $attributes['comment'],
                'visibility'  => $visibility,
                'attachments' => $attributes['attachments'] ?? [],
            ]);

            if (
                $comment->isPublic()
                && $ticket->isStatus(TicketStatus::OPEN)
                && $this->shouldMoveTicketToInProgress($actor, $ticket)
            ) {
                $this->ticketWorkflowService->transition($actor, $ticket->fresh(), TicketStatus::IN_PROGRESS);
            }

            return $comment->fresh(['user']);
        });

        $this->ticketCommentNotificationService->sendForComment($actor, $ticket->fresh(), $comment);

        return $comment;
    }

    protected function shouldMoveTicketToInProgress(User $actor, Ticket $ticket): bool
    {
        if ((int) $actor->getKey() === (int) $ticket->owner_id) {
            return false;
        }

        return Gate::forUser($actor)->allows('update', $ticket);
    }
}
