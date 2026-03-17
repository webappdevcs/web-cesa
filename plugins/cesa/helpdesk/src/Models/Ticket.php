<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_tickets';

    protected $fillable = [
        'priority_id',
        'unit_id',
        'owner_id',
        'problem_category_id',
        'company_id',
        'title',
        'description',
        'supporting_attachments',
        'ticket_status_id',
        'responsible_id',
        'approved_at',
        'solved_at',
        'close_reason',
        'cancel_reason',
        'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'priority_id'             => 'integer',
            'unit_id'                 => 'integer',
            'owner_id'                => 'integer',
            'problem_category_id'     => 'integer',
            'company_id'              => 'integer',
            'ticket_status_id'        => 'integer',
            'responsible_id'          => 'integer',
            'supporting_attachments'  => 'array',
            'approved_at'             => 'datetime',
            'solved_at'               => 'datetime',
            'close_reason'            => 'string',
            'cancel_reason'           => 'string',
            'reopen_reason'           => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (! $ticket->owner_id && Auth::check()) {
                $ticket->owner_id = (int) Auth::id();
            }

            if (! $ticket->ticket_status_id) {
                $ticket->ticket_status_id = TicketStatus::OPEN;
            }

            $ticket->applyProblemCategoryDefaults();
            $ticket->syncLifecycleTimestamps();
        });

        static::saving(function (Ticket $ticket): void {
            $ticket->applyProblemCategoryDefaults();
            $ticket->syncLifecycleTimestamps();
        });

        static::saved(function (Ticket $ticket): void {
            if ($ticket->wasRecentlyCreated || $ticket->wasChanged('ticket_status_id')) {
                TicketHistory::query()->create([
                    'ticket_id'         => $ticket->getKey(),
                    'ticket_status_id'  => $ticket->ticket_status_id,
                    'user_id'           => Auth::id() ?: $ticket->owner_id,
                    'created_at'        => $ticket->updated_at,
                    'updated_at'        => $ticket->updated_at,
                ]);
            }
        });
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function problemCategory(): BelongsTo
    {
        return $this->belongsTo(ProblemCategory::class, 'problem_category_id');
    }

    public function ticketStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ticket_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id');
    }

    public function isStatus(int $status): bool
    {
        return (int) $this->ticket_status_id === $status;
    }

    public function isTerminal(): bool
    {
        return in_array((int) $this->ticket_status_id, [
            TicketStatus::CANCELLED,
            TicketStatus::CLOSED,
        ], true);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('view_any_helpdesk_ticket')) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->outgoingFor($user)
                ->orWhere(fn (Builder $incomingQuery): Builder => $incomingQuery->incomingFor($user));
        });
    }

    public function scopeOutgoingFor(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->getKey());
    }

    public function scopeIncomingFor(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('responsible_id', $user->getKey());

            if (static::canAccessUnitInbox($user)) {
                $unitIds = static::getUnitIdsForUser($user);

                if ($unitIds !== []) {
                    $builder->orWhereIn('unit_id', $unitIds);
                }
            }
        });
    }

    protected function applyProblemCategoryDefaults(): void
    {
        if (! $this->problem_category_id) {
            return;
        }

        $category = ProblemCategory::query()->find($this->problem_category_id);

        if (! $category) {
            return;
        }

        if (! $this->unit_id) {
            $this->unit_id = $category->unit_id;
        }

        if (! $this->responsible_id && $category->default_responsible_id) {
            $this->responsible_id = $category->default_responsible_id;
        }
    }

    protected function syncLifecycleTimestamps(): void
    {
        if (! $this->ticket_status_id) {
            return;
        }

        if ((int) $this->ticket_status_id !== TicketStatus::OPEN && ! $this->approved_at) {
            $this->approved_at = now();
        }

        if ((int) $this->ticket_status_id === TicketStatus::CLOSED && ! $this->solved_at) {
            $this->solved_at = now();
        }
    }

    protected static function newFactory(): Factory
    {
        return TicketFactory::new();
    }

    /**
     * @return array<int, int>
     */
    protected static function getUnitIdsForUser(User $user): array
    {
        return DB::table('helpdesk_unit_user')
            ->where('user_id', $user->getKey())
            ->pluck('unit_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    protected static function canAccessUnitInbox(User $user): bool
    {
        return $user->can('view_any_helpdesk_ticket')
            || $user->can('view_helpdesk_ticket')
            || $user->can('update_helpdesk_ticket');
    }
}
