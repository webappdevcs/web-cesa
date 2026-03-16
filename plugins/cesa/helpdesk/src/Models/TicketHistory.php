<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\TicketHistoryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class TicketHistory extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_ticket_histories';

    protected $fillable = [
        'ticket_id',
        'ticket_status_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'ticket_id'        => 'integer',
            'ticket_status_id' => 'integer',
            'user_id'          => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function ticketStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function newFactory(): Factory
    {
        return TicketHistoryFactory::new();
    }
}
