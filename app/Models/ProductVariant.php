<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\InventoryProduct;

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $guarded = [];

    protected $casts = [
        'unit_price_usd' => 'float',
        'price_after_discount' => 'float',
        'agent_price' => 'float',
        'discount_percentage' => 'float',
        'discount_applied_at' => 'datetime',
        'product_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant): void {
            if ($variant->agent_price === null || $variant->agent_price === '') {
                $variant->agent_price = $variant->resolveAgentPriceFallback();
            }

            if (! $variant->exists) {
                $variant->discount_applied_at = (float) $variant->discount_percentage > 0
                    ? ($variant->discount_applied_at ?? now())
                    : null;

                return;
            }

            if (! $variant->isDirty('discount_percentage')) {
                return;
            }

            $previousDiscount = (float) ($variant->getOriginal('discount_percentage') ?? 0);
            $currentDiscount = (float) ($variant->discount_percentage ?? 0);

            if ($currentDiscount <= 0) {
                $variant->discount_applied_at = null;

                return;
            }

            if ($previousDiscount !== $currentDiscount) {
                $variant->discount_applied_at = now();
            }
        });
    }

    public function getStockAvailabilityAttribute(): ?string
    {
        return $this->attributes['availability'] ?? null;
    }

    public function setStockAvailabilityAttribute(?string $value): void
    {
        $this->attributes['availability'] = $value;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variantType(): BelongsTo
    {
        return $this->belongsTo(VariantType::class);
    }

    /**
     * Get all ratings for this variant
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(ProductRating::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier', 'product_variant_id', 'supplier_id')
            ->withPivot('unit_price', 'logistics_cost')
            ->withTimestamps();
    }

    /**
     * Get inventory records for this variant
     */
    public function inventoryProducts(): HasMany
    {
        return $this->hasMany(InventoryProduct::class, 'variant_id');
    }

    /**
     * Get average rating for this variant
     */
    public function getAverageRatingAttribute(): float
    {
        return ProductRating::getAverageRating($this->id);
    }

    /**
     * Get total rating count for this variant
     */
    public function getTotalRatingsAttribute(): int
    {
        return ProductRating::getTotalRatings($this->id);
    }

    /**
     * Get rating breakdown for this variant
     */
    public function getRatingBreakdownAttribute(): array
    {
        return ProductRating::getRatingBreakdown($this->id);
    }

    /**
     * Get total stock quantity for this variant
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->inventoryProducts()->sum('quantity');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variants_images')
            ->singleFile();
    }

    public function resolveAgentPrice(): float
    {
        return round((float) ($this->agent_price ?? $this->resolveAgentPriceFallback()), 2);
    }

    public function resolveAgentPriceFallback(): float
    {
        return round((float) ($this->unit_price_usd ?? 0), 2);
    }

    public function shouldUseAgentPriceFor(?User $user = null): bool
    {
        return (bool) $user?->is_agent;
    }

    public function resolveSiteOriginalPrice(?User $user = null): float
    {
        if ($this->shouldUseAgentPriceFor($user)) {
            return $this->resolveAgentPrice();
        }

        return round((float) ($this->unit_price_usd ?? 0), 2);
    }

    public function resolveSiteDisplayPrice(?User $user = null): float
    {
        if ($this->shouldUseAgentPriceFor($user)) {
            return $this->resolveAgentPrice();
        }

        $originalPrice = $this->resolveSiteOriginalPrice($user);
        $discountedPrice = round((float) ($this->price_after_discount ?? 0), 2);

        return $discountedPrice > 0 && $discountedPrice < $originalPrice
            ? $discountedPrice
            : $originalPrice;
    }

    public function resolveSiteDiscountPercentage(?User $user = null): float
    {
        if ($this->shouldUseAgentPriceFor($user)) {
            return 0.0;
        }

        return round((float) ($this->discount_percentage ?? 0), 2);
    }

    public function resolveSiteComparablePrice(?User $user = null): float
    {
        return $this->shouldUseAgentPriceFor($user)
            ? $this->resolveAgentPrice()
            : round((float) ($this->unit_price_usd ?? 0), 2);
    }
}
