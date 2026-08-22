<?php

namespace App\Http\Services;

use App\Http\Repositories\RatingRepository;
use App\Models\ProductVariantRating;
use Exception;
use Illuminate\Support\Facades\Auth;

class RatingService
{
    public function __construct(protected RatingRepository $ratingRepository) {}

    public function submitRating(int $productId, ?float $rating, ?string $comment): array
    {
        $userId = Auth::id();
        
        if (!$userId) {
            throw new Exception('User must be authenticated', 401);
        }

        // Verify product variant exists
        // $this->ratingRepository->findProduct($productId);
        // dd("sss");

        $existingRating = $this->ratingRepository->findExistingRating($userId, $productId);

        $data = [
            'user_id' => $userId,
            'product_id' => $productId,
            'rating' => $rating,
            'comment' => $comment,
        ];

        if ($existingRating) {
            $ratingModel = $this->ratingRepository->updateRating($existingRating, $data);
            $message = 'Rating updated successfully';
        } else {
            $ratingModel = $this->ratingRepository->createRating($data);
            $message = 'Rating submitted successfully';
        }

        // Load user relationship
        $ratingModel->load('user');

        return [
            'rating' => $ratingModel,
            'message' => $message,
            'stats' => $this->getProductStats($productId),
        ];
    }

    public function getProductRatings(int $productId): array
    {
        // Verify product variant exists
        $this->ratingRepository->findProduct($productId);
        
        $ratings = $this->ratingRepository->getProductRatings($productId);

        $formattedRatings = $ratings->map(function ($rating) {
            return [
                'id' => $rating->id,
                'userName' => $rating->user->full_name ?? 'Anonymous',
                'userInitial' => strtoupper(substr($rating->user->full_name ?? 'A', 0, 1)),
                'rating' => $rating->rating,
                'comment' => $rating->comment,
                'date' => $rating->created_at->format('M d, Y'),
            ];
        });

        return [
            'ratings' => $formattedRatings,
            'stats' => $this->getProductStats($productId),
        ];
    }

    public function getProductStats(int $productId): array
    {
        try {
            $averageRating = $this->ratingRepository->getAverageRating($productId);
            $totalRatings = $this->ratingRepository->getTotalRatings($productId);
            $ratingBreakdown = $this->ratingRepository->getRatingBreakdown($productId);

            return [
                'average_rating' => round($averageRating, 1),
                'total_ratings' => $totalRatings,
                'rating_breakdown' => $ratingBreakdown,
            ];
        } catch (Exception $e) {
            return [
                'average_rating' => 0,
                'total_ratings' => 0,
                'rating_breakdown' => [],
            ];
        }
    }

    public function getUserRating(int $productId): ?array
    {
        $userId = Auth::id();
        
        if (!$userId) {
            throw new Exception('User must be authenticated', 401);
        }

        $rating = $this->ratingRepository->getUserRating($userId, $productId);

        return $rating ? [
            'rating' => $rating->rating,
            'comment' => $rating->comment,
            'date' => $rating->created_at->format('M d, Y'),
        ] : null;
    }

    public function getPaginatedRatings(int $productId, int $perPage = 5, int $page = 1): array
    {
        // Verify product variant exists
        // $this->ratingRepository->findProduct($productId);
        
        $ratings = $this->ratingRepository->getPaginatedRatings($productId, $perPage, $page);

        // Format the ratings data
        $formattedRatings = $ratings->getCollection()->map(function ($rating) {
            return [
                'id' => $rating->id,
                'rating' => (float) $rating->rating,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at,
                'user' => [
                    'id' => $rating->user->id,
                    'full_name' => $rating->user->full_name,
                ],
            ];
        });

        return [
            'ratings' => $formattedRatings,
            'pagination' => [
                'current_page' => $ratings->currentPage(),
                'last_page' => $ratings->lastPage(),
                'per_page' => $ratings->perPage(),
                'total' => $ratings->total(),
                'from' => $ratings->firstItem(),
                'to' => $ratings->lastItem(),
            ],
        ];
    }
}
