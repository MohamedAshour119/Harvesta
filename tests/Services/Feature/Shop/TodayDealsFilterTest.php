<?php

namespace Tests\Services\Feature\Shop;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayDealsFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_deals_only_returns_products_with_discount_applied_today(): void
    {
        $category = IndustryCategory::factory()->create([
            'name' => [
                'en' => 'Oil',
                'ar' => 'زيت',
            ],
            'slug' => 'oil',
        ]);

        $todayDealProduct = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        ProductVariant::factory()->create([
            'product_id' => $todayDealProduct->id,
            'variant_type_id' => null,
            'discount_percentage' => 15,
            'price_after_discount' => 85,
            'discount_applied_at' => now(),
        ]);

        $oldDiscountProduct = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        ProductVariant::factory()->create([
            'product_id' => $oldDiscountProduct->id,
            'variant_type_id' => null,
            'discount_percentage' => 20,
            'price_after_discount' => 80,
            'discount_applied_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/shop/filters?categories=today-deals');

        $response->assertOk();

        $productIds = collect($response->json('data.products.data'))->pluck('id')->values();

        $this->assertTrue($productIds->contains($todayDealProduct->id));
        $this->assertFalse($productIds->contains($oldDiscountProduct->id));
    }

    public function test_changing_non_discount_fields_does_not_make_old_discount_a_today_deal(): void
    {
        $category = IndustryCategory::factory()->create([
            'name' => [
                'en' => 'Tea',
                'ar' => 'شاي',
            ],
            'slug' => 'tea',
        ]);

        $product = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_type_id' => null,
            'discount_percentage' => 10,
            'price_after_discount' => 90,
            'discount_applied_at' => now()->subDay(),
        ]);

        $variant->update([
            'variant_value' => 'Updated size',
            'unit_price_usd' => 120,
            'price_after_discount' => 108,
        ]);

        $response = $this->getJson('/api/shop/filters?categories=today-deals');

        $response->assertOk();

        $productIds = collect($response->json('data.products.data'))->pluck('id')->values();

        $this->assertFalse($productIds->contains($product->id));
        $this->assertTrue($variant->fresh()->discount_applied_at?->isSameDay(now()->subDay()));
    }
}
