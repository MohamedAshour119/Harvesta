<?php

namespace App\Http\Services;

use App\Helper\Helper;
use App\Http\Repositories\ShopPageRepository;
use App\Http\Resources\IndustrialCategoryResource;
use App\Http\Resources\ProductResource;

class ShopService
{
    public function __construct(
        protected ShopPageRepository $shopPageRepository,
        protected SemanticSearchService $semanticSearchService
    ) {}

    // public function index()
    // {
    //     $results = $this->shopPageRepository->index();
    //     $industrial_categories = $results['industrial_categories']->map(function ($industrial_category) {
    //         return new IndustrialCategoryResource($industrial_category, false);
    //     });

    //     $products = $results['products']->map(function ($product) {
    //         return new ProductResource($product, 'product_card', true);
    //     });

    //     return [
    //         'industrial_categories' => $industrial_categories,
    //         'products' => $products,
    //     ];
    // }

    public function filterProducts($filter, $minPrice = null, $maxPrice = null, $searchQuery = null)
    {
        $semanticIds = [];
        if ($searchQuery) {
            try {
                // Get a reasonable number of semantic matches for filtering
                $semanticProducts = $this->semanticSearchService->search($searchQuery, 100);
                $semanticIds = collect($semanticProducts)->pluck('id')->toArray();
            } catch (\Exception $e) {
                // Ignore semantic errors in background
            }
        }

        $results = $this->shopPageRepository->filterProducts($filter, $minPrice, $maxPrice, $searchQuery, $semanticIds);
        
        // Use the 'through' method to transform items while preserving pagination
        $products = $results['products']->through(function ($product) {
            return new ProductResource($product, 'product_card', true);
        });

        // Append query parameters to pagination links
        $products->appends(request()->query());

        $industrial_categories = [];
        if ($results['industrial_categories']) {
            $industrial_categories = $results['industrial_categories']->map(function ($industrial_category) {
                return new IndustrialCategoryResource($industrial_category, false);
            });
        }

        $data = [
            'products' => $products,
        ];

        if ($industrial_categories) {
            $data['industrial_categories'] = $industrial_categories;
        }

        return $data;
    }

    public function getProductsByCategoryName ($categoryName)
    {
        $results = $this->shopPageRepository->getProductsByCategoryName($categoryName);
        $pagination = Helper::preparePagination($results);
        $products = ProductResource::collection($results);

        return [
            'products' => $products,
            'pagination' => $pagination,
        ];
    }
}
