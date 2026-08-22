<?php

namespace Tests\Services\Unit\Chat;

use App\Http\Services\CatalogSearchService;
use App\Http\Services\ChatProductResponseGuard;
use App\Http\Services\ChatbotIntentDetector;
use App\Models\InventoryProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class ChatProductResponseGuardTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_enforce_returns_original_response_for_non_catalog_intent(): void
    {
        $catalogSearch = Mockery::mock(CatalogSearchService::class);
        $catalogSearch->shouldNotReceive('search');

        $intentDetector = Mockery::mock(ChatbotIntentDetector::class);
        $intentDetector->shouldReceive('detect')
            ->once()
            ->with('Hello there')
            ->andReturn('small_talk');

        $guard = new ChatProductResponseGuard($catalogSearch, $intentDetector);

        $response = $guard->enforce('Hello there', 'Nice to meet you.');

        $this->assertSame('Nice to meet you.', $response);
    }

    public function test_enforce_returns_matched_product_details_when_one_literal_match_exists(): void
    {
        $product = $this->makeProduct('Premium Tea', 'premium-tea', 15.0, 7, 'in-stock');

        $catalogSearch = Mockery::mock(CatalogSearchService::class);
        $catalogSearch->shouldReceive('search')
            ->once()
            ->andReturn([
                'literal' => collect([$product]),
                'combined' => collect([$product]),
            ]);

        $intentDetector = Mockery::mock(ChatbotIntentDetector::class);
        $intentDetector->shouldReceive('detect')
            ->once()
            ->andReturn('product_inquiry');

        $guard = new ChatProductResponseGuard($catalogSearch, $intentDetector);

        $response = $guard->enforce('Do you have premium tea?', 'Let me check.', 'en');

        $this->assertStringContainsString('Matched product: [Premium Tea](/products/premium-tea)', $response);
        $this->assertStringContainsString('Availability: Available (7)', $response);
        $this->assertStringContainsString('Price starts from: 15 EGP', $response);
        $this->assertStringContainsString('Minimum order quantity: 3', $response);
        $this->assertStringContainsString('Minimum order value: 120 EGP', $response);
        $this->assertStringContainsString('Current stock: 7', $response);
    }

    public function test_enforce_replaces_catalog_denials_with_the_best_matching_product(): void
    {
        $product = $this->makeProduct('Tea Bags', 'tea-bags', 10.0, 5, 'in-stock');

        $catalogSearch = Mockery::mock(CatalogSearchService::class);
        $catalogSearch->shouldReceive('search')
            ->once()
            ->andReturn([
                'literal' => collect(),
                'combined' => collect([$product]),
            ]);

        $intentDetector = Mockery::mock(ChatbotIntentDetector::class);
        $intentDetector->shouldReceive('detect')
            ->once()
            ->andReturn('purchase_intent');

        $guard = new ChatProductResponseGuard($catalogSearch, $intentDetector);

        $response = $guard->enforce(
            'Need tea bags',
            "Sorry, we don't have that product in our catalog.",
            'en'
        );

        $this->assertStringContainsString('[Tea Bags](/products/tea-bags)', $response);
        $this->assertStringNotContainsString("don't have that product", $response);
    }

    public function test_enforce_leaves_existing_availability_messages_unchanged(): void
    {
        $product = $this->makeProduct('Herbal Mix', 'herbal-mix', 18.0, 0, 'out-of-stock');

        $catalogSearch = Mockery::mock(CatalogSearchService::class);
        $catalogSearch->shouldReceive('search')
            ->once()
            ->andReturn([
                'literal' => collect(),
                'combined' => collect([$product]),
            ]);

        $intentDetector = Mockery::mock(ChatbotIntentDetector::class);
        $intentDetector->shouldReceive('detect')
            ->once()
            ->andReturn('comparison');

        $guard = new ChatProductResponseGuard($catalogSearch, $intentDetector);

        $response = $guard->enforce(
            'Tell me about herbal mix',
            'Availability: Out of stock right now.',
            'en'
        );

        $this->assertSame('Availability: Out of stock right now.', $response);
    }

    public function test_enforce_appends_availability_summary_when_matches_exist_without_denial(): void
    {
        $first = $this->makeProduct('Premium Tea', 'premium-tea', 15.0, 7, 'in-stock');
        $second = $this->makeProduct('Herbal Mix', 'herbal-mix', 18.0, 0, 'out-of-stock');

        $catalogSearch = Mockery::mock(CatalogSearchService::class);
        $catalogSearch->shouldReceive('search')
            ->once()
            ->andReturn([
                'literal' => collect(),
                'combined' => collect([$first, $second]),
            ]);

        $intentDetector = Mockery::mock(ChatbotIntentDetector::class);
        $intentDetector->shouldReceive('detect')
            ->once()
            ->andReturn('general');

        $guard = new ChatProductResponseGuard($catalogSearch, $intentDetector);

        $response = $guard->enforce('Show me tea options', 'We have some options for you.', 'en');

        $this->assertStringContainsString("We have some options for you.\n\nAvailability:", $response);
        $this->assertStringContainsString('- Premium Tea: Available', $response);
        $this->assertStringContainsString('- Herbal Mix: Out of stock', $response);
    }

    private function makeProduct(
        string $name,
        string $slug,
        float $displayPrice,
        int $stock,
        string $availability
    ): Product {
        $variant = new ProductVariant([
            'unit_price_usd' => $displayPrice + 2,
            'price_after_discount' => $displayPrice,
            'agent_price' => $displayPrice,
            'availability' => $availability,
        ]);

        $variant->setRelation('inventoryProducts', new Collection([
            new InventoryProduct(['quantity' => $stock]),
        ]));

        $product = new Product([
            'minimum_order_quantity' => 3,
            'min_order_value' => 120,
            'agent_min_order_value' => 95,
            'slug' => $slug,
        ]);
        $product->setTranslations('name', ['en' => $name, 'ar' => $name]);
        $product->setTranslations('description', ['en' => $name . ' description', 'ar' => $name . ' description']);
        $product->setRelation('variants', new Collection([$variant]));

        return $product;
    }
}
