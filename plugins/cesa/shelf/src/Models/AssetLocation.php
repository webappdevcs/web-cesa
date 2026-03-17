<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetLocation extends ShelfModel
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'description'];
}
