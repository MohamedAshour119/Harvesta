<?php

namespace Tests\Repositories\Orders;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManualSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_sale_scopes_and_flags_work(): void
    {
        $manual = Order::factory()->create([
            'source' => 'agent_manual_sale',
            'manual_sale_status' => 'submitted',
        ]);

        Order::factory()->create([
            'source' => 'customer_checkout',
            'manual_sale_status' => null,
        ]);

        $this->assertTrue($manual->isManualSale());
        $this->assertCount(1, Order::manualSales()->get());
        $this->assertCount(1, Order::submittedManualSales()->get());
    }
}
