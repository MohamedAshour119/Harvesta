<?php

namespace Tests\Services\Unit\Orders;

use App\Http\Services\InventoryAllocationService;
use App\Http\Services\ManualOrderApprovalService;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\Support\ServiceSmokeTestCase;

class ManualOrderApprovalServiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ManualOrderApprovalService::class;
    }

    public function test_approve_allocates_inventory_and_marks_manual_sale_as_delivered(): void
    {
        Queue::fake();

        $approver = User::factory()->create();
        [$order, $variant] = $this->createSubmittedManualSale('AGENT-APPROVE', 2);
        $order->forceFill(['invoice_uuid' => null])->saveQuietly();

        $allocator = $this->mockDependency(InventoryAllocationService::class);
        $allocator->shouldReceive('allocate')
            ->once()
            ->with([
                [
                    'productVariantId' => $variant->id,
                    'quantity' => 2,
                ],
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $approved = $service->approve($order, $approver, 'Looks good');

        $this->assertSame('approved', $approved->manual_sale_status);
        $this->assertSame('delivered', $approved->status);
        $this->assertSame('completed', $approved->payment_status);
        $this->assertSame($approver->id, $approved->approved_by);
        $this->assertSame('Looks good', $approved->review_notes);
        $this->assertNotNull($approved->approved_at);
        $this->assertNotEmpty($approved->invoice_uuid);
    }

    public function test_reject_marks_submitted_manual_sale_as_cancelled_without_allocating_inventory(): void
    {
        Queue::fake();

        $approver = User::factory()->create();
        [$order] = $this->createSubmittedManualSale('AGENT-REJECT', 1);

        $allocator = $this->mockDependency(InventoryAllocationService::class);
        $allocator->shouldReceive('allocate')->never();

        $service = $this->instantiateService($this->serviceClass());
        $rejected = $service->reject($order, $approver, 'Customer declined');

        $this->assertSame('rejected', $rejected->manual_sale_status);
        $this->assertSame('cancelled', $rejected->status);
        $this->assertSame($approver->id, $rejected->approved_by);
        $this->assertSame('Customer declined', $rejected->review_notes);
        $this->assertNotNull($rejected->approved_at);
    }

    public function test_approve_throws_for_orders_that_are_not_submitted_manual_sales(): void
    {
        $approver = User::factory()->create();
        [$order] = $this->createSubmittedManualSale('AGENT-INVALID', 1);
        $order->forceFill([
            'source' => 'customer_checkout',
            'manual_sale_status' => null,
        ])->saveQuietly();

        $allocator = $this->mockDependency(InventoryAllocationService::class);
        $allocator->shouldReceive('allocate')->never();

        $service = $this->instantiateService($this->serviceClass());

        $this->expectException(ValidationException::class);

        $service->approve($order->fresh(), $approver);
    }

    /**
     * @return array{0: Order, 1: ProductVariant}
     */
    private function createSubmittedManualSale(string $agentCode, int $quantity): array
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => 'approved']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'agent_price' => 20,
            'unit_price_usd' => 20,
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'agent_code' => $agentCode,
            'source' => 'agent_manual_sale',
            'manual_sale_status' => 'submitted',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 20 * $quantity,
            'total' => 20 * $quantity,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'supplier_id' => null,
            'quantity' => $quantity,
            'unit_price' => 20,
            'cost_plus_logistics' => 0,
            'total' => 20 * $quantity,
        ]);

        return [$order->fresh('items'), $variant];
    }
}
