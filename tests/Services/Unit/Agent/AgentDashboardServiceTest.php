<?php

namespace Tests\Services\Unit\Agent;

use App\Http\Repositories\AgentDashboardRepository;
use App\Http\Resources\AgentDashboardResource;
use App\Http\Services\AgentDashboardService;
use Illuminate\Http\Request;
use Tests\Support\ServiceSmokeTestCase;

class AgentDashboardServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return AgentDashboardService::class;
    }

    public function test_dashboard_info_wraps_repository_payload_in_resource(): void
    {
        $repository = $this->mockDependency(AgentDashboardRepository::class);
        $repository->shouldReceive('dashboardInfo')
            ->once()
            ->andReturn([
                'percent_change' => 15,
                'top_commission_source' => ['name' => 'Oil', 'commission' => 200],
                'monthly_target' => 1000,
                'monthly_target_progress' => 250,
            ]);

        $service = $this->instantiateService($this->serviceClass());
        $resource = $service->dashboardInfo();

        $this->assertInstanceOf(AgentDashboardResource::class, $resource);
        $this->assertSame(25.0, $resource->resolve()['monthly_target_progress_percent']);
    }

    public function test_recent_orders_uses_the_requested_page_size(): void
    {
        $repository = $this->mockDependency(AgentDashboardRepository::class);
        $repository->shouldReceive('recentOrders')
            ->once()
            ->with(25)
            ->andReturn(['orders']);

        $service = $this->instantiateService($this->serviceClass());
        $request = Request::create('/dashboard', 'GET', ['per_page' => '25']);

        $this->assertSame(['orders'], $service->recentOrders($request));
    }
}
