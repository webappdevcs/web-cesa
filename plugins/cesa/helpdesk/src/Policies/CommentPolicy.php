<?php

namespace Cesa\Helpdesk\Policies;

use Cesa\Helpdesk\Models\Comment;
use Webkul\Security\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return app(TicketPolicy::class)->view($user, $comment->ticket);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->getKey() === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->getKey() === $comment->user_id || $user->can('delete_helpdesk_ticket');
    }
}
