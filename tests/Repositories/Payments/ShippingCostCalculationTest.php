<?php

namespace Tests\Repositories\Payments;

use App\Http\Repositories\PaymentRepository;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingDestination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingCostCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_cost_falls_back_to_city_rate_when_variant_dimensions_are_missing(): void
    {
        $destination = ShippingDestination::query()->create([
            'city' => [
                'en' => 'Cairo',
                'ar' => 'القاهرة',
            ],
            'rate_per_kg' => 8.5,
            'rate_per_volume_unit' => 11,
        ]);

        $product = Product::factory()->create([
            'status' => 'approved',
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'weight' => 0,
            'volume' => 0,
        ]);

        $shippingCost = app(PaymentRepository::class)->getShippingCost([
            'city' => 'Cairo',
            'items' => [
                [
                    'productVariantId' => $variant->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $this->assertSame(17.0, $shippingCost);
    }

    public function test_shipping_cost_prefers_the_larger_dimension_based_charge_when_dimensions_exist(): void
    {
        ShippingDestination::query()->create([
            'city' => [
                'en' => 'Giza',
                'ar' => 'الجيزة',
            ],
            'rate_per_kg' => 9,
            'rate_per_volume_unit' => 12,
        ]);

        $product = Product::factory()->create([
            'status' => 'approved',
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'weight' => 0.5,
            'volume' => 2,
        ]);

        $shippingCost = app(PaymentRepository::class)->getShippingCost([
            'city' => 'Giza',
            'items' => [
                [
                    'productVariantId' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $this->assertSame(24.0, $shippingCost);
    }
}
