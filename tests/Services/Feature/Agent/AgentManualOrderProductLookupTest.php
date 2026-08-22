<?php

namespace Tests\Services\Feature\Agent;

use App\Http\Services\CatalogSearchService;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentManualOrderProductLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_lookup_prefers_literal_results_over_semantic_results(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-LOOKUP-1',
        ]));

        $literalProduct = Product::factory()->create([
            'name' => [
                'en' => 'Luxury Desk Chair',
                'ar' => 'luxury desk chair ar',
            ],
            'status' => 'approved',
        ]);
        $literalVariant = ProductVariant::factory()->create([
            'product_id' => $literalProduct->id,
            'price_after_discount' => 25,
            'unit_price_usd' => 30,
            'agent_price' => 22,
        ]);

        $semanticProduct = Product::factory()->create([
            'name' => [
                'en' => 'Executive Workspace Seat',
                'ar' => 'executive workspace seat ar',
            ],
            'status' => 'approved',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $semanticProduct->id,
            'price_after_discount' => 45,
            'unit_price_usd' => 50,
        ]);

        $this->mock(CatalogSearchService::class, function (MockInterface $mock) use ($literalProduct, $semanticProduct) {
            $mock->shouldReceive('search')
                ->once()
                ->with('desk chair', 10, 10)
                ->andReturn([
                    'literal' => collect([$literalProduct]),
                    'semantic' => collect([$semanticProduct]),
                    'combined' => collect([$literalProduct, $semanticProduct]),
                ]);
        });

        $response = $this->withHeader('Accept', 'application/json')
            ->get('/api/agents/manual-orders/lookups/products?search=desk%20chair');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $literalProduct->id)
            ->assertJsonPath('data.0.variants.0.id', $literalVariant->id)
            ->assertJsonPath('data.0.variants.0.agent_price', 22);
    }

    public function test_product_lookup_falls_back_to_semantic_results_when_literal_results_are_empty(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-LOOKUP-2',
        ]));

        $semanticProduct = Product::factory()->create([
            'name' => [
                'en' => 'Industrial Storage Cabinet',
                'ar' => 'industrial storage cabinet ar',
            ],
            'status' => 'approved',
        ]);
        $semanticVariant = ProductVariant::factory()->create([
            'product_id' => $semanticProduct->id,
            'price_after_discount' => 80,
            'unit_price_usd' => 95,
            'agent_price' => 71,
        ]);

        $this->mock(CatalogSearchService::class, function (MockInterface $mock) use ($semanticProduct) {
            $mock->shouldReceive('search')
                ->once()
                ->with('warehouse organizer', 10, 10)
                ->andReturn([
                    'literal' => collect(),
                    'semantic' => collect([$semanticProduct]),
                    'combined' => collect([$semanticProduct]),
                ]);
        });

        $response = $this->withHeader('Accept', 'application/json')
            ->get('/api/agents/manual-orders/lookups/products?search=warehouse%20organizer');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $semanticProduct->id)
            ->assertJsonPath('data.0.variants.0.id', $semanticVariant->id)
            ->assertJsonPath('data.0.variants.0.agent_price', 71);
    }

    public function test_regular_users_cannot_use_manual_order_product_lookup(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'is_agent' => false,
        ]));

        $response = $this->withHeader('Accept', 'application/json')
            ->get('/api/agents/manual-orders/lookups/products?search=desk%20chair');

        $response->assertStatus(403);
    }
}
