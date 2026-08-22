<?php

namespace App\Http\Repositories;

use App\Models\Order;
use App\Models\AgentMonthlyTarget;
use App\Models\Bonus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfileRepository
{
    public function getCustomerSummary(int $userId): array
    {
        $totalOrders = Order::where('user_id', $userId)->count();
        $totalSpent = Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->sum('total');

        return [
            'total_orders' => $totalOrders,
            'total_spent' => (float) $totalSpent,
        ];
    }

    public function getRecentOrders(int $userId, int $limit = 5)
    {
        return Order::where('user_id', $userId)
            ->with(['items.product', 'items.variant'])
            ->select('id', 'status', 'total', 'source', 'created_at', 'sold_at', 'invoice_uuid')
            ->orderByRaw('COALESCE(sold_at, created_at) DESC')
            ->limit($limit)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'status' => $order->status,
                'total' => (float) $order->total,
                'source' => $order->source,
                'date' => ($order->sold_at ?? $order->created_at)->format('d M Y'),
                'has_invoice' => !empty($order->invoice_uuid) || $order->source === 'customer_checkout',
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product?->name,
                    'product_slug' => $item->product?->slug,
                    'product_image' => $item->product?->getFirstMediaUrl('main_images'),
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) ($item->line_total ?: ($item->quantity * $item->unit_price)),
                    'variant_name' => $item->variant?->variant_name,
                    'variant_value' => $item->variant?->variant_value === 'Default' ? null : $item->variant?->variant_value,
                    'variant_image' => $item->variant?->getFirstMediaUrl('variants_images'),
                ]),
            ]);
    }

    public function getPaginatedOrders(int $userId, int $perPage = 10)
    {
        return Order::where('user_id', $userId)
            ->with(['items.product', 'items.variant'])
            ->select('id', 'status', 'total', 'source', 'created_at', 'sold_at', 'invoice_uuid', 'discount_amount', 'subtotal')
            ->orderByRaw('COALESCE(sold_at, created_at) DESC')
            ->paginate($perPage)
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'status' => $order->status,
                'total' => (float) $order->total,
                'subtotal' => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_amount,
                'source' => $order->source,
                'date' => ($order->sold_at ?? $order->created_at)->format('d M Y'),
                'has_invoice' => !empty($order->invoice_uuid) || $order->source === 'customer_checkout',
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product?->name,
                    'product_slug' => $item->product?->slug,
                    'product_image' => $item->product?->getFirstMediaUrl('main_images'),
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) ($item->line_total ?: ($item->quantity * $item->unit_price)),
                    'variant_name' => $item->variant?->variant_name,
                    'variant_value' => $item->variant?->variant_value === 'Default' ? null : $item->variant?->variant_value,
                    'variant_image' => $item->variant?->getFirstMediaUrl('variants_images'),
                ]),
            ]);
    }

    public function getAgentSummary(string $agentCode, float $commissionRate, int $userId): array
    {
        $now = Carbon::now();
        $startThisMonth = $now->copy()->startOfMonth();
        $endThisMonth = $now->copy()->endOfMonth();
        $effectiveDate = 'COALESCE(orders.sold_at, orders.created_at)';

        $totalCommissionThisMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveDate), [$startThisMonth, $endThisMonth])
            ->sum(DB::raw("total * {$commissionRate} / 100"));

        $monthlyTargetRecord = AgentMonthlyTarget::where('agent_id', $userId)->first();
        $monthlyTarget = (float) ($monthlyTargetRecord?->target_amount ?? 0);
        $monthlyTargetProgressPercent = $monthlyTarget > 0
            ? round(($totalCommissionThisMonth / $monthlyTarget) * 100, 2)
            : 0;

        $bonus = Bonus::where('user_id', $userId)->first();
        $bonusAmount = (float) ($bonus->amount ?? 0);
        $expectedBonus = $bonusAmount * min($monthlyTargetProgressPercent, 100) / 100;

        $totalCommissionAllTime = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->sum(DB::raw("total * {$commissionRate} / 100"));

        return [
            'total_commission_this_month' => (float) $totalCommissionThisMonth,
            'total_commission_all_time' => (float) $totalCommissionAllTime,
            'monthly_target' => $monthlyTarget,
            'monthly_target_progress_percent' => $monthlyTargetProgressPercent,
            'expected_bonus' => round($expectedBonus, 2),
        ];
    }

    public function getAgentActivity(string $agentCode, int $perPage = 10)
    {
        return Order::where('agent_code', $agentCode)
            ->with(['items.product', 'items.variant', 'user'])
            ->select('id', 'status', 'total', 'source', 'created_at', 'sold_at', 'invoice_uuid', 'user_id', 'first_name', 'last_name', 'email')
            ->orderByRaw('COALESCE(sold_at, created_at) DESC')
            ->paginate($perPage)
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'customer' => $order->user?->full_name ?: (trim($order->first_name . ' ' . $order->last_name) ?: ($order->email ?: 'Guest')),
                'status' => $order->status,
                'total' => (float) $order->total,
                'source' => $order->source,
                'date' => ($order->sold_at ?? $order->created_at)->format('d M Y'),
                'invoice_uuid' => $order->invoice_uuid,
                'has_invoice' => !empty($order->invoice_uuid) || $order->source === 'customer_checkout',
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product?->name,
                    'product_slug' => $item->product?->slug,
                    'product_image' => $item->product?->getFirstMediaUrl('main_images'),
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) ($item->line_total ?: ($item->quantity * $item->unit_price)),
                    'variant_name' => $item->variant?->variant_name,
                    'variant_value' => $item->variant?->variant_value === 'Default' ? null : $item->variant?->variant_value,
                    'variant_image' => $item->variant?->getFirstMediaUrl('variants_images'),
                ]),
            ]);
    }

    public function findOrderForUser(int $orderId, int $userId): ?Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->with('items.product')
            ->first();
    }

    public function findOrderForAgent(int $orderId, string $agentCode): ?Order
    {
        return Order::where('id', $orderId)
            ->where('agent_code', $agentCode)
            ->with('items.product')
            ->first();
    }
}
