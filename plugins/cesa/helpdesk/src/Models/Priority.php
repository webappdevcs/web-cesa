<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\PriorityFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Priority extends Model
{
    use HasFactory, SoftDeletes;

    public const CRITICAL = 1;

    public const HIGH = 2;

    public const MEDIUM = 3;

    public const LOW = 4;

    public const ENHANCEMENT = 5;

    protected $table = 'helpdesk_priorities';

    protected $fillable = [
        'name',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    protected static function newFactory(): Factory
    {
        return PriorityFactory::new();
    }
}
