<?php

namespace Tests\Repositories\Agent;

use App\Http\Repositories\AgentDashboardRepository;
use App\Models\Bonus;
use App\Models\IndustryCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Tests\Support\Repositories\Agent\BuildsAgentDashboardFixtures;
use Tests\Support\RepositoryTestCase;

class AgentDashboardRepositoryCommissionTest extends RepositoryTestCase
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

    public function test_commission_and_bonus_and_commissions_timeline_include_aggregated_values_and_formatted_dates(): void
    {
        Carbon::setTestNow('2026-04-20 12:00:00');

        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-DASH-3',
            'commission' => 10,
        ]);
        $this->actingAs($agent);

        Bonus::query()->create([
            'user_id' => $agent->id,
            'amount' => 40,
            'paid_at' => now(),
        ]);

        $cleaning = IndustryCategory::query()->create([
            'name' => ['en' => 'Cleaning', 'ar' => 'ØªÙ†Ø¸ÙŠÙ'],
        ]);
        $packaging = IndustryCategory::query()->create([
            'name' => ['en' => 'Packaging', 'ar' => 'ØªØºÙ„ÙŠÙ'],
        ]);

        $thisMonthA = $this->createAgentOrder($agent->code, 100, 'delivered', now()->subDays(6));
        $this->attachOrderItem($thisMonthA, $cleaning, 100, 1);

        $thisMonthB = $this->createAgentOrder($agent->code, 200, 'delivered', now()->subDays(2));
        $this->attachOrderItem($thisMonthB, $packaging, 200, 1);

        $lastMonth = $this->createAgentOrder($agent->code, 80, 'delivered', now()->subMonth()->startOfMonth()->addDays(3));
        $this->attachOrderItem($lastMonth, $cleaning, 80, 1);

        $pending = $this->createAgentOrder($agent->code, 50, 'approved', now()->subDay());
        $this->attachOrderItem($pending, $packaging, 50, 1);

        $summary = $this->repository->commissionAndBonus();
        $timeline = $this->repository->commissionsTimeline(2);

        $this->assertSame(38.0, $summary['total_commission']);
        $this->assertSame(5.0, $summary['pending_commissions']);
        $this->assertSame(8.0, $summary['total_commission_last_month']);
        $this->assertSame(30.0, $summary['total_commission_this_month']);
        $this->assertSame(2, $summary['total_orders_this_month']);
        $this->assertSame(15.0, $summary['average_commission_this_month']);
        $this->assertSame(20.0, $summary['highest_commission_this_month']);
        $this->assertSame(300.0, $summary['total_sales_this_month']);
        $this->assertSame(40.0, $summary['bonus']['amount']);
        $this->assertSame(json_encode($packaging->getTranslations('name')), $summary['top_commission_source']['name']);
        $this->assertSame(20.0, $summary['top_commission_source']['commission']);
        $this->assertSame(30.0, collect($summary['commissions_per_period'])->sum('value'));
        $this->assertSame(3, $timeline->total());
        $this->assertNotEmpty($timeline->items()[0]->formatted_date);
        $this->assertSame(20.0, (float) $timeline->items()[0]->total_commission);
    }
}
