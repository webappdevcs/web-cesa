<?php

namespace Cesa\Presensi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'presensi_shifts';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];
}
