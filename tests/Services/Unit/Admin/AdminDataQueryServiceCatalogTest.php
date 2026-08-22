<?php

namespace Tests\Services\Unit\Admin;

use App\Http\Services\AdminDataQueryService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;
use Tests\Support\Services\Admin\BuildsAdminDataQueryFixtures;

class AdminDataQueryServiceCatalogTest extends ServiceSmokeTestCase
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

    public function test_inventory_product_agent_and_recent_order_queries_return_formatted_results(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-19 12:00:00'));

        $customer = User::factory()->create([
            'is_agent' => false,
            'full_name' => 'Mona Search',
            'email' => 'mona-search@example.com',
        ]);
        $agent = User::factory()->create([
            'is_agent' => true,
            'full_name' => 'Agent Search',
            'email' => 'agent-search@example.com',
            'code' => 'AGENT-SEARCH-1',
            'commission' => 7.5,
        ]);

        [$lowStockProduct, $lowStockVariant] = $this->createProductWithStock('Low Stock Cleaner', 'low-stock-cleaner', 5, 35);
        [$outOfStockProduct] = $this->createProductWithStock('Out of Stock Degreaser', 'out-of-stock-degreaser', 0, 45);
        $this->createProductWithStock('Pending Cleaner', 'pending-cleaner', 9, 55, ['status' => 'pending']);

        $recentOrder = $this->createOrderWithItem($customer, $lowStockProduct, $lowStockVariant, [
            'status' => 'approved',
            'total' => 70,
            'subtotal' => 70,
            'agent_code' => $agent->code,
            'created_at' => Carbon::now()->subHour(),
        ], [
            'quantity' => 2,
            'unit_price' => 35,
            'total' => 70,
        ]);

        $service = $this->instantiateService($this->serviceClass());

        $alerts = $service->getInventoryAlerts();
        $agents = $service->searchAgents('AGENT-SEARCH-1');
        $products = $service->searchProducts('low cleaner');
        $recentOrders = $service->getRecentOrders(1);

        $this->assertSame(['Low Stock Cleaner', 'Out of Stock Degreaser'], collect($alerts)->pluck('product')->all());
        $this->assertSame(['Low Stock', 'Out of Stock'], collect($alerts)->pluck('status')->all());
        $this->assertSame('AGENT-SEARCH-1', $agents[0]['agent_code']);
        $this->assertSame('low-stock-cleaner', $products[0]['slug']);
        $this->assertSame(5, $products[0]['stock_quantity']);
        $this->assertSame(35.0, $products[0]['price']);
        $this->assertSame($recentOrder->id, $recentOrders[0]['id']);
        $this->assertSame('Mona Search', $recentOrders[0]['customer']);
    }
}
