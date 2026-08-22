<?php

namespace Tests\Services\Unit\Payments;

use App\Http\Repositories\PaymentRepository;
use App\Http\Resources\CouponResource;
use App\Http\Services\PaymentService;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ServiceSmokeTestCase;

class PaymentServiceCouponTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return PaymentService::class;
    }

    public function test_apply_coupon_returns_coupon_resource_after_updating_pending_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $coupon = Coupon::create([
            'name' => 'SAVE10',
            'value' => 10,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'used_count' => 1,
            'usage_limit' => 5,
        ]);

        $repository = $this->mockDependency(PaymentRepository::class);
        $repository->shouldReceive('findCouponByName')
            ->once()
            ->with('SAVE10')
            ->andReturn($coupon);
        $repository->shouldReceive('releaseExpiredPendingCoupons')
            ->once()
            ->with(null, $coupon->id);
        $repository->shouldReceive('releaseExpiredPendingCoupons')
            ->once()
            ->with($user);
        $repository->shouldReceive('hasUsedCoupon')
            ->once()
            ->with($user, $coupon->id)
            ->andReturnFalse();
        $repository->shouldReceive('hasPendingCoupon')
            ->once()
            ->with($user, $coupon->id)
            ->andReturnFalse();
        $repository->shouldReceive('detachPendingCoupons')
            ->once()
            ->with($user);
        $repository->shouldReceive('attachCoupon')
            ->once()
            ->with($user, $coupon->id);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->applyCoupon('SAVE10');

        $this->assertInstanceOf(CouponResource::class, $result);
        $this->assertSame($coupon, $result->resource);
    }

    public function test_apply_coupon_throws_when_coupon_is_expired(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::create([
            'name' => 'OLD',
            'value' => 5,
            'status' => 'active',
            'expires_at' => now()->subMinute(),
            'used_count' => 0,
            'usage_limit' => 5,
        ]);

        $repository = $this->mockDependency(PaymentRepository::class);
        $repository->shouldReceive('findCouponByName')
            ->once()
            ->with('OLD')
            ->andReturn($coupon);

        $service = $this->instantiateService($this->serviceClass());

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(422);

        $service->applyCoupon('OLD');
    }

    public function test_check_if_coupon_applied_releases_expired_records_for_current_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $coupon = Coupon::create([
            'name' => 'ACTIVE',
            'value' => 7,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'used_count' => 0,
            'usage_limit' => 5,
        ]);

        $repository = $this->mockDependency(PaymentRepository::class);
        $repository->shouldReceive('releaseExpiredPendingCoupons')
            ->once()
            ->with($user);
        $repository->shouldReceive('checkIfCouponApplied')
            ->once()
            ->andReturn($coupon);

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame($coupon, $service->checkIfCouponApplied());
    }
}
