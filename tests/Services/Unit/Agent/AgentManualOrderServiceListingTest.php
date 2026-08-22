<?php

namespace Tests\Services\Unit\Agent;

use App\Http\Services\AgentManualOrderService;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class AgentManualOrderServiceListingTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return AgentManualOrderService::class;
    }

    public function test_list_for_agent_returns_latest_manual_sales_for_that_agent_only(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-LIST-1',
        ]);

        foreach (range(1, 4) as $index) {
            Order::factory()->create([
                'agent_code' => 'AGENT-LIST-1',
                'source' => 'agent_manual_sale',
                'manual_sale_status' => 'submitted',
                'created_at' => now()->subMinutes(5 - $index),
            ]);
        }

        Order::factory()->create([
            'agent_code' => 'AGENT-LIST-1',
            'source' => 'customer_checkout',
        ]);
        Order::factory()->create([
            'agent_code' => 'OTHER-AGENT',
            'source' => 'agent_manual_sale',
        ]);

        $service = $this->instantiateService($this->serviceClass());
        $orders = $service->listForAgent($agent, 3);

        $this->assertCount(3, $orders);
        $this->assertTrue($orders->every(fn (Order $order): bool => $order->source === 'agent_manual_sale'));
        $this->assertSame(
            Order::query()
                ->where('agent_code', 'AGENT-LIST-1')
                ->where('source', 'agent_manual_sale')
                ->latest()
                ->limit(3)
                ->pluck('id')
                ->all(),
            $orders->pluck('id')->all()
        );
    }

    public function test_find_for_agent_returns_only_the_agents_manual_sale_with_relations_loaded(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-FIND-1',
        ]);
        $product = Product::factory()->create(['status' => 'approved']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);
        $order = Order::factory()->create([
            'agent_code' => 'AGENT-FIND-1',
            'source' => 'agent_manual_sale',
            'manual_sale_status' => 'submitted',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'supplier_id' => null,
            'quantity' => 1,
            'unit_price' => 15,
            'cost_plus_logistics' => 0,
            'total' => 15,
        ]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->findForAgent($agent, $order);

        $this->assertSame($order->id, $result->id);
        $this->assertTrue($result->relationLoaded('items'));
        $this->assertTrue($result->items->first()->relationLoaded('product'));
        $this->assertTrue($result->items->first()->relationLoaded('variant'));
    }
}
