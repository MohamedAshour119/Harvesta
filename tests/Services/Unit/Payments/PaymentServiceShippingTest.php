<?php

namespace Tests\Services\Unit\Payments;

use App\Http\Repositories\PaymentRepository;
use App\Http\Services\PaymentService;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class PaymentServiceShippingTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return PaymentService::class;
    }

    public function test_get_shipping_cost_delegates_to_repository(): void
    {
        $payload = ['city' => 'Cairo', 'items' => []];
        $repository = $this->mockDependency(PaymentRepository::class);
        $repository->shouldReceive('getShippingCost')
            ->once()
            ->with($payload)
            ->andReturn(32.5);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame(32.5, $service->getShippingCost($payload));
    }

    public function test_ensure_order_minimum_value_checks_total_variant_amount_against_threshold(): void
    {
        $product = Product::factory()->create();
        $belowThreshold = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_after_discount' => 20,
        ]);
        $atThreshold = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_after_discount' => 25,
        ]);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertFalse($service->ensureOrderMinimumValue([
            ['productVariantId' => $belowThreshold->id, 'quantity' => 2],
        ]));
        $this->assertTrue($service->ensureOrderMinimumValue([
            ['productVariantId' => $atThreshold->id, 'quantity' => 2],
        ]));
    }
}
