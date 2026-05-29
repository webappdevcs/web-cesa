<?php

namespace Cesa\Padelnis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPriceLine extends Model
{
    protected $table = 'padelnis_reservation_price_lines';

    protected $fillable = [
        'catalog_item_id',
        'component',
        'slot',
        'calculation_type',
        'basis',
        'basis_amount',
        'rate',
        'amount',
        'source',
        'price_rule_id',
        'is_commissionable',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basis_amount'      => 'decimal:2',
            'rate'              => 'decimal:4',
            'amount'            => 'decimal:2',
            'is_commissionable' => 'boolean',
            'metadata'          => 'array',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function priceRule(): BelongsTo
    {
        return $this->belongsTo(SpecialPrice::class, 'price_rule_id');
    }
}
