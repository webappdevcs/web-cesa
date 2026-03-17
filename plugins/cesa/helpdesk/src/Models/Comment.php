<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Security\Models\User;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_INTERNAL = 'internal';

    protected $table = 'helpdesk_comments';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'comment',
        'visibility',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'ticket_id'    => 'integer',
            'user_id'      => 'integer',
            'visibility'   => 'string',
            'attachments'  => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Comment $comment): void {
            if (! $comment->user_id && Auth::check()) {
                $comment->user_id = (int) Auth::id();
            }

            if (! $comment->visibility) {
                $comment->visibility = static::VISIBILITY_PUBLIC;
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', static::VISIBILITY_PUBLIC);
    }

    public function isInternal(): bool
    {
        return $this->visibility === static::VISIBILITY_INTERNAL;
    }

    public function isPublic(): bool
    {
        return $this->visibility === static::VISIBILITY_PUBLIC;
    }

    protected static function newFactory(): Factory
    {
        return CommentFactory::new();
    }
}
