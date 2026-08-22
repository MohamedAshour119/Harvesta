<?php

namespace Tests\Repositories\Agent;

use App\Http\Repositories\AgentDashboardRepository;
use App\Models\AgentMonthlyTarget;
use App\Models\IndustryCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Tests\Support\Repositories\Agent\BuildsAgentDashboardFixtures;
use Tests\Support\RepositoryTestCase;

class AgentDashboardRepositoryDashboardTest extends RepositoryTestCase
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

    public function test_dashboard_info_calculates_current_month_metrics_new_clients_repeat_buyers_and_top_categories(): void
    {
        Carbon::setTestNow('2026-04-20 12:00:00');

        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-DASH-1',
            'commission' => 10,
        ]);
        $this->actingAs($agent);
        AgentMonthlyTarget::query()->create([
            'agent_id' => $agent->id,
            'target_amount' => 20,
            'achieved_amount' => 0,
            'reset_at' => now()->endOfMonth()->toDateString(),
        ]);

        $cleaning = IndustryCategory::query()->create([
            'name' => ['en' => 'Cleaning', 'ar' => 'ØªÙ†Ø¸ÙŠÙ'],
        ]);
        $packaging = IndustryCategory::query()->create([
            'name' => ['en' => 'Packaging', 'ar' => 'ØªØºÙ„ÙŠÙ'],
        ]);

        $newCustomer = User::factory()->create(['is_agent' => false]);
        $repeatCustomer = User::factory()->create(['is_agent' => false]);

        $currentA = $this->createAgentOrder($agent->code, 100, 'delivered', now()->subDays(5), $newCustomer);
        $this->attachOrderItem($currentA, $cleaning, 100, 1);

        $currentB = $this->createAgentOrder($agent->code, 50, 'delivered', now()->subDays(2), $repeatCustomer);
        $this->attachOrderItem($currentB, $packaging, 50, 1);

        $previous = $this->createAgentOrder($agent->code, 80, 'delivered', now()->subMonth()->startOfMonth()->addDays(2), $repeatCustomer);
        $this->attachOrderItem($previous, $cleaning, 80, 1);

        $pending = $this->createAgentOrder($agent->code, 60, 'pending', now()->subDay(), $newCustomer);
        $this->attachOrderItem($pending, $cleaning, 60, 1);

        $result = $this->repository->dashboardInfo();

        $this->assertSame(15.0, $result['total_commission_this_month']);
        $this->assertSame(6.0, $result['pending_commissions']);
        $this->assertSame(8.0, $result['total_commission_last_month']);
        $this->assertSame(87.5, $result['percent_change']);
        $this->assertSame(20.0, $result['monthly_target']);
        $this->assertSame(15.0, $result['monthly_target_progress']);
        $this->assertSame(75.0, $result['monthly_target_progress_percent']);
        $this->assertSame(1, $result['new_clients']);
        $this->assertSame(1, $result['repeat_buyers']);
        $this->assertSame(50.0, $result['conversion_rate']);
        $this->assertSame(json_encode($cleaning->getTranslations('name')), $result['top_commission_source']['name']);
        $this->assertSame(10.0, $result['top_commission_source']['commission']);
        $this->assertSame(100.0, $result['top_selling_categories'][0]['value']);
        $this->assertSame(150.0, collect($result['sales_performance'])->firstWhere('month', 'Apr')['value']);
    }
}
