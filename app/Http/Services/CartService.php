<?php

namespace App\Http\Services;

use App\Http\Repositories\CartRepository;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CartService
{
    protected $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function getProductsByIds(array $productIds)
    {
        $result = $this->cartRepository->getProductsByIds($productIds);

        if ($result->isEmpty() && !empty($productIds)) {
            return [
                'success' => false,
                'message' => 'No products found for the provided IDs.',
                'data'    => [],
                'status_code' => 404
            ];
        }

        $sortedProducts = $result->sortBy(function ($product) use ($productIds) {
            return array_search($product->id, $productIds);
        })->values();
        
        // Re-apply resource collection to the sorted products
        $productsResource = $sortedProducts->map(function ($item) {
            return new ProductResource($item, null, true);
        });        

        // $productsResource = ProductResource::collection($sortedProducts);

        return [
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data'    => $productsResource,
            'status_code' => 200
        ];
    }
}
