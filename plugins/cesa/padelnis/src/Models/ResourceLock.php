<?php

namespace Cesa\Padelnis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceLock extends Model
{
    public const string RESOURCE_COURT = 'court';

    public const string RESOURCE_COACH = 'coach';

    protected $table = 'padelnis_resource_locks';

    protected $fillable = [
        'reservation_id',
        'resource_type',
        'resource_id',
        'lock_date',
        'slot',

        'active_lock_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resource_id' => 'integer',
            'lock_date'   => 'date',

            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public static function makeActiveLockKey(string $resourceType, int $resourceId, mixed $lockDate, string $slot): ?string
    {
        $date = Reservation::normalizeReservationDateForLock($lockDate);

        if ($date === null || $resourceId <= 0 || blank($slot)) {
            return null;
        }

        return "{$resourceType}|{$resourceId}|{$date}|{$slot}";
    }
}
