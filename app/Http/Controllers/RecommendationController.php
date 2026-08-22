<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\RecombeeService;
use App\Traits\HttpResponses;
use App\Models\Product;
use App\Http\Resources\ProductResource;

class RecommendationController extends Controller
{
    use HttpResponses;
    protected $recombeeService;

    public function __construct(RecombeeService $recombeeService)
    {
        $this->recombeeService = $recombeeService;
    }

    public function forUser(Request $request)
    {
        $count = $request->query('count', 10);
        $userId = $request->user()->id;

        $response = $this->recombeeService->recommendItemsToUser($userId, $count);
        $recommendedIds = collect($response['recomms'] ?? [])->pluck('id')->toArray();

        // Fetch products in the same order
        $products = [];
        if (!empty($recommendedIds)) {
            $productModels = Product::whereIn('id', $recommendedIds)
                ->with(['media', 'variants'])
                ->get()
                ->keyBy('id');
            foreach ($recommendedIds as $id) {
                if ($productModels->has($id)) {
                    $products[] = $productModels->get($id);
                }
            }
        }

        $formattedProducts = collect($products)->map(function ($product) {
            return new ProductResource($product);
        });

        return $this->response_success($formattedProducts, 'Recommendations retrieved.');
    }

    public function forItem(Request $request, $productId)
    {
        $count = $request->query('count', 10);
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;

        $response = $this->recombeeService->recommendItemsToItem($productId, $userId, $count);
        $recommendedIds = collect($response['recomms'] ?? [])->pluck('id')->toArray();

        $products = [];
        if (!empty($recommendedIds)) {
            $productModels = Product::whereIn('id', $recommendedIds)
                ->with(['media', 'variants'])
                ->get()
                ->keyBy('id');
            foreach ($recommendedIds as $id) {
                if ($productModels->has($id)) {
                    $products[] = $productModels->get($id);
                }
            }
        }

        $formattedProducts = collect($products)->map(function ($product) {
            return new ProductResource($product);
        });

        return $this->response_success($formattedProducts, 'Recommendations retrieved.');
    }
    public function frequentlyBoughtTogether(Request $request, $productId)
    {
        $count = $request->query('count', 3);
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;

        // Use explicit FBT logic (ecommerce:cross-sell is equivalent to FBT)
        // We pass null for userId to ensure consistent results for both authenticated and guest users
        $response = $this->recombeeService->recommendItemsToItem($productId, null, $count, [
            'logic' => 'ecommerce:cross-sell'
        ]);


        $recommendedIds = collect($response['recomms'] ?? [])->pluck('id')->toArray();

        $products = [];
        if (!empty($recommendedIds)) {
            $productModels = Product::whereIn('id', $recommendedIds)
                ->with(['media', 'variants'])
                ->get()
                ->keyBy('id');
            foreach ($recommendedIds as $id) {
                if ($productModels->has($id)) {
                    $products[] = $productModels->get($id);
                }
            }
        }

        $formattedProducts = collect($products)->map(function ($product) {
            return new ProductResource($product);
        });

        return $this->response_success($formattedProducts, 'FBT Recommendations retrieved.');
    }
}
