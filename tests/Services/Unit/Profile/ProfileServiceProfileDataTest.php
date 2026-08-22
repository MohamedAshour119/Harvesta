<?php

namespace Tests\Services\Unit\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Http\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class ProfileServiceProfileDataTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ProfileService::class;
    }

    public function test_get_profile_data_includes_agent_summary_for_agent_users(): void
    {
        $user = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-100',
            'commission' => 12.5,
            'monthly_target' => 5000,
            'last_login_at' => now(),
        ]);
        $this->actingAs($user);

        $repository = $this->mockDependency(ProfileRepository::class);
        $repository->shouldReceive('getCustomerSummary')
            ->once()
            ->with($user->id)
            ->andReturn(['total_orders' => 3, 'total_spent' => 1800.0]);
        $repository->shouldReceive('getRecentOrders')
            ->once()
            ->with($user->id)
            ->andReturn([['id' => 11]]);
        $repository->shouldReceive('getAgentSummary')
            ->once()
            ->with('AGENT-100', 12.5, $user->id)
            ->andReturn(['total_commission_this_month' => 225.0]);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->getProfileData();

        $this->assertSame($user->id, $result['user']['id']);
        $this->assertSame('AGENT-100', $result['user']['code']);
        $this->assertSame(3, $result['customer_summary']['total_orders']);
        $this->assertSame(225.0, $result['agent_summary']['total_commission_this_month']);
    }
}
