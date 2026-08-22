<?php

namespace Tests\Services\Unit\Admin;

use App\Http\Services\AdminDataQueryService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;
use Tests\Support\Services\Admin\BuildsAdminDataQueryFixtures;

class AdminDataQueryServiceContextTest extends ServiceSmokeTestCase
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

    public function test_platform_snapshot_and_relevant_context_include_targeted_sections_or_fallback_snapshot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-19 12:00:00'));

        $customer = User::factory()->create([
            'is_agent' => false,
            'full_name' => 'Context Buyer',
            'email' => 'buyer@example.com',
        ]);
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-CONTEXT-1',
            'commission' => 5,
        ]);
        [$product, $variant] = $this->createProductWithStock('Context Cleaner', 'context-cleaner', 3, 60);

        $this->createOrderWithItem($customer, $product, $variant, [
            'status' => 'pending',
            'total' => 60,
            'subtotal' => 60,
            'agent_code' => $agent->code,
            'created_at' => Carbon::now()->subHours(2),
            'email' => $customer->email,
        ], [
            'quantity' => 1,
            'unit_price' => 60,
            'total' => 60,
        ]);

        $service = $this->instantiateService($this->serviceClass());

        $snapshot = $service->getPlatformSnapshot('month');
        $targetedContext = $service->getRelevantContext('Show customer orders for buyer@example.com', 'month');
        $fallbackContext = $service->getRelevantContext('show all', 'month');

        $this->assertArrayHasKey('sales_overview', $snapshot);
        $this->assertArrayHasKey('order_status', $snapshot);
        $this->assertArrayHasKey('matched_customers', $targetedContext);
        $this->assertArrayHasKey('matched_orders', $targetedContext);
        $this->assertArrayHasKey('customer_performance', $targetedContext);
        $this->assertArrayHasKey('order_status', $targetedContext);
        $this->assertArrayHasKey('platform_snapshot', $fallbackContext);
    }
}
