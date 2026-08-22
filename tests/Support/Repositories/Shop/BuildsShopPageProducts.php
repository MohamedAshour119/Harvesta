<?php

namespace Tests\Support\Repositories\Shop;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;

trait BuildsShopPageProducts
{
    /**
     * @return array{product: Product, variant: ProductVariant}
     */
    private function createShopProduct(IndustryCategory $category, string $name, string $slug, array $variantOverrides = []): array
    {
        $product = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
            'name' => ['en' => $name, 'ar' => $name],
            'description' => ['en' => "{$name} description", 'ar' => "{$name} description"],
            'slug' => $slug,
        ]);

        $variant = ProductVariant::factory()->create(array_merge([
            'product_id' => $product->id,
            'agent_price' => 30,
            'unit_price_usd' => 40,
            'availability' => 'in-stock',
        ], $variantOverrides));

        return ['product' => $product, 'variant' => $variant];
    }
}
