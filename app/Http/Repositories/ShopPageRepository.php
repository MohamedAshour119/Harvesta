<?php

namespace App\Http\Repositories;

use App\Models\IndustryCategory;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShopPageRepository
{
    // public function index()
    // {
    //     $industrialCategories = IndustryCategory::withCount('products')->get();
    //     $products = Product::whereNotNull('industry_category_id')
    //         ->with(['industrialCategory', 'variants'])
    //         ->orderBy('created_at', 'asc')
    //         ->take(4)
    //         ->get();
    

    //     return [
    //         'industrial_categories' => $industrialCategories,
    //         'products'=> $products
    //     ];
    // }

    public function filterProducts($filter, $minPrice = null, $maxPrice = null, $searchQuery = null, $semanticIds = [])
    {
        $industrialCategories = [];
        $viewer = Auth::guard('sanctum')->user();
        $variantPriceColumn = $viewer?->is_agent ? 'agent_price' : 'unit_price_usd';
        $query = Product::where('status', 'approved')->has('variants');

        // Apply search query filtering if present
        if (($searchQuery !== null && $searchQuery !== '') || !empty($semanticIds)) {
            $keyword = $searchQuery ? strtolower($searchQuery) : null;

            $query->where(function ($q) use ($keyword, $semanticIds) {
                if ($keyword) {
                    $q->where(function($sub) use ($keyword) {
                        $sub->whereRaw(
                            'LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?',
                            ['%' . $keyword . '%']
                        )->orWhereRaw(
                            'LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?',
                            ['%' . $keyword . '%']
                        );
                    });
                }
                
                if (!empty($semanticIds)) {
                    $q->orWhereIn('id', $semanticIds);
                }
            });

            if ($keyword) {
                $query->orderByRaw(
                    'CASE
                        WHEN LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ? THEN 1
                        WHEN LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ? THEN 2
                        ELSE 3
                    END', [$keyword . '%', $keyword . '%']
                );
            }
        }

        // Apply price filtering using whereHas for product_variants
        if ($minPrice !== null || $maxPrice !== null) {
            $query->whereHas('variants', function ($variantQuery) use ($minPrice, $maxPrice, $variantPriceColumn) {
                if ($minPrice !== null) {
                    $variantQuery->where($variantPriceColumn, '>=', $minPrice);
                }
                if ($maxPrice !== null && $maxPrice > 0) { // maxPrice > 0 ensures it's a valid upper bound
                    $variantQuery->where($variantPriceColumn, '<=', $maxPrice);
                }
            });
        }

        $filters = is_string($filter) ? array_filter(explode('/', $filter)) : [];
        $knownSpecialFilters = ['newest', 'most-popular', 'all-discounts', 'today-deals', 'in-stock', 'out-of-stock'];
        
        $specialFilters = array_intersect($filters, $knownSpecialFilters);
        $potentialCategorySlugs = array_diff($filters, $knownSpecialFilters);

        $allCategorySlugs = [];
        foreach ($potentialCategorySlugs as $slugPart) {
            $allCategorySlugs = array_merge($allCategorySlugs, array_map('trim', explode(',', $slugPart)));
        }
        $allCategorySlugs = array_filter($allCategorySlugs);

        if (!empty($allCategorySlugs)) {
            $categoryIds = IndustryCategory::where(function ($query) use ($allCategorySlugs) {
                $query->whereIn('slug', $allCategorySlugs)
                      ->orWhereIn('name->en', $allCategorySlugs);
            })->pluck('id');
            
            if ($categoryIds->isEmpty()) {
                return [
                    'products' => new LengthAwarePaginator([], 0, 15),
                    'industrial_categories' => $industrialCategories
                ];
            }
            $query->whereIn('industry_category_id', $categoryIds);
        }

        if (in_array('all-discounts', $specialFilters)) {
            $query->whereHas('variants', function ($q) {
                $q->where('discount_percentage', '>', 0);
            });
        }

        if (in_array('today-deals', $specialFilters)) {
            $query->whereHas('variants', function ($q) {
                $q->where('discount_percentage', '>', 0)
                    ->whereDate('discount_applied_at', now()->toDateString());
            });
        }

        if (in_array('in-stock', $specialFilters)) {
            $query->whereHas('variants', function ($q) {
                $q->where('availability', 'in-stock');
            });
        }

        if (in_array('out-of-stock', $specialFilters)) {
            $query->whereHas('variants', function ($q) {
                $q->where('availability', 'out-of-stock');
            });
        }

        if (in_array('most-popular', $specialFilters)) {
            $query->withCount('orderItems as sales_count')
                  ->orderByDesc('sales_count')
                  ->orderByDesc('created_at');
        } elseif (in_array('newest', $specialFilters) || in_array('today-deals', $specialFilters) || in_array('all-discounts', $specialFilters)) {
            $query->orderBy('id', 'desc');
        }

        $query->with(['industrialCategory', 'variants' => function ($variantQuery) use ($specialFilters, $searchQuery, $variantPriceColumn) {
            if (in_array('all-discounts', $specialFilters)) {
                $variantQuery->where('discount_percentage', '>', 0);
            }
            if (in_array('today-deals', $specialFilters)) {
                $variantQuery->where('discount_percentage', '>', 0)
                    ->whereDate('discount_applied_at', now()->toDateString());
            }
            if (in_array('in-stock', $specialFilters)) {
                $variantQuery->where('availability', 'in-stock');
            }
            if (in_array('out-of-stock', $specialFilters)) {
                $variantQuery->where('availability', 'out-of-stock');
            }
            if ($searchQuery && (in_array('in-stock', $specialFilters) || in_array('out-of-stock', $specialFilters))) {
                $variantQuery->orderBy($variantPriceColumn, 'asc');
            }
        }]);

        $industrialCategoriesQuery = IndustryCategory::query();

        if ($searchQuery || !empty($semanticIds)) {
            $keyword = $searchQuery ? strtolower($searchQuery) : null;
            $industrialCategoriesQuery->whereHas('products', function ($q) use ($keyword, $semanticIds) {
                $q->where('status', 'approved')->has('variants')->where(function ($inner) use ($keyword, $semanticIds) {
                    if ($keyword) {
                        $inner->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?', ['%' . $keyword . '%'])
                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?', ['%' . $keyword . '%']);
                    }
                    if (!empty($semanticIds)) {
                        $inner->orWhereIn('id', $semanticIds);
                    }
                });
            })->withCount(['products' => function ($q) use ($keyword, $semanticIds) {
                $q->has('variants')->where(function ($inner) use ($keyword, $semanticIds) {
                    if ($keyword) {
                        $inner->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) LIKE ?', ['%' . $keyword . '%'])
                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.ar"))) LIKE ?', ['%' . $keyword . '%']);
                    }
                    if (!empty($semanticIds)) {
                        $inner->orWhereIn('id', $semanticIds);
                    }
                });
            }]);
        } else {
            $industrialCategoriesQuery->withCount(['products' => function ($q) {
                $q->where('status', 'approved')->has('variants');
            }]);
        }

        $industrialCategories = $industrialCategoriesQuery->orderBy('name')->get();

        $perPage = 15;
        $requestedPage = (int) request('page', 1);
        $total = (clone $query)->count();
        $lastPage = max((int) ceil($total / $perPage), 1);

        if ($requestedPage > $lastPage) {
            $products = $query->paginate($perPage, ['*'], 'page', 1);
        } else {
            $products = $query->paginate($perPage);
        }

        return [
            'products' => $products,
            'industrial_categories' => $industrialCategories
        ];
    }

    public function getProductsByCategoryName ($categoryName)
    {
        $category_id = IndustryCategory::where('name->en', $categoryName)->first()?->id;
        $products = Product::where('status', 'approved')->has('variants')->where('industry_category_id', $category_id)->with('industrialCategory')->paginate(16);

        return $products;
    }
}
