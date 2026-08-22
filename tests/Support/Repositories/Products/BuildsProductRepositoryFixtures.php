<?php

namespace Tests\Support\Repositories\Products;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;

trait BuildsProductRepositoryFixtures
{
    /**
     * @return array{product: Product, variant: ProductVariant}
     */
    private function createProductWithVariant(string $englishName, string $slug, ?IndustryCategory $category = null): array
    {
        $product = Product::factory()->create([
            'industry_category_id' => $category?->id,
            'name' => ['en' => $englishName, 'ar' => $englishName],
            'description' => ['en' => "{$englishName} description", 'ar' => "{$englishName} description"],
            'status' => 'approved',
            'slug' => $slug,
        ]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        return ['product' => $product, 'variant' => $variant];
    }
}
