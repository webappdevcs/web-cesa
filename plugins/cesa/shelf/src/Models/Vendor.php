<?php

namespace Cesa\Shelf\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends ShelfModel
{
    use HasFactory;

    protected $fillable = ['name', 'last_price'];
}
