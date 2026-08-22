<?php

namespace Tests\Repositories\Home;

use App\Http\Repositories\HomePageRepository;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use Tests\Support\RepositoryTestCase;

class HomePageRepositoryTest extends RepositoryTestCase
{
    private HomePageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(HomePageRepository::class);
    }

    public function test_index_returns_all_categories_and_top_categories_with_products_that_have_variants(): void
    {
        $topCategory = IndustryCategory::query()->create(['name' => ['en' => 'Top', 'ar' => 'الأعلى']]);
        $secondCategory = IndustryCategory::query()->create(['name' => ['en' => 'Second', 'ar' => 'الثاني']]);
        $emptyCategory = IndustryCategory::query()->create(['name' => ['en' => 'Empty', 'ar' => 'فارغ']]);

        foreach (range(1, 3) as $index) {
            $product = Product::factory()->create([
                'industry_category_id' => $topCategory->id,
                'status' => 'approved',
                'name' => ['en' => "Top {$index}", 'ar' => "Top {$index}"],
            ]);
            ProductVariant::factory()->create(['product_id' => $product->id]);
        }

        $secondProduct = Product::factory()->create([
            'industry_category_id' => $secondCategory->id,
            'status' => 'approved',
        ]);
        ProductVariant::factory()->create(['product_id' => $secondProduct->id]);

        Product::factory()->create([
            'industry_category_id' => $emptyCategory->id,
            'status' => 'approved',
        ]);

        $result = $this->repository->index();

        $this->assertCount(3, $result['industrial_categories']);
        $this->assertSame([$topCategory->id, $secondCategory->id], $result['top_categories_products']->pluck('category.id')->all());
        $this->assertCount(3, $result['top_categories_products'][0]['products']);
        $this->assertCount(1, $result['top_categories_products'][1]['products']);
    }
}
