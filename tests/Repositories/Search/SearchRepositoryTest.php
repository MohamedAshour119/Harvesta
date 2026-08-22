<?php

namespace Tests\Repositories\Search;

use App\Http\Repositories\SearchRepository;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use Tests\Support\RepositoryTestCase;

class SearchRepositoryTest extends RepositoryTestCase
{
    private SearchRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(SearchRepository::class);
    }

    public function test_search_returns_products_with_variants_and_prioritizes_prefix_matches(): void
    {
        $prefix = $this->createSearchableProduct('Tea Premium', 'tea-premium');
        $contains = $this->createSearchableProduct('Herbal Tea Mix', 'herbal-tea');
        $ignoredWithoutVariants = Product::factory()->create([
            'name' => ['en' => 'Tea Base', 'ar' => 'Tea Base'],
            'description' => ['en' => 'No variant', 'ar' => 'No variant'],
            'status' => 'approved',
            'slug' => 'tea-base',
        ]);

        $results = $this->repository->search('tea');

        $this->assertSame([$prefix->id, $contains->id], $results->pluck('id')->all());
        $this->assertFalse($results->contains('id', $ignoredWithoutVariants->id));
    }

    public function test_search_keyword_returns_paginated_prefix_matches_with_loaded_categories(): void
    {
        $category = IndustryCategory::query()->create([
            'name' => ['en' => 'Beverages', 'ar' => 'مشروبات'],
        ]);
        $match = $this->createSearchableProduct('Coffee Beans', 'coffee-beans', $category);
        $other = $this->createSearchableProduct('Cold Brew', 'cold-brew', $category);

        $results = $this->repository->searchKeyword('co');

        $this->assertEqualsCanonicalizing([$match->id, $other->id], collect($results->items())->pluck('id')->all());
        $this->assertTrue($results->getCollection()->every(fn (Product $product): bool => $product->relationLoaded('industrialCategory')));
    }

    private function createSearchableProduct(string $englishName, string $slug, ?IndustryCategory $category = null): Product
    {
        $product = Product::factory()->create([
            'industry_category_id' => $category?->id,
            'name' => ['en' => $englishName, 'ar' => $englishName],
            'description' => ['en' => "{$englishName} description", 'ar' => "{$englishName} description"],
            'status' => 'approved',
            'slug' => $slug,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        return $product;
    }
}
