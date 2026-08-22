<?php

namespace Tests\Services\Unit\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Http\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Support\ServiceSmokeTestCase;

class ProfileServiceAgentActivityTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ProfileService::class;
    }

    public function test_get_agent_activity_returns_null_for_non_agent_users(): void
    {
        $user = User::factory()->create([
            'is_agent' => false,
            'code' => null,
        ]);
        $this->actingAs($user);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertNull($service->getAgentActivity());
    }

    public function test_get_agent_activity_delegates_to_repository_for_agents(): void
    {
        $user = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-201',
        ]);
        $this->actingAs($user);

        $activity = new LengthAwarePaginator(
            collect([['id' => 21]]),
            1,
            10,
            1,
            ['path' => '/agent-activity']
        );

        $repository = $this->mockDependency(ProfileRepository::class);
        $repository->shouldReceive('getAgentActivity')
            ->once()
            ->with('AGENT-201', 10)
            ->andReturn($activity);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame($activity, $service->getAgentActivity(10));
    }
}
