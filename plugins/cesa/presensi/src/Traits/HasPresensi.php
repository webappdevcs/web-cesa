<?php

namespace Cesa\Presensi\Traits;

use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Overtime;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasPresensi
{
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class)->withTrashed();
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class)->withTrashed();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->partner?->avatar_url;
    }
}
