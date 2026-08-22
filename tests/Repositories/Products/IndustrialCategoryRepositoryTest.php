<?php

namespace Tests\Repositories\Products;

use App\Http\Repositories\IndustrialCategoryRepository;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use Tests\Support\RepositoryTestCase;

class IndustrialCategoryRepositoryTest extends RepositoryTestCase
{
    private IndustrialCategoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(IndustrialCategoryRepository::class);
    }

    public function test_index_counts_only_products_that_have_variants(): void
    {
        $category = IndustryCategory::query()->create(['name' => ['en' => 'Packaging', 'ar' => 'تعبئة']]);

        $withVariants = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);
        ProductVariant::factory()->create(['product_id' => $withVariants->id]);

        Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        $categories = $this->repository->index();

        $this->assertSame(1, $categories->firstWhere('id', $category->id)->products_count);
    }
}
