<?php

namespace Tests\Services\Unit\Chat;

use App\Http\Services\CatalogSearchService;
use App\Http\Services\ChatbotContextBuilder;
use App\Http\Services\ChatbotIntentDetector;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingDestination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class ChatbotContextBuilderTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ChatbotContextBuilder::class;
    }

    public function test_build_system_prompt_for_greetings_keeps_the_prompt_light(): void
    {
        $intentDetector = $this->mockDependency(ChatbotIntentDetector::class);
        $catalogSearch = $this->mockDependency(CatalogSearchService::class);

        $intentDetector->shouldReceive('detect')
            ->once()
            ->with('Hello there')
            ->andReturn('greeting');
        $catalogSearch->shouldReceive('search')
            ->once()
            ->with('Hello there', 3, 3)
            ->andReturn([
                'literal' => collect(),
                'semantic' => collect(),
                'combined' => collect(),
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $prompt = $service->buildSystemPrompt('Hello there');

        $this->assertStringContainsString('CURRENT INTENT: Greeting', $prompt);
        $this->assertStringContainsString('DIRECT PRODUCT MATCHES:', $prompt);
        $this->assertStringContainsString('None', $prompt);
        $this->assertStringContainsString('(No product search performed - this is a greeting)', $prompt);
        $this->assertStringNotContainsString('SHIPPING INFORMATION:', $prompt);
    }

    public function test_build_system_prompt_includes_product_shipping_memory_and_profile_context(): void
    {
        $product = $this->createProductWithInventory();
        $searchProduct = $product->fresh(['variants.inventoryProducts', 'industrialCategory']);

        ShippingDestination::create([
            'city' => ['en' => 'Cairo', 'ar' => 'القاهرة'],
            'rate_per_kg' => 12.5,
            'rate_per_volume_unit' => 8.0,
        ]);

        $intentDetector = $this->mockDependency(ChatbotIntentDetector::class);
        $catalogSearch = $this->mockDependency(CatalogSearchService::class);

        $intentDetector->shouldReceive('detect')
            ->once()
            ->with('What is shipping for cleaner?')
            ->andReturn('shipping_inquiry');
        $catalogSearch->shouldReceive('search')
            ->once()
            ->with('What is shipping for cleaner?', 3, 3)
            ->andReturn([
                'literal' => collect([$searchProduct]),
                'semantic' => collect(),
                'combined' => collect([$searchProduct]),
            ]);
        $catalogSearch->shouldReceive('search')
            ->once()
            ->with('What is shipping for cleaner?', 7, 8)
            ->andReturn([
                'literal' => collect([$searchProduct]),
                'semantic' => collect(),
                'combined' => collect([$searchProduct]),
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $prompt = $service->buildSystemPrompt(
            'What is shipping for cleaner?',
            $product->slug,
            'en',
            'Earlier we discussed bulk pricing.',
            [$product->slug],
            [
                'name' => 'Sara',
                'total_orders' => 2,
                'last_order_date' => '2026-04-01',
                'total_spent' => 120,
                'frequently_ordered_products' => ['Industrial Cleaner'],
            ]
        );

        $this->assertStringContainsString('CURRENTLY VIEWING PRODUCT', $prompt);
        $this->assertStringContainsString('DIRECT PRODUCT MATCHES', $prompt);
        $this->assertStringContainsString('Industrial Cleaner', $prompt);
        $this->assertStringContainsString('/products/industrial-cleaner', $prompt);
        $this->assertStringContainsString('SHIPPING INFORMATION:', $prompt);
        $this->assertStringContainsString('- Cairo: 12.5 EGP/KG', $prompt);
        $this->assertStringContainsString('CONVERSATION MEMORY', $prompt);
        $this->assertStringContainsString('Earlier we discussed bulk pricing.', $prompt);
        $this->assertStringContainsString('CUSTOMER PROFILE:', $prompt);
        $this->assertStringContainsString('Name: Sara', $prompt);
        $this->assertStringContainsString('TOTAL PRODUCTS FOUND: 1', $prompt);
    }

    public function test_detect_intent_delegates_to_the_detector_service(): void
    {
        $intentDetector = $this->mockDependency(ChatbotIntentDetector::class);
        $intentDetector->shouldReceive('detect')
            ->once()
            ->with('Need pricing')
            ->andReturn('purchase_intent');

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame('purchase_intent', $service->detectIntent('Need pricing'));
    }

    private function createProductWithInventory(): Product
    {
        $product = Product::factory()->create([
            'slug' => 'industrial-cleaner',
            'status' => 'approved',
            'name' => [
                'en' => 'Industrial Cleaner',
                'ar' => 'منظف صناعي',
            ],
            'description' => [
                'en' => 'A concentrated industrial cleaner for warehouses.',
                'ar' => 'منظف صناعي مركز للمخازن.',
            ],
            'minimum_order_quantity' => 3,
            'min_order_value' => 250,
            'agent_min_order_value' => 200,
            'oem' => true,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variant_value' => '25kg',
            'availability' => 'in-stock',
            'unit_price_usd' => 120,
            'price_after_discount' => 110,
            'agent_price' => 95,
        ]);
        $inventory = Inventory::create([
            'name' => 'Chatbot Warehouse',
            'maximum_quantity' => 100,
            'location' => 'Cairo',
            'status' => 'normal-stock',
        ]);
        InventoryProduct::create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'quantity' => 7,
        ]);

        return $product;
    }
}
