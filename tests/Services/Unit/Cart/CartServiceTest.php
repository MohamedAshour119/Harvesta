<?php

namespace Tests\Services\Unit\Cart;

use App\Http\Repositories\CartRepository;
use App\Http\Services\CartService;
use App\Models\Product;
use Illuminate\Support\Collection;
use Tests\Support\ServiceSmokeTestCase;

class CartServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return CartService::class;
    }

    public function test_get_products_by_ids_returns_not_found_payload_when_repository_is_empty(): void
    {
        $repository = $this->mockDependency(CartRepository::class);
        $repository->shouldReceive('getProductsByIds')
            ->once()
            ->with([1, 2])
            ->andReturn(new Collection());

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->getProductsByIds([1, 2]);

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['status_code']);
        $this->assertSame([], $result['data']);
    }

    public function test_get_products_by_ids_preserves_requested_order_in_resources(): void
    {
        $first = new Product(['id' => 1, 'name' => ['en' => 'First']]);
        $second = new Product(['id' => 2, 'name' => ['en' => 'Second']]);

        $repository = $this->mockDependency(CartRepository::class);
        $repository->shouldReceive('getProductsByIds')
            ->once()
            ->with([1, 2])
            ->andReturn(collect([$second, $first]));

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->getProductsByIds([1, 2]);

        $this->assertTrue($result['success']);
        $this->assertSame([1, 2], $result['data']->map(fn ($resource) => $resource->resource->id)->all());
    }
}
