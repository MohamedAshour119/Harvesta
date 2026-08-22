<?php

namespace Tests\Services\Unit\Admin;

use App\Http\Services\AdminDataQueryService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;
use Tests\Support\Services\Admin\BuildsAdminDataQueryFixtures;

class AdminDataQueryServiceLookupTest extends ServiceSmokeTestCase
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

    public function test_lookup_helpers_and_sales_trends_return_expected_order_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-19 12:00:00'));

        $customer = User::factory()->create([
            'is_agent' => false,
            'full_name' => 'Trend Buyer',
            'email' => 'trend-buyer@example.com',
            'phone_number' => '555-000',
        ]);
        [$product, $variant] = $this->createProductWithStock('Trend Cleaner', 'trend-cleaner', 9, 50);

        $firstOrder = $this->createOrderWithItem($customer, $product, $variant, [
            'status' => 'approved',
            'total' => 50,
            'subtotal' => 50,
            'created_at' => Carbon::now()->subMonth()->startOfMonth()->addDays(2),
            'email' => $customer->email,
            'phone_number' => '555-000',
        ], [
            'quantity' => 1,
            'unit_price' => 50,
            'total' => 50,
        ]);
        $latestOrder = $this->createOrderWithItem($customer, $product, $variant, [
            'status' => 'shipped',
            'total' => 100,
            'subtotal' => 100,
            'created_at' => Carbon::now()->subDay(),
            'email' => $customer->email,
            'phone_number' => '555-000',
        ], [
            'quantity' => 2,
            'unit_price' => 50,
            'total' => 100,
        ]);

        $service = $this->instantiateService($this->serviceClass());

        $lookupOrder = $service->lookupOrder($latestOrder->invoice_uuid);
        $boundaryOrder = $service->getBoundaryOrderDetails('latest');
        $customerLookup = $service->lookupCustomer($customer->email);
        $customerOrders = $service->getOrdersForCustomer($customer->email, 5);
        $salesTrends = $service->getSalesTrends(2);

        $this->assertSame($latestOrder->id, $lookupOrder['id']);
        $this->assertSame('Trend Buyer', $lookupOrder['customer']);
        $this->assertSame('Trend Cleaner', $lookupOrder['items'][0]['product']);
        $this->assertSame($latestOrder->id, $boundaryOrder['id']);
        $this->assertSame('trend-buyer@example.com', $customerLookup[0]['email']);
        $this->assertSame(2, $customerLookup[0]['order_count']);
        $this->assertSame(150.0, $customerLookup[0]['total_spent']);
        $this->assertSame([$latestOrder->id, $firstOrder->id], collect($customerOrders)->pluck('id')->all());
        $this->assertSame(2, count($salesTrends));
        $this->assertSame(50.0, $salesTrends[0]['revenue']);
        $this->assertSame(100.0, $salesTrends[1]['revenue']);
    }
}
