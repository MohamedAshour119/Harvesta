<?php

namespace Tests\Services\Unit\Admin;

use App\Http\Services\AdminDataQueryService;
use App\Http\Services\AdminIntentRouter;
use Tests\Support\ServiceSmokeTestCase;

class AdminIntentRouterTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return AdminIntentRouter::class;
    }

    public function test_route_returns_top_customers_data_for_matching_queries(): void
    {
        $dataService = $this->mockDependency(AdminDataQueryService::class);
        $dataService->shouldReceive('getTopCustomers')
            ->once()
            ->with(10, 'year')
            ->andReturn([['customer_name' => 'Acme']]);

        $router = $this->instantiateService($this->serviceClass());
        $result = $router->route('show top customers this year');

        $this->assertSame('top_customers', $result['intent']);
        $this->assertSame("Top Customers by Spending (year)", $result['data_label']);
        $this->assertSame([['customer_name' => 'Acme']], $result['data']);
    }
}
