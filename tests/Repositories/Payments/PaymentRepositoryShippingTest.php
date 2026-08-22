<?php

namespace Tests\Repositories\Payments;

use App\Http\Repositories\PaymentRepository;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingDestination;
use Tests\Support\RepositoryTestCase;

class PaymentRepositoryShippingTest extends RepositoryTestCase
{
    private PaymentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(PaymentRepository::class);
    }

    public function test_get_shipping_cost_uses_city_rate_and_variant_dimensions(): void
    {
        ShippingDestination::query()->create([
            'city' => ['en' => 'Cairo', 'ar' => 'Ø§Ù„Ù‚Ø§Ù‡Ø±Ø©'],
            'rate_per_kg' => 8,
            'rate_per_volume_unit' => 11,
        ]);

        $product = Product::factory()->create(['status' => 'approved']);
        $dimensionless = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'weight' => 0,
            'volume' => 0,
        ]);
        $sized = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'weight' => 0.5,
            'volume' => 2,
        ]);

        $total = $this->repository->getShippingCost([
            'city' => 'Cairo',
            'items' => [
                ['productVariantId' => $dimensionless->id, 'quantity' => 2],
                ['productVariantId' => $sized->id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(38.0, $total);
    }

    public function test_get_shipping_cost_throws_when_city_is_missing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('City not found');

        $this->repository->getShippingCost([
            'city' => 'Unknown',
            'items' => [],
        ]);
    }
}
