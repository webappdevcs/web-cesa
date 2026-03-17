<?php

namespace Cesa\Helpdesk\Database\Factories;

use App\Models\User;
use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'ticket_id'    => Ticket::factory(),
            'user_id'      => User::factory(),
            'comment'      => $this->faker->paragraph(),
            'visibility'   => Comment::VISIBILITY_PUBLIC,
            'attachments'  => [],
        ];
    }
}
