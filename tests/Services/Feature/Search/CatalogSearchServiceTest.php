<?php

namespace Tests\Services\Feature\Search;

use App\Http\Services\CatalogSearchService;
use App\Http\Services\GroqService;
use App\Http\Services\SemanticSearchService;
use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class CatalogSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_query_understanding_prefers_oil_for_frying_intent(): void
    {
        [$oilProduct, $friedProduct] = $this->seedOilAndFriedProducts();

        $this->mock(GroqService::class, function (MockInterface $mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'content' => json_encode([
                        'language' => 'ar',
                        'is_intent_query' => true,
                        'search_phrases' => ['زيت للقلي', 'زيت طبخ', 'frying oil', 'cooking oil'],
                        'keywords' => ['زيت', 'قلي'],
                        'negative_keywords' => ['مقلي', 'وجبة مقلية'],
                    ], JSON_UNESCAPED_UNICODE),
                    'tokens_used' => 42,
                ]);
        });

        $results = app(CatalogSearchService::class)->search('منتج يستخدم للقلي', 5, 5);
        $oilIndex = $results['literal']->search(fn (Product $product) => $product->id === $oilProduct->id);
        $friedIndex = $results['literal']->search(fn (Product $product) => $product->id === $friedProduct->id);

        $this->assertSame($oilProduct->id, $results['literal']->first()?->id);
        $this->assertTrue($results['literal']->pluck('id')->contains($friedProduct->id));
        $this->assertLessThan($friedIndex, $oilIndex);
    }

    public function test_ai_query_understanding_can_handle_colloquial_arabic(): void
    {
        [$oilProduct] = $this->seedOilAndFriedProducts();

        $this->mock(GroqService::class, function (MockInterface $mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'content' => json_encode([
                        'language' => 'ar',
                        'is_intent_query' => true,
                        'search_phrases' => ['زيت للقلي', 'frying oil'],
                        'keywords' => ['زيت', 'قلي'],
                        'negative_keywords' => ['وجبة', 'مقلي'],
                    ], JSON_UNESCAPED_UNICODE),
                    'tokens_used' => 38,
                ]);
        });

        $results = app(CatalogSearchService::class)->search('حاجة تنفع للقلي', 5, 5);

        $this->assertSame($oilProduct->id, $results['literal']->first()?->id);
    }

    public function test_ai_query_understanding_keeps_fried_product_queries_on_fried_items(): void
    {
        [, $friedProduct] = $this->seedOilAndFriedProducts();

        $this->mock(GroqService::class, function (MockInterface $mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'content' => json_encode([
                        'language' => 'ar',
                        'is_intent_query' => true,
                        'search_phrases' => ['منتج مقلي', 'وجبة مقلية', 'fried food'],
                        'keywords' => ['مقلي'],
                        'negative_keywords' => ['زيت'],
                    ], JSON_UNESCAPED_UNICODE),
                    'tokens_used' => 35,
                ]);
        });

        $results = app(CatalogSearchService::class)->search('منتج مقلي', 5, 5);

        $this->assertSame($friedProduct->id, $results['literal']->first()?->id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->mock(SemanticSearchService::class, function (MockInterface $mock) {
            $mock->shouldReceive('search')
                ->zeroOrMoreTimes()
                ->andReturn([]);
        });
    }

    /**
     * @return array{0: Product, 1: Product}
     */
    private function seedOilAndFriedProducts(): array
    {
        $oilCategory = IndustryCategory::factory()->create([
            'name' => [
                'en' => 'Oil',
                'ar' => 'زيت',
            ],
            'slug' => 'oil',
        ]);

        $friedCategory = IndustryCategory::factory()->create([
            'name' => [
                'en' => 'Fried',
                'ar' => 'مقلي',
            ],
            'slug' => 'fried',
        ]);

        $oilProduct = Product::factory()->create([
            'industry_category_id' => $oilCategory->id,
            'name' => [
                'en' => 'Roots Corn Oil',
                'ar' => 'زيت ذرة رووتس',
            ],
            'description' => [
                'en' => 'Cooking oil for frying and everyday kitchen use.',
                'ar' => 'زيت مناسب للقلي والطبخ اليومي.',
            ],
            'status' => 'approved',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $oilProduct->id,
            'price_after_discount' => 20,
            'unit_price_usd' => 20,
        ]);

        $friedProduct = Product::factory()->create([
            'industry_category_id' => $friedCategory->id,
            'name' => [
                'en' => 'Fried Chicken Fillet',
                'ar' => 'فيليه دجاج مقلي',
            ],
            'description' => [
                'en' => 'Ready fried chicken meal.',
                'ar' => 'وجبة دجاج مقلي جاهزة.',
            ],
            'status' => 'approved',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $friedProduct->id,
            'price_after_discount' => 30,
            'unit_price_usd' => 30,
        ]);

        return [$oilProduct, $friedProduct];
    }
}
