<?php

namespace App\Http\Repositories;

use App\Models\IndustryCategory;
use App\Models\Product;

class CartRepository
{

    public function getProductsByIds($productIds)
    {
        $productIdsOnly = array_column($productIds, 'productId');
        $productVariantIds = array_column($productIds, 'productVariantId');

        return Product::whereIn('id', $productIdsOnly)
            ->select(['id', 'name', 'description', 'industry_category_id'])
            ->with([
                'variants' => function ($query) use ($productVariantIds) {
                    $query->whereIn('id', $productVariantIds);
                },
                'industrialCategory'
            ])
            ->get();
    }
}