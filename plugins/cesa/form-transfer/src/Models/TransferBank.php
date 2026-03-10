<?php

namespace Cesa\FormTransfer\Models;

use Cesa\FormTransfer\Database\Factories\TransferBankFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferBank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'form_transfer_banks';

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function transferRequests(): HasMany
    {
        return $this->hasMany(TransferRequest::class, 'bank_id')->withTrashed();
    }

    /**
     * Get display name (prefer short_name if available)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->short_name ?? $this->name;
    }

    /**
     * Get bank options for form dropdown
     */
    public static function getOptions(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->mapWithKeys(function ($bank) {
                return [$bank->getKey() => $bank->display_name];
            })
            ->all();
    }

    protected static function newFactory(): Factory
    {
        return TransferBankFactory::new();
    }
}
