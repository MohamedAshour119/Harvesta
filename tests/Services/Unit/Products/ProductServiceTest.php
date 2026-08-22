<?php

namespace Tests\Services\Unit\Products;

use App\Http\Repositories\ProductRepository;
use App\Http\Resources\ProductResource;
use App\Http\Services\ProductService;
use App\Models\Product;
use Tests\Support\ServiceSmokeTestCase;

class ProductServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return ProductService::class;
    }

    public function test_get_product_by_slug_returns_null_when_repository_has_no_product(): void
    {
        $repository = $this->mockDependency(ProductRepository::class);
        $repository->shouldReceive('getProductBySlug')
            ->once()
            ->with('missing-product')
            ->andReturn([
                'product' => null,
                'similar_products' => collect(),
            ]);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertNull($service->getProductBySlug('missing-product'));
    }

    public function test_get_product_by_slug_wraps_product_and_similar_products_in_resources(): void
    {
        $product = new Product(['id' => 1, 'slug' => 'premium-tea', 'name' => ['en' => 'Premium Tea']]);
        $similar = new Product(['id' => 2, 'slug' => 'green-tea', 'name' => ['en' => 'Green Tea']]);

        $repository = $this->mockDependency(ProductRepository::class);
        $repository->shouldReceive('getProductBySlug')
            ->once()
            ->with('premium-tea')
            ->andReturn([
                'product' => $product,
                'similar_products' => collect([$similar]),
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->getProductBySlug('premium-tea');

        $this->assertInstanceOf(ProductResource::class, $result['product']);
        $this->assertSame(1, $result['product']->resource->id);
        $this->assertSame([2], $result['similar_products']->map(fn ($resource) => $resource->resource->id)->all());
    }
}
