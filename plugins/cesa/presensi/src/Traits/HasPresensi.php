<?php

namespace Cesa\Presensi\Traits;

use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Overtime;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasPresensi
{
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->presensi_image ? url('storage/'.$this->presensi_image) : null;
    }
}
