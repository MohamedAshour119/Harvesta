<?php

namespace App\Http\Services;

use App\Http\Repositories\PaymentRepository;
use App\Http\Resources\CouponResource;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Support\Facades\Auth;

class PaymentService
{

    public function __construct(protected PaymentRepository $paymentRepository) {}

    public function applyCoupon(string $couponName, $isValidateInOrderRequest = false)
    {
        $user = Auth::user();
        $coupon = $this->paymentRepository->findCouponByName($couponName);

        if (!$coupon || $coupon->status === 'in-active') {
            throw new Exception(__('translations.invalid-coupon'), 422);
        }

        if (now()->isAfter($coupon->expires_at)) {
            throw new Exception(__('translations.coupon-expired'), 422);
        }

        $this->paymentRepository->releaseExpiredPendingCoupons(couponId: $coupon->id);
        $this->paymentRepository->releaseExpiredPendingCoupons(user: $user);

        if ($this->paymentRepository->hasUsedCoupon($user, $coupon->id)) {
            throw new Exception(__('translations.coupon-already-used'), 409);
        }

        if (!$isValidateInOrderRequest && $this->paymentRepository->hasPendingCoupon($user, $coupon->id)) {
            throw new Exception(__('translations.coupon-already-applied'), 409);
        }

        if ($coupon->used_count >= $coupon->usage_limit) {
            throw new Exception(__('translations.coupon-reached-limit'), 422);
        }

        $this->paymentRepository->detachPendingCoupons($user);
        $this->paymentRepository->attachCoupon($user, $coupon->id);

        return new CouponResource($coupon);
    }

    public function checkIfCouponApplied()
    {
        $this->paymentRepository->releaseExpiredPendingCoupons(Auth::user());

        return $this->paymentRepository->checkIfCouponApplied();
    }

    public function getShippingCost(array $data)
    {
        return $this->paymentRepository->getShippingCost($data);
    }

    public function ensureOrderMinimumValue(array $productsVariants)
    {
        $totalAmount = 0;
        foreach ($productsVariants as $variantData) {
            $variantId = $variantData['productVariantId'] ?? $variantData['id'] ?? null;
            $quantity = $variantData['quantity'] ?? 1;
            if (!$variantId || $quantity <= 0) {
                continue;
            }
            $productVariantModel = ProductVariant::findOrFail($variantId);
            $totalAmount += $productVariantModel->price_after_discount * $quantity;
        }

        if ($totalAmount < 50) {
            return false;
        } else {
            return true;
        }
    }
}
