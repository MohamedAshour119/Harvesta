<?php

namespace App\Http\Controllers;

use App\Http\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    public function __construct(protected RatingService $ratingService) {}

    /**
     * Submit a rating for a product variant
     */
    public function submitRating(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rating' => 'nullable|numeric|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->ratingService->submitRating(
                $request->product_id,
                $request->rating,
                $request->comment
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'rating' => $result['rating'],
                    'stats' => $result['stats'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get all ratings for a specific product variant
     */
    public function getProductRatings(int $productId): JsonResponse
    {
        try {
            $result = $this->ratingService->getProductRatings($productId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ratings',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get rating statistics for a product variant
     */
    public function getProductStats(int $productId): JsonResponse
    {
        try {
            $stats = $this->ratingService->getProductStats($productId);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rating statistics',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get user's rating for a specific variant (if exists)
     */
    public function getUserRating(int $productId): JsonResponse
    {
        try {
            $userRating = $this->ratingService->getUserRating($productId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_rating' => $userRating,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user rating',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get paginated ratings for a product variant
     */
    public function getPaginatedRatings(Request $request, int $productId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $perPage = $request->get('per_page', 5);
            $page = $request->get('page', 1);

            $result = $this->ratingService->getPaginatedRatings($productId, $perPage, $page);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ratings',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}
