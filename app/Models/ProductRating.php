<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRating extends Model
{
    use HasFactory;

    protected $table = 'product_ratings';

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who submitted this rating
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product variant being rated
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get average rating for a product variant
     */
    public static function getAverageRating(int $productId): float
    {
        return self::where('product_id', $productId)
            ->whereNotNull('rating')
            ->avg('rating') ?? 0;
    }

    /**
     * Get total rating count for a product variant
     */
    public static function getTotalRatings(int $productId): int
    {
        return self::where('product_id', $productId)->whereNotNull('rating')->count();
    }

    /**
     * Get rating breakdown for a product variant (1-star to 5-star counts)
     */
    public static function getRatingBreakdown(int $productId): array
    {
        $totalRatings = self::getTotalRatings($productId);
        
        if ($totalRatings === 0) {
            return [
                ['stars' => 5, 'count' => 0, 'percentage' => 0],
                ['stars' => 4, 'count' => 0, 'percentage' => 0],
                ['stars' => 3, 'count' => 0, 'percentage' => 0],
                ['stars' => 2, 'count' => 0, 'percentage' => 0],
                ['stars' => 1, 'count' => 0, 'percentage' => 0],
            ];
        }

        $breakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = self::where('product_id', $productId)
                ->whereNotNull('rating')
                ->whereBetween('rating', [$star - 0.5, $star + 0.4])
                ->count();
            
            $percentage = round(($count / $totalRatings) * 100);
            
            $breakdown[] = [
                'stars' => $star,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return $breakdown;
    }
}
