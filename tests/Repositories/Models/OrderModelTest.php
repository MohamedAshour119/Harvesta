<?php

namespace Tests\Repositories\Models;

use App\Jobs\UpdateAgentMonthlyAchievedAmount;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_order_assigns_an_invoice_uuid(): void
    {
        $order = $this->createOrder();

        $this->assertNotNull($order->invoice_uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-fA-F-]{36}$/',
            $order->invoice_uuid
        );
    }

    public function test_updating_an_order_to_delivered_dispatches_the_monthly_achievement_job(): void
    {
        Bus::fake();

        $order = $this->createOrder(['status' => 'pending']);

        $order->update(['status' => 'delivered']);

        Bus::assertDispatched(UpdateAgentMonthlyAchievedAmount::class);
    }

    public function test_manual_sale_scopes_and_flags_return_the_expected_orders(): void
    {
        $submitted = $this->createOrder([
            'source' => 'agent_manual_sale',
            'manual_sale_status' => 'submitted',
        ]);

        $this->createOrder([
            'source' => 'agent_manual_sale',
            'manual_sale_status' => 'draft',
        ]);

        $customerOrder = $this->createOrder([
            'source' => 'customer_checkout',
            'manual_sale_status' => null,
        ]);

        $this->assertSame([$submitted->id], Order::submittedManualSales()->pluck('id')->all());
        $this->assertCount(2, Order::manualSales()->get());
        $this->assertTrue($submitted->isManualSale());
        $this->assertFalse($customerOrder->isManualSale());
    }

    private function createOrder(array $overrides = []): Order
    {
        $user = User::factory()->create();

        return Order::query()->create(array_merge([
            'user_id' => $user->id,
            'first_name' => 'Order',
            'last_name' => 'Customer',
            'email' => 'order@example.com',
            'phone_number' => '01000000000',
            'company_name' => 'Order Co',
            'address' => '123 Test St',
            'city' => 'Cairo',
            'province' => 'Cairo',
            'postal_code' => '12345',
            'payment_status' => 'completed',
            'status' => 'approved',
            'discount_amount' => 0,
            'subtotal' => 100,
            'total' => 100,
            'agent_code' => 'SYSTEM',
            'source' => 'customer_checkout',
            'manual_sale_status' => null,
        ], $overrides));
    }
}
