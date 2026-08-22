<?php

namespace App\Services\Order;

use App\Models\SmartBundle;

class SmartBundleService
{
    public function calculateCartCTAs(array $cartItems): array
    {
        // استخراج أرقام المنتجات من السلة
        $productIds = collect($cartItems)->map(function ($item) {
            return data_get($item, 'product_id') ?? (data_get($item, 'productId') ?? (data_get($item, 'id')));
        })->filter()->unique()->toArray();

        // جلب العروض النشطة (الخاصة بمنتجات معينة أو العامة)
        $bundles = SmartBundle::where(function ($query) use ($productIds) {
                if (!empty($productIds)) {
                    $query->whereIn('product_id', $productIds, 'and', false);
                }
                $query->orWhereNull('product_id', 'or', false);
            }, null, null, 'and')
            ->where('is_active', '=', true, 'and')
            ->get(['*']);

        // تقسيم العروض: المخصصة لمنتج معين والعروض العامة
        $productSpecificBundles = $bundles->whereNotNull('product_id')->keyBy('product_id');
        $globalBundle = $bundles->whereNull('product_id')->first();

        $ctas = [];

        foreach ($cartItems as $item) {
            $productId = data_get($item, 'product_id') ?? (data_get($item, 'productId') ?? (data_get($item, 'id')));
            $quantity = (int) (data_get($item, 'quantity') ?? 0);

            if (!$productId) continue;

            // تحديد العرض المناسب: الخاص بالمنتج أولاً، ثم العرض العام
            $bundle = $productSpecificBundles->get($productId) ?? $globalBundle;

            if ($bundle) {
                $isMeetingTarget = $quantity >= $bundle->target_quantity;
                $discountPercentage = (float) $bundle->discount_percentage;
                
                // Get the product price - we need this for calculation
                // In a more robust system, we'd pass the prices in, but for now we'll assume 
                // we want to return the discount info so the caller can apply it.
                
                $ctas[] = [
                    'product_id' => $productId,
                    'type' => $isMeetingTarget ? 'success' : 'warning',
                    'missing_quantity' => max(0, $bundle->target_quantity - $quantity),
                    'target_quantity' => $bundle->target_quantity,
                    'discount_percentage' => $discountPercentage,
                    'bundle_name' => $bundle->getTranslations('name'),
                    'is_applied' => $isMeetingTarget,
                ];
            }
        }

        return $ctas;
    }
}
