<?php

namespace Tests\Repositories\Agent;

use App\Http\Repositories\AgentDashboardRepository;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Tests\Support\Repositories\Agent\BuildsAgentDashboardFixtures;
use Tests\Support\RepositoryTestCase;

class AgentDashboardRepositorySalesTest extends RepositoryTestCase
{
    use BuildsAgentDashboardFixtures;

    private AgentDashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        App::setLocale('en');
        $this->repository = app(AgentDashboardRepository::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sales_performance_and_recent_orders_return_expected_yearly_and_paginated_data(): void
    {
        Carbon::setTestNow('2026-04-20 12:00:00');

        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-DASH-2',
            'commission' => 10,
        ]);
        $this->actingAs($agent);

        $januaryCustomer = User::factory()->create(['full_name' => 'January Buyer']);
        $aprilCustomer = User::factory()->create(['full_name' => 'April Buyer']);

        $janOrder = $this->createAgentOrder($agent->code, 100, 'delivered', Carbon::parse('2026-01-10 14:30:00'), $januaryCustomer);
        $this->attachOrderItem($janOrder, $this->createCategory('January'), 100, 1);

        $febOrder = $this->createAgentOrder($agent->code, 60, 'delivered', Carbon::parse('2026-02-15 10:00:00'), $aprilCustomer);
        $this->attachOrderItem($febOrder, $this->createCategory('February'), 60, 1);

        $aprilDelivered = $this->createAgentOrder($agent->code, 80, 'delivered', Carbon::parse('2026-04-10 14:30:00'), $aprilCustomer);
        $this->attachOrderItem($aprilDelivered, $this->createCategory('April'), 80, 1);

        $guestOrder = $this->createAgentOrder($agent->code, 40, 'pending', Carbon::parse('2026-04-09 08:15:00'), null, [
            'first_name' => 'Guest',
            'last_name' => 'Buyer',
            'email' => 'guest@example.com',
        ]);
        $this->attachOrderItem($guestOrder, $this->createCategory('Guest'), 40, 1);

        $salesPerformance = $this->repository->salesPerformance(2026);
        $recentOrders = $this->repository->recentOrders(2);

        $this->assertSame(100.0, collect($salesPerformance)->firstWhere('month', 'Jan')['value']);
        $this->assertSame(60.0, collect($salesPerformance)->firstWhere('month', 'Feb')['value']);
        $this->assertSame(80.0, collect($salesPerformance)->firstWhere('month', 'Apr')['value']);
        $this->assertSame([$aprilDelivered->id, $guestOrder->id], collect($recentOrders->items())->pluck('id')->all());
        $this->assertSame('April Buyer', $recentOrders->items()[0]->customer);
        $this->assertSame('10/04/2026', $recentOrders->items()[0]->date);
        $this->assertSame('02:30 PM', $recentOrders->items()[0]->time);
        $this->assertSame(8.0, (float) $recentOrders->items()[0]->commission);
        $this->assertSame('Guest Buyer', $recentOrders->items()[1]->customer);
    }
}
