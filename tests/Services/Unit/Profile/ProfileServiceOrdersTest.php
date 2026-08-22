<?php

namespace Tests\Services\Unit\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Http\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Support\ServiceSmokeTestCase;

class ProfileServiceOrdersTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ProfileService::class;
    }

    public function test_get_paginated_orders_uses_current_user_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $paginator = new LengthAwarePaginator(
            collect([['id' => 1]]),
            1,
            15,
            1,
            ['path' => '/orders']
        );

        $repository = $this->mockDependency(ProfileRepository::class);
        $repository->shouldReceive('getPaginatedOrders')
            ->once()
            ->with($user->id, 15)
            ->andReturn($paginator);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame($paginator, $service->getPaginatedOrders(15));
    }
}
