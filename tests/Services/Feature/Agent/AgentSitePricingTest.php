<?php

namespace Tests\Services\Feature\Agent;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentSitePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_agents_see_agent_price_on_product_page(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-SITE-1',
        ]);

        $product = Product::factory()->create([
            'status' => 'approved',
            'slug' => 'agent-priced-product',
            'min_order_value' => 150,
            'agent_min_order_value' => 80,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_type_id' => null,
            'unit_price_usd' => 120.50,
            'price_after_discount' => 90.25,
            'discount_percentage' => 25,
            'agent_price' => 70.75,
        ]);

        Sanctum::actingAs($agent);

        $response = $this->getJson('/api/products/agent-priced-product');

        $response->assertOk()
            ->assertJsonPath('data.product.min_order_value', 80)
            ->assertJsonPath('data.product.agent_min_order_value', 80)
            ->assertJsonPath('data.product.cheapestVariant.unit_price_usd', 70.75)
            ->assertJsonPath('data.product.cheapestVariant.price_after_discount', 70.75)
            ->assertJsonPath('data.product.cheapestVariant.discount_percentage', 0)
            ->assertJsonPath('data.product.variants.0.unit_price_usd', 70.75)
            ->assertJsonPath('data.product.variants.0.price_after_discount', 70.75);
    }

    public function test_regular_users_keep_the_normal_product_price(): void
    {
        $product = Product::factory()->create([
            'status' => 'approved',
            'slug' => 'regular-priced-product',
            'min_order_value' => 150,
            'agent_min_order_value' => 80,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_type_id' => null,
            'unit_price_usd' => 120.50,
            'price_after_discount' => 90.25,
            'discount_percentage' => 25,
            'agent_price' => 70.75,
        ]);

        $response = $this->getJson('/api/products/regular-priced-product');

        $response->assertOk()
            ->assertJsonPath('data.product.min_order_value', 150)
            ->assertJsonPath('data.product.agent_min_order_value', 80)
            ->assertJsonPath('data.product.cheapestVariant.unit_price_usd', 120.5)
            ->assertJsonPath('data.product.cheapestVariant.price_after_discount', 90.25)
            ->assertJsonPath('data.product.cheapestVariant.discount_percentage', 25);
    }

    public function test_shop_price_filter_uses_agent_price_for_agents(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-SITE-2',
        ]);

        $category = IndustryCategory::factory()->create([
            'slug' => 'oil',
        ]);

        $matchingProduct = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        ProductVariant::factory()->create([
            'product_id' => $matchingProduct->id,
            'variant_type_id' => null,
            'unit_price_usd' => 120,
            'price_after_discount' => 100,
            'agent_price' => 20,
        ]);

        $excludedProduct = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
        ]);

        ProductVariant::factory()->create([
            'product_id' => $excludedProduct->id,
            'variant_type_id' => null,
            'unit_price_usd' => 20,
            'price_after_discount' => 20,
            'agent_price' => 60,
        ]);

        Sanctum::actingAs($agent);

        $response = $this->getJson('/api/shop/filters?min_price=15&max_price=25');

        $response->assertOk();

        $productIds = collect($response->json('data.products.data'))->pluck('id')->values();

        $this->assertTrue($productIds->contains($matchingProduct->id));
        $this->assertFalse($productIds->contains($excludedProduct->id));
    }
}
