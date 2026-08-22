<?php

namespace Tests\Repositories\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Models\AgentMonthlyTarget;
use App\Models\Bonus;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Tests\Support\RepositoryTestCase;

class ProfileRepositoryAgentSummaryTest extends RepositoryTestCase
{
    private ProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProfileRepository::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_get_agent_summary_calculates_current_month_all_time_and_expected_bonus(): void
    {
        Carbon::setTestNow('2026-04-20 12:00:00');

        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-PROFILE-1',
        ]);
        AgentMonthlyTarget::query()->create([
            'agent_id' => $agent->id,
            'target_amount' => 200,
            'achieved_amount' => 0,
            'reset_at' => now()->endOfMonth()->toDateString(),
        ]);
        Bonus::query()->create([
            'user_id' => $agent->id,
            'amount' => 50,
            'paid_at' => now(),
        ]);

        Order::factory()->create([
            'agent_code' => 'AGENT-PROFILE-1',
            'status' => 'delivered',
            'total' => 1000,
            'subtotal' => 1000,
            'created_at' => now()->subDay(),
            'sold_at' => now()->subDay(),
        ]);
        Order::factory()->create([
            'agent_code' => 'AGENT-PROFILE-1',
            'status' => 'delivered',
            'total' => 500,
            'subtotal' => 500,
            'created_at' => now()->subMonth()->startOfMonth()->addDay(),
            'sold_at' => now()->subMonth()->startOfMonth()->addDay(),
        ]);

        $summary = $this->repository->getAgentSummary('AGENT-PROFILE-1', 10, $agent->id);

        $this->assertSame(100.0, $summary['total_commission_this_month']);
        $this->assertSame(150.0, $summary['total_commission_all_time']);
        $this->assertSame(50.0, $summary['monthly_target_progress_percent']);
        $this->assertSame(25.0, $summary['expected_bonus']);
    }
}
