<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_units';

    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'helpdesk_unit_user', 'unit_id', 'user_id')
            ->withTimestamps();
    }

    public function problemCategories(): HasMany
    {
        return $this->hasMany(ProblemCategory::class, 'unit_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'unit_id');
    }

    protected static function newFactory(): Factory
    {
        return UnitFactory::new();
    }
}
