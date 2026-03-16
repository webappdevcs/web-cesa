<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\TicketStatusFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketStatus extends Model
{
    use HasFactory, SoftDeletes;

    public const OPEN = 1;

    public const IN_PROGRESS = 2;

    public const CANCELLED = 3;

    public const CLOSED = 4;

    protected $table = 'helpdesk_ticket_statuses';

    protected $fillable = [
        'name',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_status_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class, 'ticket_status_id');
    }

    protected static function newFactory(): Factory
    {
        return TicketStatusFactory::new();
    }
}
