<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function products()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_supplier', 'supplier_id', 'product_variant_id')
            ->withPivot('unit_price', 'logistics_cost')
            ->withTimestamps();
    }
}
