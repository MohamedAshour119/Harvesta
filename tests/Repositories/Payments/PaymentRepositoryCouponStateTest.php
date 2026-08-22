<?php

namespace Tests\Repositories\Payments;

use App\Http\Repositories\PaymentRepository;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\RepositoryTestCase;

class PaymentRepositoryCouponStateTest extends RepositoryTestCase
{
    private PaymentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(PaymentRepository::class);
    }

    public function test_find_coupon_by_name_and_coupon_status_checks_use_pivot_state(): void
    {
        $user = User::factory()->create();
        $usedCoupon = Coupon::query()->create([
            'name' => 'USED10',
            'value' => 10,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 1,
            'expires_at' => now()->addDay(),
        ]);
        $pendingCoupon = Coupon::query()->create([
            'name' => 'PENDING10',
            'value' => 10,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 1,
            'expires_at' => now()->addDay(),
        ]);

        DB::table('coupon_user')->insert([
            [
                'user_id' => $user->id,
                'coupon_id' => $usedCoupon->id,
                'used_status' => 'used',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'coupon_id' => $pendingCoupon->id,
                'used_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame($usedCoupon->id, $this->repository->findCouponByName('USED10')?->id);
        $this->assertTrue($this->repository->hasUsedCoupon($user, $usedCoupon->id));
        $this->assertTrue($this->repository->hasPendingCoupon($user, $pendingCoupon->id));
    }

    public function test_release_expired_pending_coupons_decrements_usage_and_deletes_only_expired_rows(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::query()->create([
            'name' => 'EXPIRED',
            'value' => 15,
            'status' => 'active',
            'usage_limit' => 10,
            'used_count' => 3,
            'expires_at' => now()->addDay(),
        ]);

        DB::table('coupon_user')->insert([
            [
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'used_status' => 'pending',
                'created_at' => now()->subHours(25),
                'updated_at' => now()->subHours(25),
            ],
            [
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'used_status' => 'pending',
                'created_at' => now()->subHours(26),
                'updated_at' => now()->subHours(26),
            ],
            [
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'used_status' => 'pending',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
        ]);

        $this->repository->releaseExpiredPendingCoupons($user, $coupon->id);

        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame(1, DB::table('coupon_user')->where('coupon_id', $coupon->id)->count());
    }

    public function test_detach_pending_coupons_clears_user_rows_and_updates_usage_counts(): void
    {
        $user = User::factory()->create();
        $couponA = Coupon::query()->create([
            'name' => 'A',
            'value' => 5,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 2,
            'expires_at' => now()->addDay(),
        ]);
        $couponB = Coupon::query()->create([
            'name' => 'B',
            'value' => 7,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 1,
            'expires_at' => now()->addDay(),
        ]);

        DB::table('coupon_user')->insert([
            [
                'user_id' => $user->id,
                'coupon_id' => $couponA->id,
                'used_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'coupon_id' => $couponA->id,
                'used_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'coupon_id' => $couponB->id,
                'used_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->repository->detachPendingCoupons($user);

        $this->assertSame(0, DB::table('coupon_user')->where('user_id', $user->id)->count());
        $this->assertSame(0, $couponA->fresh()->used_count);
        $this->assertSame(0, $couponB->fresh()->used_count);
    }

    public function test_attach_coupon_increments_usage_and_check_if_coupon_applied_returns_pending_coupon(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::query()->create([
            'name' => 'ATTACH',
            'value' => 10,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
        ]);
        Auth::login($user);

        $this->repository->attachCoupon($user, $coupon->id);
        $result = $this->repository->checkIfCouponApplied();

        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame($coupon->id, $result->id);
    }

    public function test_check_if_coupon_applied_throws_when_user_has_no_pending_coupon(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No coupon applied');

        $this->repository->checkIfCouponApplied();
    }
}
