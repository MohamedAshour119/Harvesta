<?php

namespace App\Http\Repositories;

use App\Http\Resources\UserResource;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\ShippingDestination;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class PaymentRepository
{
    protected const COUPON_PENDING_HOLD_HOURS = 24;

    public function findCouponByName(string $couponName): ?Coupon
    {
        $query = Coupon::query();

        if (DB::getDriverName() === 'mysql') {
            return $query->whereRaw('BINARY `name` = ?', [$couponName])->first();
        }

        return $query->where('name', $couponName)->first();
    }
    

    public function hasUsedCoupon(User $user, int $couponId): bool
    {
        return $user->coupons()
            ->where('coupon_id', $couponId)
            ->where('used_status', 'used')
            ->exists();
    }

    public function hasPendingCoupon(User $user, int $couponId): bool
    {
        return $user->coupons()
            ->where('coupon_id', $couponId)
            ->where('used_status', 'pending')
            ->exists();
    }

    public function releaseExpiredPendingCoupons(?User $user = null, ?int $couponId = null): void
    {
        DB::transaction(function () use ($user, $couponId): void {
            $expiredPendingCoupons = DB::table('coupon_user')
                ->select('coupon_id', DB::raw('COUNT(*) as pending_count'))
                ->where('used_status', 'pending')
                ->where('created_at', '<=', now()->subHours(self::COUPON_PENDING_HOLD_HOURS))
                ->when($user, fn ($query) => $query->where('user_id', $user->id))
                ->when($couponId, fn ($query) => $query->where('coupon_id', $couponId))
                ->groupBy('coupon_id')
                ->get();

            if ($expiredPendingCoupons->isEmpty()) {
                return;
            }

            foreach ($expiredPendingCoupons as $expiredPendingCoupon) {
                $coupon = Coupon::query()->lockForUpdate()->find($expiredPendingCoupon->coupon_id);

                if (! $coupon) {
                    continue;
                }

                $coupon->update([
                    'used_count' => max(0, (int) $coupon->used_count - (int) $expiredPendingCoupon->pending_count),
                ]);
            }

            DB::table('coupon_user')
                ->where('used_status', 'pending')
                ->where('created_at', '<=', now()->subHours(self::COUPON_PENDING_HOLD_HOURS))
                ->when($user, fn ($query) => $query->where('user_id', $user->id))
                ->when($couponId, fn ($query) => $query->where('coupon_id', $couponId))
                ->delete();
        });
    }

    public function detachPendingCoupons(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $pendingCoupons = DB::table('coupon_user')
                ->select('coupon_id', DB::raw('COUNT(*) as pending_count'))
                ->where('user_id', $user->id)
                ->where('used_status', 'pending')
                ->groupBy('coupon_id')
                ->get();

            if ($pendingCoupons->isNotEmpty()) {
                foreach ($pendingCoupons as $pendingCoupon) {
                    $coupon = Coupon::query()->lockForUpdate()->find($pendingCoupon->coupon_id);

                    if (! $coupon) {
                        continue;
                    }

                    $coupon->update([
                        'used_count' => max(0, (int) $coupon->used_count - (int) $pendingCoupon->pending_count),
                    ]);
                }

                DB::table('coupon_user')
                    ->where('user_id', $user->id)
                    ->where('used_status', 'pending')
                    ->delete();
            }
        });
    }

    public function attachCoupon(User $user, int $couponId): void
    {
        DB::transaction(function () use ($user, $couponId): void {
            DB::table('coupon_user')->insert([
                'user_id' => $user->id,
                'coupon_id' => $couponId,
                'used_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Coupon::query()
                ->whereKey($couponId)
                ->increment('used_count');
        });
    }

    public function checkIfCouponApplied()
    {
        $user = Auth::user();
        $coupon = $user->coupons()->where('used_status', 'pending')->first();

        if (!$coupon) {
            throw new Exception('No coupon applied');
        }

        return $coupon;
    }

    public function getShippingCost(array $data)
    {
        $city = ShippingDestination::where('city->en', $data['city'])->first();

        if (!$city) {
            throw new Exception('City not found');
        }

        $totalShippingCost = 0;
        $items = $data['items'];

        foreach ($items as $item) {
            $productVariant = ProductVariant::find($item['productVariantId']);

            if ($productVariant) {
                $quantity = max((int) ($item['quantity'] ?? 0), 0);
                $weight = (float) ($productVariant->weight ?? 0);
                $volume = (float) ($productVariant->volume ?? 0);

                $weightCost = $weight * (float) $city->rate_per_kg * $quantity;
                $volumeCost = $volume * (float) $city->rate_per_volume_unit * $quantity;

                // Many imported variants still have zero dimensions, so use a minimum
                // per-item shipping charge instead of returning zero.
                if ($weight <= 0 && $volume <= 0) {
                    $totalShippingCost += (float) $city->rate_per_kg * $quantity;
                    continue;
                }

                $totalShippingCost += max($weightCost, $volumeCost);
            }
        }
        
        $totalShippingCost = round($totalShippingCost, 2);
        return $totalShippingCost;
    }
}
