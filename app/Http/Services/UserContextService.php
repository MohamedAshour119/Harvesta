<?php

namespace App\Http\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;

class UserContextService
{
    /**
     * Build user context for chatbot personalization.
     * Cached for 15 minutes per user.
     *
     * @param int $userId
     * @return array
     */
    public function buildUserContext(int $userId): array
    {
        return Cache::remember("chatbot_user_ctx_{$userId}", 900, function () use ($userId) {
            $user = User::find($userId);

            if (!$user) {
                return [];
            }

            // Get order stats
            $orders = Order::where('user_id', $userId)
                ->where('status', '!=', 'cancelled')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalOrders = $orders->count();
            $lastOrderDate = $orders->first()?->created_at?->format('Y-m-d') ?? 'N/A';
            $totalSpent = $orders->sum(function (Order $order) {
                return (float) ($order->total_amount ?? $order->total ?? 0);
            });

            // Get frequently ordered products (top 5)
            $frequentProducts = OrderItem::whereIn('order_id', $orders->pluck('id'))
                ->with('product')
                ->selectRaw('product_id, COUNT(*) as order_count')
                ->groupBy('product_id')
                ->orderByDesc('order_count')
                ->take(5)
                ->get()
                ->map(function ($item) {
                    $product = $item->product;
                    if ($product) {
                        return $product->getTranslation('name', app()->getLocale())
                            ?: $product->getTranslation('name', 'en');
                    }
                    return null;
                })
                ->filter()
                ->values()
                ->toArray();

            // Get last ordered products (last 3)
            $lastOrderedProducts = [];
            $lastOrder = $orders->first();
            if ($lastOrder) {
                $lastOrderedProducts = OrderItem::where('order_id', $lastOrder->id)
                    ->with('product')
                    ->get()
                    ->map(function ($item) {
                        $product = $item->product;
                        if ($product) {
                            return $product->getTranslation('name', app()->getLocale())
                                ?: $product->getTranslation('name', 'en');
                        }
                        return null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();
            }

            return [
                'name' => $user->full_name ?? $user->name ?? 'Customer',
                'total_orders' => $totalOrders,
                'last_order_date' => $lastOrderDate,
                'total_spent' => round($totalSpent, 2),
                'frequently_ordered_products' => $frequentProducts,
                'last_ordered_products' => $lastOrderedProducts,
            ];
        });
    }
}
