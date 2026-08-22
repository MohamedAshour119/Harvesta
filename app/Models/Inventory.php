<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $guarded = [];

    public function inventoryProducts(): HasMany
    {
        return $this->hasMany(InventoryProduct::class);
    }
}
