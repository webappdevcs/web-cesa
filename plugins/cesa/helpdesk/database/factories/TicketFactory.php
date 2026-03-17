<?php

namespace Cesa\Helpdesk\Database\Factories;

use App\Models\User;
use Cesa\Helpdesk\Models\Priority;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Cesa\Helpdesk\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'priority_id'            => Priority::factory(),
            'unit_id'                => Unit::factory(),
            'owner_id'               => User::factory(),
            'problem_category_id'    => null,
            'company_id'             => null,
            'title'                  => $this->faker->sentence(),
            'description'            => $this->faker->paragraph(),
            'supporting_attachments' => [],
            'ticket_status_id'       => TicketStatus::factory(),
            'responsible_id'         => null,
            'approved_at'            => null,
            'solved_at'              => null,
            'close_reason'           => null,
            'cancel_reason'          => null,
            'reopen_reason'          => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Ticket $ticket): void {
            if ($ticket->problem_category_id) {
                return;
            }

            $category = ProblemCategory::factory()->create([
                'unit_id' => $ticket->unit_id,
            ]);

            $ticket->forceFill([
                'problem_category_id' => $category->getKey(),
            ])->saveQuietly();
        });
    }
}
