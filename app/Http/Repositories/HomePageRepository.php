<?php

namespace App\Http\Repositories;

use App\Http\Resources\UserResource;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\User;

class HomePageRepository
{
    public function index()
    {
        $industrialCategories = IndustryCategory::withCount(['products' => function ($query) {
                $query->where('status', 'approved')->has('variants');
            }])
            ->orderByDesc('products_count')
            ->orderBy('id', 'asc')
            ->take(8)
            ->get();

        // Get top 3 categories by product count (only products having variants)
        $topCategories = IndustryCategory::withCount(['products' => function ($query) {
                $query->has('variants');
            }])
            ->get()
            ->where('products_count', '>', 0)
            ->sortByDesc('products_count')
            ->take(3)
            ->values();

        $topProductsByCategory = $topCategories->map(function (IndustryCategory $category) {
            return [
                'category' => $category,
                'products' => $category->products()
                    ->has('variants')
                    ->with(['variants', 'industrialCategory'])
                    ->take(10)
                    ->get()
            ];
        });

        return [
            'industrial_categories' => $industrialCategories,
            'top_categories_products' => $topProductsByCategory
        ];
    }

}
