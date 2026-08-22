<?php

namespace Tests\Services\Unit\Shop;

use App\Http\Repositories\ShopPageRepository;
use App\Http\Services\SemanticSearchService;
use App\Http\Services\ShopService;
use App\Models\IndustryCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Support\ServiceSmokeTestCase;

class ShopServiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ShopService::class;
    }

    public function test_filter_products_passes_semantic_ids_to_repository_and_wraps_results(): void
    {
        $product = Product::query()->create([
            'name' => ['en' => 'Premium Tea', 'ar' => 'Premium Tea'],
            'description' => ['en' => 'Tea', 'ar' => 'Tea'],
            'status' => 'approved',
        ]);

        $category = new IndustryCategory([
            'id' => 5,
            'name' => ['en' => 'Beverages'],
            'slug' => 'beverages',
        ]);

        $paginator = new LengthAwarePaginator(
            collect([$product]),
            1,
            10,
            1,
            ['path' => 'http://localhost/shop']
        );

        $semanticSearch = $this->mockDependency(SemanticSearchService::class);
        $semanticSearch->shouldReceive('search')
            ->once()
            ->with('tea', 100)
            ->andReturn([
                ['id' => 7],
                ['id' => 9],
            ]);

        $repository = $this->mockDependency(ShopPageRepository::class);
        $repository->shouldReceive('filterProducts')
            ->once()
            ->with('featured', 10, 100, 'tea', [7, 9])
            ->andReturn([
                'products' => $paginator,
                'industrial_categories' => collect([$category]),
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->filterProducts('featured', 10, 100, 'tea');

        $this->assertSame(1, $result['products']->count());
        $this->assertCount(1, $result['industrial_categories']);
    }
}
