<?php

namespace Cesa\Presensi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'presensi_offices';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
    ];
}
