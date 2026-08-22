<?php

namespace App\Http\Repositories;

use App\Models\AgentMonthlyTarget;
use App\Models\Bonus;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentDashboardRepository
{
    private function customerIdentityExpression(): string
    {
        return "COALESCE(CONCAT('user:', CAST(orders.user_id AS CHAR)), CONCAT('email:', NULLIF(LOWER(orders.email), '')), CONCAT('phone:', NULLIF(orders.phone_number, '')))";
    }

    private function effectiveOrderDateExpression(): string
    {
        return 'COALESCE(orders.sold_at, orders.created_at)';
    }

    public function dashboardInfo()
    {
        $user = Auth::user();
        $commissionRate = $user->commission;
        $agentCode = $user->code;
        $now = Carbon::now();
        $year = $now->year;
        $startThisMonth = $now->copy()->startOfMonth();
        $endThisMonth = $now->copy()->endOfMonth();
        $startLastMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $endLastMonth = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $effectiveOrderDateExpression = $this->effectiveOrderDateExpression();
        $customerIdentityExpression = $this->customerIdentityExpression();

        $orders = Order::with('orderItem.product.industrialCategory')
            ->where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startThisMonth, $endThisMonth])
            ->get();

        $categoryCommissions = [];
        $totalCommissionThisMonth = 0;

        foreach ($orders as $order) {
            $orderCommission = ($order->total * $commissionRate) / 100;
            $totalCommissionThisMonth += $orderCommission;

            $itemsTotal = $order->orderItem->sum('total');

            if ($itemsTotal > 0) {
                foreach ($order->orderItem as $item) {
                    $category = $item->product?->industrialCategory;
                    $categoryName = $category
                        ? json_encode($category->getTranslations('name'))
                        : json_encode(['en' => 'Uncategorized', 'ar' => 'غير مصنف']);
                    $itemProportion = $item->total / $itemsTotal;
                    $itemCommission = $orderCommission * $itemProportion;

                    if (!isset($categoryCommissions[$categoryName])) {
                        $categoryCommissions[$categoryName] = 0;
                    }

                    $categoryCommissions[$categoryName] += $itemCommission;
                }
            } else {
                $categoryName = json_encode(['en' => 'Uncategorized', 'ar' => 'غير مصنف']);
                $categoryCommissions[$categoryName] = ($categoryCommissions[$categoryName] ?? 0) + $orderCommission;
            }
        }

        arsort($categoryCommissions);
        $topSourceName = array_key_first($categoryCommissions);
        $topSourceCommission = $categoryCommissions[$topSourceName] ?? 0;

        $topSource = (object) [
            'name' => $topSourceName,
            'commission' => $topSourceCommission,
        ];

        $monthlyTargetQuery = AgentMonthlyTarget::where('agent_id', $user->id)->first();
        $monthlyTarget = (float) ($monthlyTargetQuery?->target_amount ?? 0);
        $monthlyTargetProgress = (float) $totalCommissionThisMonth;
        $monthlyTargetProgressPercent = ($monthlyTarget > 0)
            ? round(($monthlyTargetProgress / $monthlyTarget) * 100, 2)
            : 0;

        $identifiedDeliveredOrders = Order::query()
            ->where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereRaw("{$customerIdentityExpression} IS NOT NULL");

        $newClients = (clone $identifiedDeliveredOrders)
            ->selectRaw("{$customerIdentityExpression} as customer_key")
            ->selectRaw("MIN({$effectiveOrderDateExpression}) as first_ordered_at")
            ->groupBy('customer_key')
            ->havingRaw("MIN({$effectiveOrderDateExpression}) BETWEEN ? AND ?", [$startThisMonth, $endThisMonth])
            ->get()
            ->count();

        $repeatBuyers = DB::query()
            ->fromSub(
                (clone $identifiedDeliveredOrders)
                    ->selectRaw("{$customerIdentityExpression} as customer_key")
                    ->selectRaw("MIN({$effectiveOrderDateExpression}) as first_ordered_at")
                    ->selectRaw(
                        "MAX(CASE WHEN {$effectiveOrderDateExpression} BETWEEN ? AND ? THEN 1 ELSE 0 END) as ordered_this_month",
                        [$startThisMonth, $endThisMonth]
                    )
                    ->groupBy('customer_key'),
                'customer_order_stats'
            )
            ->where('ordered_this_month', 1)
            ->where('first_ordered_at', '<', $startThisMonth)
            ->count();

        $totalClients = $newClients + $repeatBuyers;
        $conversionRate = $totalClients
            ? round($newClients / $totalClients * 100, 2)
            : 0;

        $topSellingCategories = [];
        foreach ($categoryCommissions as $name => $commission) {
            $revenue = $commissionRate > 0 ? ($commission / $commissionRate) * 100 : 0;

            $topSellingCategories[] = [
                'name' => $name,
                'value' => (float) round($revenue, 2),
            ];

            if (count($topSellingCategories) >= 3) {
                break;
            }
        }

        $pendingCommissions = Order::where('agent_code', $agentCode)
            ->whereIn('status', ['pending', 'approved', 'shipped'])
            ->sum(DB::raw("total * {$commissionRate} / 100"));

        $totalCommissionLastMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startLastMonth, $endLastMonth])
            ->sum(DB::raw("total * {$commissionRate} / 100"));

        if ($totalCommissionLastMonth > 0) {
            $percentChange = round(
                (($totalCommissionThisMonth - $totalCommissionLastMonth) / $totalCommissionLastMonth) * 100,
                2
            );
        } else {
            $percentChange = (float) $totalCommissionThisMonth;
        }

        $salesPerformance = collect(range(1, 12))->map(function ($month) use ($agentCode, $year, $effectiveOrderDateExpression) {
            $sum = Order::where('agent_code', $agentCode)
                ->where('status', 'delivered')
                ->whereYear(DB::raw($effectiveOrderDateExpression), $year)
                ->whereMonth(DB::raw($effectiveOrderDateExpression), $month)
                ->sum('total');

            return [
                'month' => Carbon::createFromFormat('!m', $month)->format('M'),
                'value' => (float) $sum,
            ];
        })->toArray();

        return [
            'total_commission_this_month' => (float) $totalCommissionThisMonth,
            'pending_commissions' => (float) $pendingCommissions,
            'total_commission_last_month' => (float) $totalCommissionLastMonth,
            'percent_change' => $percentChange,
            'top_commission_source' => [
                'name' => $topSource->name ?? null,
                'commission' => (float) ($topSource->commission ?? 0),
            ],
            'monthly_target' => (float) $monthlyTarget,
            'monthly_target_progress' => (float) $monthlyTargetProgress,
            'monthly_target_progress_percent' => (float) $monthlyTargetProgressPercent,
            'new_clients' => $newClients,
            'repeat_buyers' => $repeatBuyers,
            'conversion_rate' => $conversionRate,
            'top_selling_categories' => $topSellingCategories,
            'sales_performance' => $salesPerformance,
        ];
    }

    public function salesPerformance($year)
    {
        $user = Auth::user();
        $agentCode = $user->code;
        $effectiveOrderDateExpression = $this->effectiveOrderDateExpression();

        return collect(range(1, 12))->map(function ($month) use ($agentCode, $year, $effectiveOrderDateExpression) {
            $sum = Order::where('agent_code', $agentCode)
                ->where('status', 'delivered')
                ->whereYear(DB::raw($effectiveOrderDateExpression), $year)
                ->whereMonth(DB::raw($effectiveOrderDateExpression), $month)
                ->sum('total');

            return [
                'month' => Carbon::createFromFormat('!m', $month)->format('M'),
                'value' => (float) $sum,
            ];
        })->toArray();
    }

    public function recentOrders(int $perPage = 10)
    {
        $user = Auth::user();
        $commissionRate = $user->commission;
        $agentCode = $user->code;
        $locale = app()->getLocale();
        $effectiveOrderDateExpression = $this->effectiveOrderDateExpression();

        if ($locale === 'ar') {
            $timeSelect = DB::raw("CONCAT(DATE_FORMAT({$effectiveOrderDateExpression}, '%h:%i'), ' ', CASE WHEN DATE_FORMAT({$effectiveOrderDateExpression}, '%p')='AM' THEN 'صباحا' ELSE 'مساء' END) as time");
        } else {
            $timeSelect = DB::raw("DATE_FORMAT({$effectiveOrderDateExpression}, '%h:%i %p') as time");
        }

        return DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.agent_code', $agentCode)
            ->select(
                'orders.id',
                DB::raw("COALESCE(users.full_name, TRIM(CONCAT(COALESCE(orders.first_name, ''), ' ', COALESCE(orders.last_name, ''))), orders.email, orders.phone_number, 'Guest customer') as customer"),
                'orders.status',
                DB::raw("DATE_FORMAT({$effectiveOrderDateExpression}, '%d/%m/%Y') as date"),
                $timeSelect,
                DB::raw("ROUND(orders.total * {$commissionRate} / 100, 2) as commission")
            )
            ->orderByRaw("{$effectiveOrderDateExpression} desc")
            ->paginate($perPage);
    }

    public function commissionAndBonus()
    {
        $user = Auth::user();
        $commissionRate = $user->commission;
        $agentCode = $user->code;
        $now = Carbon::now();
        $startLastMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $endLastMonth = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $startThisMonth = $now->copy()->startOfMonth();
        $endThisMonth = $now->copy()->endOfMonth();
        $effectiveOrderDateExpression = $this->effectiveOrderDateExpression();

        $allOrders = Order::with('orderItem.product.industrialCategory')
            ->where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->get();

        $allTimeCategoryCommissions = [];
        $totalCommission = 0;

        foreach ($allOrders as $order) {
            $orderCommission = ($order->total * $commissionRate) / 100;
            $totalCommission += $orderCommission;
            $itemsTotal = $order->orderItem->sum('total');

            if ($itemsTotal > 0) {
                foreach ($order->orderItem as $item) {
                    $category = $item->product?->industrialCategory;
                    $categoryName = $category
                        ? json_encode($category->getTranslations('name'))
                        : json_encode(['en' => 'Uncategorized', 'ar' => 'غير مصنف']);
                    $itemProportion = $item->total / $itemsTotal;
                    $allTimeCategoryCommissions[$categoryName] = ($allTimeCategoryCommissions[$categoryName] ?? 0)
                        + ($orderCommission * $itemProportion);
                }
            } else {
                $categoryName = json_encode(['en' => 'Uncategorized', 'ar' => 'غير مصنف']);
                $allTimeCategoryCommissions[$categoryName] = ($allTimeCategoryCommissions[$categoryName] ?? 0) + $orderCommission;
            }
        }

        arsort($allTimeCategoryCommissions);
        $topSourceName = array_key_first($allTimeCategoryCommissions);
        $topSourceCommission = $allTimeCategoryCommissions[$topSourceName] ?? 0;
        $topSource = (object) ['name' => $topSourceName, 'commission' => $topSourceCommission];

        $pendingCommissions = Order::where('agent_code', $agentCode)
            ->whereIn('status', ['pending', 'approved', 'shipped'])
            ->sum(DB::raw("total * {$commissionRate} / 100"));

        $totalCommissionLastMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startLastMonth, $endLastMonth])
            ->sum(DB::raw("total * {$commissionRate} / 100"));

        $totalCommissionThisMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startThisMonth, $now])
            ->sum(DB::raw("total * {$commissionRate} / 100"));

        $totalOrdersThisMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startThisMonth, $endThisMonth])
            ->count();

        $averageCommissionThisMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startThisMonth, $endThisMonth])
            ->avg(DB::raw("total * {$commissionRate} / 100"));

        $highestCommissionThisMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startThisMonth, $endThisMonth])
            ->max(DB::raw("total * {$commissionRate} / 100"));

        $totalSalesThisMonth = Order::where('agent_code', $agentCode)
            ->where('status', 'delivered')
            ->whereBetween(DB::raw($effectiveOrderDateExpression), [$startThisMonth, $endThisMonth])
            ->sum('total');

        $bonus = Bonus::where('user_id', $user->id)->first();

        $commissionsPerPeriod = [];
        $currentPeriodStart = $startThisMonth->copy();

        while ($currentPeriodStart->lte($endThisMonth)) {
            $currentPeriodEnd = $currentPeriodStart->copy()->addDays(6)->endOfDay();
            if ($currentPeriodEnd->gt($endThisMonth)) {
                $currentPeriodEnd = $endThisMonth->copy()->endOfDay();
            }

            $sum = Order::where('agent_code', $agentCode)
                ->where('status', 'delivered')
                ->whereBetween(DB::raw($effectiveOrderDateExpression), [$currentPeriodStart, $currentPeriodEnd])
                ->sum(DB::raw("total * {$commissionRate} / 100"));

            $commissionsPerPeriod[] = [
                'period' => $currentPeriodStart->format('d M') . ' - ' . $currentPeriodEnd->format('d M'),
                'value' => (float) $sum,
            ];

            $currentPeriodStart = $currentPeriodEnd->copy()->addSecond();
        }

        return [
            'total_commission' => (float) $totalCommission,
            'pending_commissions' => (float) $pendingCommissions,
            'total_commission_last_month' => (float) $totalCommissionLastMonth,
            'total_commission_this_month' => (float) $totalCommissionThisMonth,
            'total_orders_this_month' => $totalOrdersThisMonth,
            'average_commission_this_month' => (float) ($averageCommissionThisMonth ?? 0),
            'highest_commission_this_month' => (float) ($highestCommissionThisMonth ?? 0),
            'total_sales_this_month' => (float) $totalSalesThisMonth,
            'bonus' => [
                'amount' => (float) ($bonus->amount ?? 0),
                'paid_at' => $bonus ? $bonus->paid_at->format('Y-M-d') : null,
            ],
            'top_commission_source' => [
                'name' => $topSource->name ?? null,
                'commission' => (float) ($topSource->commission ?? 0),
            ],
            'commissions_per_period' => $commissionsPerPeriod,
        ];
    }

    public function commissionsTimeline(int $perPage = 6)
    {
        $user = Auth::user();
        $commissionRate = $user->commission;
        $agentCode = $user->code;
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $effectiveOrderDateExpression = $this->effectiveOrderDateExpression();

        $commissions = Order::leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.agent_code', $agentCode)
            ->where('orders.status', 'delivered')
            ->select(
                'orders.id as order_number',
                DB::raw("COALESCE(users.full_name, TRIM(CONCAT(COALESCE(orders.first_name, ''), ' ', COALESCE(orders.last_name, ''))), orders.email, orders.phone_number, 'Guest customer') as user_name"),
                DB::raw("{$effectiveOrderDateExpression} as created_at"),
                DB::raw("ROUND(orders.total * {$commissionRate} / 100, 2) as total_commission")
            )
            ->orderByRaw("{$effectiveOrderDateExpression} desc")
            ->paginate($perPage);

        $commissions->getCollection()->transform(function ($item) use ($locale) {
            $item->formatted_date = Carbon::parse($item->created_at)
                ->locale($locale)
                ->translatedFormat('F d, Y');

            return $item;
        });

        return $commissions;
    }

    public function getTotalDeliveredCommissions()
    {
        return Order::join('users', 'orders.agent_code', '=', 'users.code')
            ->where('users.is_agent', true)
            ->where('orders.status', 'delivered')
            ->sum(DB::raw('orders.total * users.commission / 100'));
    }

    public function getTotalPendingCommissions()
    {
        return Order::join('users', 'orders.agent_code', '=', 'users.code')
            ->where('users.is_agent', true)
            ->whereIn('orders.status', ['pending', 'approved', 'shipped'])
            ->sum(DB::raw('orders.total * users.commission / 100'));
    }

    public function getTopSellingCategory()
    {
        $locale = app()->getLocale();

        return Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('industry_categories', 'products.industry_category_id', '=', 'industry_categories.id')
            ->where('orders.status', 'delivered')
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(industry_categories.name, '$.\"{$locale}\"')) as category_name"),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('category_name')
            ->orderByDesc('total_quantity')
            ->first();
    }

    public function getTotalOrdersCount()
    {
        return Order::count();
    }

    public function getTotalSales(): float
    {
        return (float) Order::join('users', 'orders.agent_code', '=', 'users.code')
            ->where('users.is_agent', true)
            ->where('orders.status', 'delivered')
            ->sum('orders.total');
    }
}
