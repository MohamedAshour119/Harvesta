<?php

namespace Tests\Services\Feature\Payments;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CouponApplyUsageCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_applying_a_coupon_increments_its_usage_count(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::query()->create([
            'name' => 'UOHP',
            'value' => 10,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/apply-coupon', [
            'coupon' => $coupon->name,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.coupon.name', 'UOHP');

        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_switching_to_another_coupon_releases_the_previous_pending_count(): void
    {
        $user = User::factory()->create();
        $firstCoupon = Coupon::query()->create([
            'name' => 'FIRST10',
            'value' => 10,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
        ]);
        $secondCoupon = Coupon::query()->create([
            'name' => 'SECOND15',
            'value' => 15,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/apply-coupon', [
            'coupon' => $firstCoupon->name,
        ])->assertOk();

        $this->postJson('/api/apply-coupon', [
            'coupon' => $secondCoupon->name,
        ])->assertOk();

        $this->assertSame(0, $firstCoupon->fresh()->used_count);
        $this->assertSame(1, $secondCoupon->fresh()->used_count);
    }

    public function test_same_user_can_reapply_the_coupon_after_24_hours_if_purchase_was_not_completed(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::query()->create([
            'name' => 'REUSE24',
            'value' => 10,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 0,
            'expires_at' => now()->addDays(2),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/apply-coupon', [
            'coupon' => $coupon->name,
        ])->assertOk();

        DB::table('coupon_user')
            ->where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->where('used_status', 'pending')
            ->update([
                'created_at' => now()->subHours(25),
                'updated_at' => now()->subHours(25),
            ]);

        $this->postJson('/api/apply-coupon', [
            'coupon' => $coupon->name,
        ])->assertOk()
            ->assertJsonPath('data.coupon.name', 'REUSE24');

        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame(1, DB::table('coupon_user')
            ->where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->where('used_status', 'pending')
            ->count());
    }

    public function test_expired_pending_coupon_is_not_returned_as_currently_applied(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::query()->create([
            'name' => 'EXPIREDHOLD',
            'value' => 10,
            'status' => 'active',
            'usage_limit' => 5,
            'used_count' => 0,
            'expires_at' => now()->addDays(2),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/apply-coupon', [
            'coupon' => $coupon->name,
        ])->assertOk();

        DB::table('coupon_user')
            ->where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->where('used_status', 'pending')
            ->update([
                'created_at' => now()->subHours(25),
                'updated_at' => now()->subHours(25),
            ]);

        $this->getJson('/api/check-if-coupon-applied')
            ->assertStatus(400);

        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertSame(0, DB::table('coupon_user')
            ->where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->where('used_status', 'pending')
            ->count());
    }
}
