<?php

namespace Tests\Services\Unit\Agent;

use App\Http\Services\AgentManualOrderService;
use App\Http\Services\CatalogSearchService;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class AgentManualOrderServiceLookupTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return AgentManualOrderService::class;
    }

    public function test_customer_lookup_returns_matching_non_agent_users_only(): void
    {
        User::factory()->create([
            'is_agent' => false,
            'full_name' => 'Mona Salem',
            'email' => 'mona@example.com',
            'phone_number' => '12345',
        ]);
        User::factory()->create([
            'is_agent' => true,
            'full_name' => 'Mona Agent',
            'email' => 'agent@example.com',
            'code' => 'AGENT-MONA',
        ]);

        $service = $this->instantiateService($this->serviceClass());
        $results = $service->customerLookup('Mona');

        $this->assertCount(1, $results);
        $this->assertSame('Mona Salem', $results->first()['full_name']);
        $this->assertSame('mona@example.com', $results->first()['email']);
    }

    public function test_product_lookup_prefers_literal_search_results_and_formats_variants(): void
    {
        $literalProduct = Product::factory()->create([
            'status' => 'approved',
            'slug' => 'literal-product',
        ]);
        $literalVariant = ProductVariant::factory()->create([
            'product_id' => $literalProduct->id,
            'agent_price' => 22,
            'price_after_discount' => 25,
            'unit_price_usd' => 30,
        ]);

        $semanticProduct = Product::factory()->create([
            'status' => 'approved',
            'slug' => 'semantic-product',
        ]);
        ProductVariant::factory()->create([
            'product_id' => $semanticProduct->id,
            'agent_price' => 55,
            'price_after_discount' => 58,
            'unit_price_usd' => 60,
        ]);

        $catalogSearch = $this->mockDependency(CatalogSearchService::class);
        $catalogSearch->shouldReceive('search')
            ->once()
            ->with('desk chair', 10, 10)
            ->andReturn([
                'literal' => collect([$literalProduct]),
                'semantic' => collect([$semanticProduct]),
                'combined' => collect([$literalProduct, $semanticProduct]),
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $results = $service->productLookup('desk chair');

        $this->assertCount(1, $results);
        $this->assertSame($literalProduct->id, $results->first()['id']);
        $this->assertSame($literalVariant->id, $results->first()['variants'][0]['id']);
        $this->assertSame(22.0, $results->first()['variants'][0]['agent_price']);
    }
}
