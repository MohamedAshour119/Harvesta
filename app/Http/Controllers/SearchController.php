<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Services\CatalogSearchService;
use App\Http\Services\SearchService;
use App\Http\Services\SemanticSearchService;
use App\Models\IndustryCategory;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use HttpResponses;

    protected SearchService $searchService;
    protected SemanticSearchService $semanticSearchService;
    protected CatalogSearchService $catalogSearchService;

    public function __construct(
        SearchService $searchService,
        SemanticSearchService $semanticSearchService,
        CatalogSearchService $catalogSearchService
    ) {
        $this->searchService = $searchService;
        $this->semanticSearchService = $semanticSearchService;
        $this->catalogSearchService = $catalogSearchService;
    }

    public function search(Request $request)
    {
        try {
            $results = $this->searchService->search($request->keyword);

            return $this->response_success($results, 'Search results');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 404);
        }
    }

    public function searchKeyword($keyword)
    {
        try {
            $results = $this->searchService->searchKeyword($keyword);

            return $this->response_success($results, 'Search results for custom keyword');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 404);
        }
    }

    public function semanticSearch(Request $request)
    {
        try {
            $query = $request->input('query');

            if (empty($query)) {
                return $this->response_error('Query is required', [], 422);
            }

            $cacheKey = 'semantic_search_api:v2:' . md5(trim(mb_strtolower($query)));
            $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query) {
                $searchResults = $this->catalogSearchService->search($query, 7, 8);
                $literalModels = $searchResults['literal'];
                $semanticModels = $searchResults['semantic'];
                $literalResults = ProductResource::collection($literalModels);
                $formattedSemantic = ProductResource::collection($semanticModels);

                $allResultModels = $literalModels->concat($semanticModels);

                $categoryCounts = $allResultModels->whereNotNull('industry_category_id')
                    ->groupBy('industry_category_id')
                    ->map(fn ($group) => $group->count());

                $categoryIds = $categoryCounts->keys()->toArray();

                $relevantCategories = IndustryCategory::whereIn('id', $categoryIds)
                    ->get()
                    ->map(function ($category) use ($categoryCounts) {
                        $category->setAttribute('products_count', $categoryCounts->get($category->id, 0));

                        return $category;
                    });

                return [
                    'literal_results' => $literalResults,
                    'semantic_results' => $formattedSemantic,
                    'has_semantic' => $formattedSemantic->count() > 0,
                    'industrial_categories' => $relevantCategories,
                ];
            });

            return $this->response_success($data, 'Search results');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }

    public function visualSearch(Request $request)
    {
        try {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageSource = 'data:' . $image->getMimeType() . ';base64,' . base64_encode(file_get_contents($image->getRealPath()));
            } elseif ($request->input('image_base64')) {
                $imageSource = $request->input('image_base64');
            } else {
                return $this->response_error('Image is required', [], 422);
            }

            $visualProducts = $this->semanticSearchService->searchByImage($imageSource, 12);

            if (empty($visualProducts)) {
                return $this->response_success([
                    'visual_results' => [],
                    'industrial_categories' => [],
                ], 'No visually similar products found');
            }

            $productIds = collect($visualProducts)->pluck('id')->toArray();
            $visualModels = \App\Models\Product::where('status', 'approved')
                ->with(['variants', 'industrialCategory'])
                ->whereIn('id', $productIds)
                ->get()
                ->sortBy(function ($product) use ($productIds) {
                    return array_search($product->id, $productIds);
                })
                ->values();

            $formattedResults = ProductResource::collection($visualModels);

            $categoryCounts = $visualModels->whereNotNull('industry_category_id')
                ->groupBy('industry_category_id')
                ->map(fn ($group) => $group->count());

            $categoryIds = $categoryCounts->keys()->toArray();

            $relevantCategories = IndustryCategory::whereIn('id', $categoryIds)
                ->get()
                ->map(function ($category) use ($categoryCounts) {
                    $category->setAttribute('products_count', $categoryCounts->get($category->id, 0));

                    return $category;
                });

            return $this->response_success([
                'visual_results' => $formattedResults,
                'industrial_categories' => $relevantCategories,
            ], 'Visual search results');
        } catch (\Exception $e) {
            return $this->response_error($e->getMessage(), [], 500);
        }
    }
}
