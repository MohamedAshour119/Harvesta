<?php

namespace Tests\Services\Unit\Agent;

use App\Http\Requests\AgentManualOrderStoreRequest;
use App\Http\Services\AgentManualOrderService;
use App\Http\Services\ManualOrderApprovalService;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Support\ServiceSmokeTestCase;

class AgentManualOrderServiceStoreTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return AgentManualOrderService::class;
    }

    public function test_store_creates_order_items_and_auto_approves_when_total_is_below_threshold(): void
    {
        Queue::fake();

        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-STORE-1',
        ]);
        $product = Product::factory()->create(['status' => 'approved']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'agent_price' => 18,
            'unit_price_usd' => 25,
            'cost_plus_logistics' => 3,
        ]);

        $approvalService = $this->mockDependency(ManualOrderApprovalService::class);
        $approvalService->shouldReceive('approve')
            ->once()
            ->with(Mockery::on(function (Order $order) use ($agent): bool {
                return $order->agent_code === $agent->code
                    && $order->source === 'agent_manual_sale'
                    && (float) $order->total === 36.0;
            }), $agent, 'Auto-approved (System)')
            ->andReturnUsing(function (Order $order, User $approver): Order {
                $order->forceFill([
                    'manual_sale_status' => 'approved',
                    'status' => 'delivered',
                    'payment_status' => 'completed',
                    'approved_by' => $approver->id,
                ])->save();

                return $order->refresh();
            });

        $service = $this->instantiateService($this->serviceClass());
        $order = $service->store($this->makeStoreRequest($agent, $product, $variant, 2, ['priority' => 'rush']));

        $this->assertSame('approved', $order->manual_sale_status);
        $this->assertSame('delivered', $order->status);
        $this->assertSame(36.0, (float) $order->subtotal);
        $this->assertCount(1, $order->items);
        $this->assertSame(18.0, (float) $order->items->first()->unit_price);
        $this->assertSame(36.0, (float) $order->items->first()->total);
    }

    public function test_store_keeps_large_manual_sales_submitted_for_review(): void
    {
        Queue::fake();

        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-STORE-2',
        ]);
        $product = Product::factory()->create(['status' => 'approved']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'agent_price' => 600,
            'unit_price_usd' => 620,
            'cost_plus_logistics' => 50,
        ]);

        $approvalService = $this->mockDependency(ManualOrderApprovalService::class);
        $approvalService->shouldReceive('approve')->never();

        $service = $this->instantiateService($this->serviceClass());
        $order = $service->store($this->makeStoreRequest($agent, $product, $variant, 2));

        $this->assertSame('submitted', $order->manual_sale_status);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(1200.0, (float) $order->total);
    }

    private function makeStoreRequest(
        User $agent,
        Product $product,
        ProductVariant $variant,
        int $quantity,
        ?array $specialOptions = null
    ): AgentManualOrderStoreRequest {
        $request = Mockery::mock(AgentManualOrderStoreRequest::class);
        $request->shouldReceive('user')
            ->andReturn($agent);
        $request->shouldReceive('customerSnapshot')
            ->andReturn([
                'user_id' => null,
                'first_name' => 'Manual',
                'last_name' => 'Customer',
                'email' => 'manual@example.com',
                'phone_number' => '123456789',
                'address' => 'Warehouse Street',
                'city' => 'Cairo',
                'province' => 'Cairo',
                'postal_code' => '11511',
                'company_name' => 'Manual Co',
            ]);
        $request->shouldReceive('validated')
            ->once()
            ->with('products')
            ->andReturn([
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                ],
            ]);
        $request->shouldReceive('validated')
            ->once()
            ->with('special_sales_options')
            ->andReturn($specialOptions);
        $request->shouldReceive('date')
            ->once()
            ->with('sold_at')
            ->andReturn(now());
        $request->shouldReceive('hasFile')
            ->once()
            ->with('proof_photo')
            ->andReturnFalse();

        return $request;
    }
}
