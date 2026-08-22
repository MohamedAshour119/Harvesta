<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'variant_id',
    ];

    /**
     * Get the user that owns the wishlist item.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product associated with the wishlist item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant associated with the wishlist item.
     * Assuming your variant model is named ProductVariant.
     * Adjust if it's different (e.g., Variant::class).
     */
    public function variant()
    {
        // If your variant model is named ProductVariant and is in App\Models namespace
        return $this->belongsTo(ProductVariant::class, 'variant_id'); 
    }
}
