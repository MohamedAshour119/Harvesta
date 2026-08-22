<?php

namespace Tests\Repositories\Shop;

use App\Http\Repositories\ShopPageRepository;
use App\Models\IndustryCategory;
use App\Models\Product;
use Tests\Support\RepositoryTestCase;

class ShopPageRepositoryCategoryLookupTest extends RepositoryTestCase
{
    private ShopPageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ShopPageRepository::class);
    }

    public function test_get_products_by_category_name_returns_only_approved_products_with_variants(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Stationery', 'ar' => 'Ù‚Ø±Ø·Ø§Ø³ÙŠØ©'],
            'slug' => 'stationery',
        ]);

        $included = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
            'name' => ['en' => 'Marker', 'ar' => 'Marker'],
        ]);
        \App\Models\ProductVariant::factory()->create(['product_id' => $included->id]);

        Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'pending',
        ]);

        $excluded = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        $products = $this->repository->getProductsByCategoryName('Stationery');

        $this->assertSame([$included->id], collect($products->items())->pluck('id')->all());
        $this->assertFalse(collect($products->items())->contains('id', $excluded->id));
    }
}
