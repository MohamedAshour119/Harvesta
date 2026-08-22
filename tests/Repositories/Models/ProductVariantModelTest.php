<?php

namespace Tests\Repositories\Models;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantModelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_creating_a_variant_fills_agent_price_and_sets_discount_timestamp(): void
    {
        Carbon::setTestNow('2026-04-19 10:00:00');
        $product = $this->createProduct();

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'variant_value' => '1 L',
            'unit_price_usd' => 100,
            'agent_price' => null,
            'discount_percentage' => 15,
            'price_after_discount' => 85,
            'availability' => 'in-stock',
        ]);

        $this->assertSame(100.0, $variant->fresh()->agent_price);
        $this->assertTrue($variant->fresh()->discount_applied_at->equalTo(now()));
    }

    public function test_updating_discount_percentage_to_zero_clears_the_discount_timestamp(): void
    {
        $product = $this->createProduct();

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'variant_value' => '1 L',
            'unit_price_usd' => 100,
            'agent_price' => 80,
            'discount_percentage' => 10,
            'price_after_discount' => 90,
            'availability' => 'in-stock',
        ]);

        $variant->update([
            'discount_percentage' => 0,
        ]);

        $this->assertNull($variant->fresh()->discount_applied_at);
    }

    public function test_stock_availability_accessor_and_mutator_proxy_the_availability_field(): void
    {
        $product = $this->createProduct();

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'variant_value' => '1 L',
            'unit_price_usd' => 50,
            'availability' => 'out-of-stock',
        ]);

        $this->assertSame('out-of-stock', $variant->stock_availability);

        $variant->stock_availability = 'in-stock';

        $this->assertSame('in-stock', $variant->availability);
    }

    public function test_site_price_helpers_switch_between_customer_and_agent_prices(): void
    {
        $product = $this->createProduct();

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'variant_value' => '1 L',
            'unit_price_usd' => 100,
            'agent_price' => 70,
            'discount_percentage' => 20,
            'price_after_discount' => 80,
            'availability' => 'in-stock',
        ]);

        $agent = User::factory()->create(['is_agent' => true]);
        $customer = User::factory()->create(['is_agent' => false]);

        $this->assertSame(70.0, $variant->resolveAgentPrice());
        $this->assertSame(100.0, $variant->resolveAgentPriceFallback());
        $this->assertTrue($variant->shouldUseAgentPriceFor($agent));
        $this->assertFalse($variant->shouldUseAgentPriceFor($customer));
        $this->assertSame(100.0, $variant->resolveSiteOriginalPrice($customer));
        $this->assertSame(80.0, $variant->resolveSiteDisplayPrice($customer));
        $this->assertSame(20.0, $variant->resolveSiteDiscountPercentage($customer));
        $this->assertSame(100.0, $variant->resolveSiteComparablePrice($customer));
        $this->assertSame(70.0, $variant->resolveSiteOriginalPrice($agent));
        $this->assertSame(70.0, $variant->resolveSiteDisplayPrice($agent));
        $this->assertSame(0.0, $variant->resolveSiteDiscountPercentage($agent));
        $this->assertSame(70.0, $variant->resolveSiteComparablePrice($agent));
    }

    private function createProduct(): Product
    {
        return Product::query()->create([
            'name' => ['en' => 'Variant Parent', 'ar' => 'Variant Parent'],
            'description' => ['en' => 'Parent product', 'ar' => 'Parent product'],
            'status' => 'approved',
        ]);
    }
}
