<?php

namespace Cesa\Helpdesk\Models;

use Cesa\Helpdesk\Database\Factories\ProblemCategoryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;

class ProblemCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_problem_categories';

    protected $fillable = [
        'unit_id',
        'name',
        'default_responsible_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_id'                => 'integer',
            'default_responsible_id' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function defaultResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_responsible_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'problem_category_id');
    }

    protected static function newFactory(): Factory
    {
        return ProblemCategoryFactory::new();
    }
}
