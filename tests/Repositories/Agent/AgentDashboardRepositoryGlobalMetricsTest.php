<?php

namespace Tests\Repositories\Agent;

use App\Http\Repositories\AgentDashboardRepository;
use App\Models\IndustryCategory;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Tests\Support\Repositories\Agent\BuildsAgentDashboardFixtures;
use Tests\Support\RepositoryTestCase;

class AgentDashboardRepositoryGlobalMetricsTest extends RepositoryTestCase
{
    use BuildsAgentDashboardFixtures;

    private AgentDashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        App::setLocale('en');
        $this->repository = app(AgentDashboardRepository::class);
    }

    public function test_global_dashboard_aggregates_return_expected_totals_and_top_category(): void
    {
        App::setLocale('en');

        $agentOne = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-GLOBAL-1',
            'commission' => 10,
        ]);
        $agentTwo = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-GLOBAL-2',
            'commission' => 5,
        ]);
        $nonAgent = User::factory()->create([
            'is_agent' => false,
            'code' => null,
        ]);

        $cleaning = IndustryCategory::query()->create([
            'name' => ['en' => 'Cleaning', 'ar' => 'ØªÙ†Ø¸ÙŠÙ'],
        ]);
        $packaging = IndustryCategory::query()->create([
            'name' => ['en' => 'Packaging', 'ar' => 'ØªØºÙ„ÙŠÙ'],
        ]);

        $deliveredOne = $this->createAgentOrder($agentOne->code, 100, 'delivered', now()->subDays(2));
        $this->attachOrderItem($deliveredOne, $cleaning, 100, 3);

        $deliveredTwo = $this->createAgentOrder($agentTwo->code, 80, 'delivered', now()->subDay());
        $this->attachOrderItem($deliveredTwo, $packaging, 80, 1);

        $pending = $this->createAgentOrder($agentOne->code, 50, 'pending', now());
        $this->attachOrderItem($pending, $cleaning, 50, 2);

        $ignored = $this->createAgentOrder('NON-AGENT-CODE', 70, 'delivered', now(), $nonAgent);
        $this->attachOrderItem($ignored, $packaging, 70, 1);

        $topCategory = $this->repository->getTopSellingCategory();

        $this->assertSame(14.0, (float) $this->repository->getTotalDeliveredCommissions());
        $this->assertSame(5.0, (float) $this->repository->getTotalPendingCommissions());
        $this->assertSame(4, $this->repository->getTotalOrdersCount());
        $this->assertSame(180.0, $this->repository->getTotalSales());
        $this->assertSame('Cleaning', $topCategory->category_name);
        $this->assertSame(3, (int) $topCategory->total_quantity);
    }
}
