<?php

namespace App\Http\Repositories;

use App\Models\ProductRating;
use Illuminate\Pagination\LengthAwarePaginator;

class RatingRepository
{
    public function findExistingRating(int $userId, int $productId): ?ProductRating
    {
        return ProductRating::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
    }

    public function createRating(array $data): ProductRating
    {
        return ProductRating::create($data);
    }

    public function updateRating(ProductRating $rating, array $data): ProductRating
    {
        $rating->update($data);
        return $rating->fresh();
    }

    public function findProduct(int $productId): ProductRating
    {
        return ProductRating::find($productId);
    }

    public function getProductRatings(int $productId): \Illuminate\Database\Eloquent\Collection
    {
        return ProductRating::where('product_id', $productId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAverageRating(int $productId): float
    {
        return ProductRating::getAverageRating($productId);
    }

    public function getTotalRatings(int $productId): int
    {
        return ProductRating::getTotalRatings($productId);
    }

    public function getRatingBreakdown(int $productId): array
    {
        return ProductRating::getRatingBreakdown($productId);
    }

    public function getUserRating(int $userId, int $productId): ?ProductRating
    {
        return ProductRating::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
    }

    public function getPaginatedRatings(int $productId, int $perPage, int $page): LengthAwarePaginator
    {
        return ProductRating::with('user')
            ->where('product_id', $productId)
            ->whereNotNull('comment')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
