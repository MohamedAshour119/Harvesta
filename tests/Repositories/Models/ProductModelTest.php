<?php

namespace Tests\Repositories\Models;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_and_updating_a_product_generates_a_slug_from_the_english_name(): void
    {
        $product = Product::query()->create([
            'name' => ['en' => 'Premium Desk Lamp', 'ar' => 'Premium Desk Lamp'],
            'description' => ['en' => 'Desk lamp', 'ar' => 'Desk lamp'],
            'status' => 'approved',
        ]);

        $this->assertSame('premium-desk-lamp', $product->slug);

        $product->update([
            'name' => ['en' => 'Reading Lamp Pro', 'ar' => 'Reading Lamp Pro'],
        ]);

        $this->assertSame('reading-lamp-pro', $product->fresh()->slug);
    }

    public function test_creating_a_product_without_english_text_falls_back_to_an_id_based_slug(): void
    {
        $product = Product::query()->create([
            'name' => ['en' => 'مرحبا', 'ar' => 'مرحبا'],
            'description' => ['en' => 'Fallback slug case', 'ar' => 'Fallback slug case'],
            'status' => 'approved',
        ]);

        $this->assertSame('product-' . $product->id, $product->slug);
    }

    public function test_minimum_order_value_helpers_respect_agent_specific_pricing(): void
    {
        $product = Product::query()->create([
            'name' => ['en' => 'Bulk Cleaner', 'ar' => 'Bulk Cleaner'],
            'description' => ['en' => 'Cleaner', 'ar' => 'Cleaner'],
            'min_order_value' => 150,
            'agent_min_order_value' => 90,
            'status' => 'approved',
        ]);

        $agent = User::factory()->create(['is_agent' => true]);
        $customer = User::factory()->create(['is_agent' => false]);

        $this->assertSame(90.0, $product->resolveAgentMinOrderValue());
        $this->assertSame(90.0, $product->resolveSiteMinOrderValue($agent));
        $this->assertSame(150.0, $product->resolveSiteMinOrderValue($customer));

        $product->agent_min_order_value = null;

        $this->assertSame(150.0, $product->resolveAgentMinOrderValue());
    }
}
