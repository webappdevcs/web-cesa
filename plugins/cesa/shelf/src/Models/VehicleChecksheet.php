<?php

namespace Cesa\Shelf\Models;

use Cesa\Shelf\Concerns\InteractsWithManagedFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleChecksheet extends ShelfModel
{
    use HasFactory;
    use InteractsWithManagedFiles;

    protected $fillable = [
        'asset_id',
        'reference_number',
        'pic',
        'license_plate',
        'location',
        'destination',
        'remarks',
        'start_km',
        'departure_time',
        'departure_photo',
        'departure_damage_report',
        'end_km',
        'return_time',
        'return_photo',
        'return_damage_report',
        'rental_duration',
        'distance_traveled',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'return_time'    => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsToIncludingTrashed(Asset::class, 'asset_id');
    }

    public function getResourceUsersAttribute(): EloquentCollection
    {
        $users = new EloquentCollection;

        if ($this->creator) {
            $users->push($this->creator);
        }

        $assetUsers = $this->asset?->getAttribute('resource_users');

        if ($assetUsers instanceof EloquentCollection) {
            foreach ($assetUsers as $relatedUser) {
                if ($relatedUser && ! $users->contains('id', $relatedUser->id)) {
                    $users->push($relatedUser);
                }
            }
        }

        return $users;
    }

    public function scopeApplyPermissionScope(Builder $query): Builder
    {
        $user = filament()->auth()->user();

        if (! $user) {
            return $query;
        }

        $userIds = bouncer()->getAuthorizedUserIds();

        if (empty($userIds)) {
            return $query;
        }

        return $query->where(function (Builder $scopedQuery) use ($userIds): void {
            $scopedQuery->whereIn('creator_id', $userIds)
                ->orWhereHas('asset', function (Builder $assetQuery) use ($userIds): void {
                    $assetQuery->where(function (Builder $relatedQuery) use ($userIds): void {
                        $relatedQuery->whereIn('creator_id', $userIds)
                            ->orWhereIn('recipient_id', $userIds)
                            ->orWhereIn('nbh_responsible_user_id', $userIds);
                    });
                });
        });
    }

    protected static function booted(): void
    {
        static::saving(function (self $vehicleChecksheet): void {
            $vehicleChecksheet->calculateRentalDetails();
        });
    }

    protected function calculateRentalDetails(): void
    {
        if (isset($this->start_km, $this->end_km) && is_numeric($this->start_km) && is_numeric($this->end_km)) {
            $this->distance_traveled = max(0, $this->end_km - $this->start_km);
        } else {
            $this->distance_traveled = 0;
        }

        if (isset($this->departure_time, $this->return_time)) {
            $departure = \Carbon\Carbon::parse($this->departure_time);
            $return = \Carbon\Carbon::parse($this->return_time);

            $durationInMinutes = $departure->diffInMinutes($return);

            $this->rental_duration = round($durationInMinutes / 1440, 5);
        } else {
            $this->rental_duration = 0;
        }
    }

    protected function managedFileAttributes(): array
    {
        return [
            'departure_photo' => [
                'directory' => 'shelf/vehicle-checksheets/departure-photos',
            ],
            'departure_damage_report' => [
                'directory' => 'shelf/vehicle-checksheets/departure-damage-reports',
            ],
            'return_photo' => [
                'directory' => 'shelf/vehicle-checksheets/return-photos',
            ],
            'return_damage_report' => [
                'directory' => 'shelf/vehicle-checksheets/return-damage-reports',
            ],
        ];
    }
}
