<?php

namespace Tests\Services\Unit\Chat;

use App\Http\Services\ChatProductLinker;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\ServiceSmokeTestCase;

class ChatProductLinkerTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function serviceClass(): string
    {
        return ChatProductLinker::class;
    }

    public function test_link_products_wraps_known_product_names_with_markdown_links(): void
    {
        $product = Product::query()->create([
            'name' => ['en' => 'Premium Tea', 'ar' => 'Premium Tea'],
            'description' => ['en' => 'Tea', 'ar' => 'Tea'],
            'slug' => 'premium-tea',
            'status' => 'approved',
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'variant_value' => '1 kg',
            'unit_price_usd' => 100,
        ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->linkProducts('Premium Tea is available for bulk orders.');

        $this->assertSame('[Premium Tea](/products/premium-tea) is available for bulk orders.', $result);
    }

    public function test_link_products_does_not_double_link_existing_markdown_links(): void
    {
        $product = Product::query()->create([
            'name' => ['en' => 'Premium Tea', 'ar' => 'Premium Tea'],
            'description' => ['en' => 'Tea', 'ar' => 'Tea'],
            'slug' => 'premium-tea',
            'status' => 'approved',
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'variant_value' => '1 kg',
            'unit_price_usd' => 100,
        ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->linkProducts('See [Premium Tea](/products/premium-tea) for details.');

        $this->assertSame('See [Premium Tea](/products/premium-tea) for details.', $result);
    }
}
