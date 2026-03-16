<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_comments';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'comment',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'ticket_id'    => 'integer',
            'user_id'      => 'integer',
            'attachments'  => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function newFactory(): Factory
    {
        return CommentFactory::new();
    }
}
