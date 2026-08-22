<?php

namespace Tests\Services\Unit\Admin;

use App\Http\Services\AdminDataQueryService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;
use Tests\Support\Services\Admin\BuildsAdminDataQueryFixtures;

class AdminDataQueryServiceMetricsTest extends ServiceSmokeTestCase
{
    use BuildsAdminDataQueryFixtures;
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return AdminDataQueryService::class;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sales_customer_and_agent_metrics_use_current_period_non_cancelled_orders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-19 12:00:00'));

        $customer = User::factory()->create([
            'is_agent' => false,
            'full_name' => 'Buyer One',
            'email' => 'buyer-one@example.com',
        ]);
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-ADMIN-1',
            'commission' => 10,
        ]);
        [$product, $variant] = $this->createProductWithStock('Industrial Cleaner', 'industrial-cleaner', 5, 60);

        $this->createOrderWithItem($customer, $product, $variant, [
            'status' => 'delivered',
            'payment_status' => 'completed',
            'total' => 120,
            'subtotal' => 120,
            'agent_code' => $agent->code,
            'created_at' => Carbon::now()->subDays(5),
        ], [
            'quantity' => 2,
            'unit_price' => 60,
            'total' => 120,
        ]);
        $this->createOrderWithItem($customer, $product, $variant, [
            'status' => 'pending',
            'payment_status' => 'pending',
            'total' => 80,
            'subtotal' => 80,
            'agent_code' => $agent->code,
            'created_at' => Carbon::now()->subDays(2),
        ], [
            'quantity' => 1,
            'unit_price' => 80,
            'total' => 80,
        ]);
        $this->createOrderWithItem($customer, $product, $variant, [
            'status' => 'cancelled',
            'payment_status' => 'failed',
            'total' => 70,
            'subtotal' => 70,
            'agent_code' => $agent->code,
            'created_at' => Carbon::now()->subDay(),
        ], [
            'quantity' => 1,
            'unit_price' => 70,
            'total' => 70,
        ]);
        $this->createOrderWithItem($customer, $product, $variant, [
            'status' => 'delivered',
            'payment_status' => 'completed',
            'total' => 999,
            'subtotal' => 999,
            'agent_code' => $agent->code,
            'created_at' => Carbon::now()->subMonth()->startOfMonth()->addDays(3),
        ], [
            'quantity' => 5,
            'unit_price' => 199.8,
            'total' => 999,
        ]);

        $service = $this->instantiateService($this->serviceClass());

        $salesOverview = $service->getSalesOverview('month');
        $topProducts = $service->getTopProducts(5, 'month');
        $topCustomers = $service->getTopCustomers(5, 'month');
        $customerPerformance = $service->getCustomerPerformance('month');
        $orderStatus = $service->getOrdersByStatus('month');
        $agentPerformance = $service->getAgentPerformance('month');

        $this->assertSame(200.0, $salesOverview['total_revenue']);
        $this->assertSame(2, $salesOverview['order_count']);
        $this->assertSame(100.0, $salesOverview['average_order_value']);
        $this->assertSame(3, $topProducts[0]['total_sold']);
        $this->assertSame(200.0, $topProducts[0]['total_revenue']);
        $this->assertSame('Buyer One', $topCustomers[0]['customer_name']);
        $this->assertSame(200.0, $topCustomers[0]['total_spent']);
        $this->assertSame(1, $customerPerformance['active_customers']);
        $this->assertSame(0, $customerPerformance['new_customers']);
        $this->assertSame(1, $customerPerformance['repeat_customers']);
        $this->assertSame(200.0, $customerPerformance['total_customer_revenue']);
        $this->assertSame(3, $orderStatus['total_orders']);
        $this->assertSame(1, $orderStatus['pending_orders']);
        $this->assertSame(1, $orderStatus['delivered_orders']);
        $this->assertSame(1, $orderStatus['cancelled_orders']);
        $this->assertSame('AGENT-ADMIN-1', $agentPerformance[0]['agent_code']);
        $this->assertSame(2, $agentPerformance[0]['total_orders']);
        $this->assertSame(200.0, $agentPerformance[0]['total_revenue']);
    }
}
