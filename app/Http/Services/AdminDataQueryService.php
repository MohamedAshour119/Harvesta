<?php

namespace App\Http\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDataQueryService
{
    private const ORDER_TOTAL_COLUMN = 'total';

    /**
     * Get sales overview for a given period.
     */
    public function getSalesOverview(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        $orders = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'total_revenue' => round($orders->sum(self::ORDER_TOTAL_COLUMN), 2),
            'order_count' => $orders->count(),
            'average_order_value' => $orders->count() > 0
                ? round($orders->sum(self::ORDER_TOTAL_COLUMN) / $orders->count(), 2)
                : 0,
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
        ];
    }

    /**
     * Get top selling products.
     */
    public function getTopProducts(int $limit = 10, string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as total_revenue'))
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled')->where('created_at', '>=', $startDate))
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $product = Product::find($item->product_id);
                return [
                    'product_name' => $product
                        ? ($product->getTranslation('name', 'en') ?: $product->getTranslation('name', 'ar'))
                        : 'Unknown Product',
                    'total_sold' => (int) $item->total_sold,
                    'total_revenue' => round($item->total_revenue, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get top customers by spending.
     */
    public function getTopCustomers(int $limit = 10, string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        return Order::select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total) as total_spent'))
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $startDate)
            ->whereHas('user', fn($query) => $query->where(function ($nested) {
                $nested->whereNull('is_agent')->orWhere('is_agent', false);
            }))
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->user_id);
                return [
                    'customer_name' => $user?->full_name ?? $user?->name ?? 'Unknown',
                    'email' => $user?->email ?? 'N/A',
                    'order_count' => (int) $item->order_count,
                    'total_spent' => round($item->total_spent, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get customer performance overview for a period.
     */
    public function getCustomerPerformance(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        $currentPeriodOrders = Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $startDate)
            ->whereHas('user', fn($query) => $query->where(function ($nested) {
                $nested->whereNull('is_agent')->orWhere('is_agent', false);
            }))
            ->get();

        $activeCustomerIds = $currentPeriodOrders
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $activeCustomers = $activeCustomerIds->count();
        $topCustomer = $this->getTopCustomers(1, $period)[0] ?? null;

        $newCustomers = 0;
        $repeatCustomers = 0;

        if ($activeCustomers > 0) {
            $firstOrderDates = Order::query()
                ->where('status', '!=', 'cancelled')
                ->whereIn('user_id', $activeCustomerIds)
                ->select('user_id', DB::raw('MIN(created_at) as first_order_at'))
                ->groupBy('user_id')
                ->get();

            $newCustomers = $firstOrderDates
                ->filter(fn($item) => Carbon::parse($item->first_order_at)->gte($startDate))
                ->count();

            $repeatCustomers = $activeCustomers - $newCustomers;
        }

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'active_customers' => $activeCustomers,
            'new_customers' => $newCustomers,
            'repeat_customers' => $repeatCustomers,
            'total_customer_orders' => $currentPeriodOrders->count(),
            'total_customer_revenue' => round($currentPeriodOrders->sum(self::ORDER_TOTAL_COLUMN), 2),
            'average_customer_spend' => $activeCustomers > 0
                ? round($currentPeriodOrders->sum(self::ORDER_TOTAL_COLUMN) / $activeCustomers, 2)
                : 0,
            'top_customer_name' => $topCustomer['customer_name'] ?? 'N/A',
            'top_customer_email' => $topCustomer['email'] ?? 'N/A',
            'top_customer_spend' => $topCustomer['total_spent'] ?? 0,
            'top_customer_orders' => $topCustomer['order_count'] ?? 0,
        ];
    }

    /**
     * Get inventory alerts (low/out of stock).
     */
    public function getInventoryAlerts(): array
    {
        $products = Product::where('status', 'approved')
            ->has('variants')
            ->with(['variants.inventoryProducts'])
            ->get();

        $alerts = [];

        foreach ($products as $product) {
            $totalStock = $product->variants->sum(fn($v) => $v->inventoryProducts->sum('quantity'));
            $name = $product->getTranslation('name', 'en') ?: $product->getTranslation('name', 'ar');

            if ($totalStock === 0) {
                $alerts[] = [
                    'product' => $name,
                    'status' => 'Out of Stock',
                    'quantity' => 0,
                ];
            } elseif ($totalStock <= 10) {
                $alerts[] = [
                    'product' => $name,
                    'status' => 'Low Stock',
                    'quantity' => $totalStock,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get orders grouped by status.
     */
    public function getOrdersByStatus(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        $counts = Order::select('status', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $knownStatuses = [
            'pending',
            'approved',
            'shipped',
            'delivered',
            'cancelled',
        ];

        $result = [
            'total_orders' => array_sum($counts),
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
        ];

        foreach ($knownStatuses as $status) {
            $result["{$status}_orders"] = (int) ($counts[$status] ?? 0);
        }

        return $result;
    }

    /**
     * Look up a specific order.
     */
    public function lookupOrder(string $query): ?array
    {
        $order = Order::where('id', $query)
            ->orWhere('invoice_uuid', $query)
            ->with(['items.product', 'user'])
            ->first();

        if (!$order) {
            return null;
        }

        return $this->formatOrderDetails($order);
    }

    /**
     * Get the first or latest stored order details.
     */
    public function getBoundaryOrderDetails(string $position = 'first'): ?array
    {
        $direction = $position === 'latest' ? 'desc' : 'asc';

        $order = Order::with(['items.product', 'user'])
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction)
            ->first();

        if (!$order) {
            return null;
        }

        return $this->formatOrderDetails($order);
    }

    private function formatOrderDetails(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'total_amount' => round($order->total, 2),
            'customer' => $order->user->full_name ?? $order->user->name ?? 'Guest',
            'created_at' => $order->created_at->format('Y-m-d H:i'),
            'items' => $order->items->map(function ($item) {
                $product = $item->product;
                return [
                    'product' => $product
                        ? ($product->getTranslation('name', 'en') ?: $product->getTranslation('name', 'ar'))
                        : 'Unknown',
                    'quantity' => $item->quantity,
                    'unit_price' => round($item->unit_price, 2),
                    'total' => round($item->total, 2),
                ];
            })->toArray(),
        ];
    }

    /**
     * Look up a customer by name, email, or phone.
     */
    public function lookupCustomer(string $query): array
    {
        $users = User::query()
            ->where(function ($builder) use ($query) {
                $builder->where('full_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->take(5)
            ->get();

        return $users->map(function ($user) {
            $orderCount = Order::where('user_id', $user->id)->where('status', '!=', 'cancelled')->count();
            $totalSpent = Order::where('user_id', $user->id)->where('status', '!=', 'cancelled')->sum(self::ORDER_TOTAL_COLUMN);

            return [
                'name' => $user->full_name ?? $user->name,
                'email' => $user->email,
                'order_count' => $orderCount,
                'total_spent' => round($totalSpent, 2),
                'is_agent' => $user->is_agent ?? false,
            ];
        })->toArray();
    }

    /**
     * Get all orders that belong to a specific customer query.
     */
    public function getOrdersForCustomer(string $query, int $limit = 20): array
    {
        $matchingUserIds = User::query()
            ->where(function ($builder) use ($query) {
                $builder->where('full_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->pluck('id');

        return Order::query()
            ->with(['items.product', 'user'])
            ->where(function ($builder) use ($query, $matchingUserIds) {
                if ($matchingUserIds->isNotEmpty()) {
                    $builder->whereIn('user_id', $matchingUserIds);
                }

                $builder->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($limit)
            ->get()
            ->map(fn(Order $order) => $this->formatOrderDetails($order))
            ->toArray();
    }

    /**
     * Get monthly sales trends.
     */
    public function getSalesTrends(int $months = 6): array
    {
        $trends = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = Carbon::now()->subMonths($i)->endOfMonth();

            $orders = Order::where('status', '!=', 'cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $trends[] = [
                'month' => $start->format('Y-m'),
                'revenue' => round($orders->sum(self::ORDER_TOTAL_COLUMN), 2),
                'orders' => $orders->count(),
            ];
        }

        return $trends;
    }

    /**
     * Get agent performance comparison.
     */
    public function getAgentPerformance(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        return User::query()
            ->where('is_agent', true)
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->get()
            ->map(function ($agent) use ($startDate) {
                $orders = Order::where('agent_code', $agent->code)
                    ->where('status', '!=', 'cancelled')
                    ->where('created_at', '>=', $startDate)
                    ->get();

                return [
                    'agent_name' => $agent->full_name ?? $agent->name ?? $agent->email ?? 'Unknown',
                    'agent_email' => $agent->email ?? 'N/A',
                    'agent_code' => $agent->code,
                    'total_orders' => $orders->count(),
                    'delivered_orders' => $orders->where('status', 'delivered')->count(),
                    'approved_orders' => $orders->where('status', 'approved')->count(),
                    'pending_orders' => $orders->where('status', 'pending')->count(),
                    'shipped_orders' => $orders->where('status', 'shipped')->count(),
                    'total_revenue' => round($orders->sum(self::ORDER_TOTAL_COLUMN), 2),
                    'commission_rate' => (float) ($agent->commission ?? 0),
                ];
            })
            ->sortByDesc('total_revenue')
            ->values()
            ->toArray();
    }

    /**
     * Search agents by email, name, or code.
     */
    public function searchAgents(string $query, int $limit = 10): array
    {
        return User::query()
            ->where('is_agent', true)
            ->where(function ($builder) use ($query) {
                $builder->where('full_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%");
            })
            ->take($limit)
            ->get()
            ->map(fn(User $agent) => [
                'agent_name' => $agent->full_name ?? $agent->email ?? 'Unknown',
                'agent_email' => $agent->email ?? 'N/A',
                'agent_code' => $agent->code,
                'commission_rate' => (float) ($agent->commission ?? 0),
            ])
            ->toArray();
    }

    /**
     * Search products by text query.
     */
    public function searchProducts(string $query, int $limit = 10): array
    {
        $terms = $this->extractSearchTerms($query);

        if (empty($terms)) {
            return [];
        }

        return Product::query()
            ->where('status', 'approved')
            ->where(function ($builder) use ($terms) {
                foreach ($terms as $term) {
                    $builder->orWhere('slug', 'like', "%{$term}%")
                        ->orWhere('name->en', 'like', "%{$term}%")
                        ->orWhere('name->ar', 'like', "%{$term}%")
                        ->orWhere('description->en', 'like', "%{$term}%")
                        ->orWhere('description->ar', 'like', "%{$term}%");
                }
            })
            ->with('variants.inventoryProducts')
            ->take($limit)
            ->get()
            ->map(function (Product $product) {
                $variant = $product->variants->sortByDesc(fn($item) => $item->inventoryProducts->sum('quantity'))->first();
                $stock = $product->variants->sum(fn($item) => $item->inventoryProducts->sum('quantity'));

                return [
                    'product_name' => $product->getTranslation('name', 'en') ?: $product->getTranslation('name', 'ar'),
                    'slug' => $product->slug,
                    'status' => $product->status,
                    'stock_quantity' => $stock,
                    'price' => round((float) ($variant?->price_after_discount ?? $variant?->unit_price_usd ?? 0), 2),
                    'availability' => $variant?->stock_availability ?? $variant?->availability ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Get recent orders summary.
     */
    public function getRecentOrders(int $limit = 5): array
    {
        return Order::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($limit)
            ->get()
            ->map(fn(Order $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'customer' => $order->user?->full_name ?? $order->email ?? 'Guest',
                'total_amount' => round((float) $order->total, 2),
                'created_at' => $order->created_at->format('Y-m-d H:i'),
            ])
            ->toArray();
    }

    /**
     * Compact platform snapshot for broad admin questions.
     */
    public function getPlatformSnapshot(string $period = 'month'): array
    {
        return [
            'sales_overview' => $this->getSalesOverview($period),
            'order_status' => $this->getOrdersByStatus($period),
            'top_products' => array_slice($this->getTopProducts(5, $period), 0, 5),
            'top_customers' => array_slice($this->getTopCustomers(5, $period), 0, 5),
            'agent_performance' => array_slice($this->getAgentPerformance($period), 0, 5),
            'inventory_alerts' => array_slice($this->getInventoryAlerts(), 0, 5),
            'recent_orders' => $this->getRecentOrders(5),
        ];
    }

    /**
     * Broader contextual data for flexible admin questions.
     */
    public function getRelevantContext(string $message, string $period = 'month'): array
    {
        $normalized = $this->normalizeText($message);
        $context = [];
        $emails = $this->extractEmails($message);

        foreach ($emails as $email) {
            $customerMatches = $this->lookupCustomer($email);
            if (!empty($customerMatches)) {
                $context['matched_customers'] = $customerMatches;
            }

            $agentMatches = $this->searchAgents($email);
            if (!empty($agentMatches)) {
                $context['matched_agents'] = $agentMatches;
            }

            $customerOrders = $this->getOrdersForCustomer($email, 10);
            if (!empty($customerOrders)) {
                $context['matched_orders'] = $customerOrders;
            }
        }

        $searchQuery = $this->extractEntitySearchText($message);
        if ($searchQuery !== null) {
            $context['matched_customers'] = $context['matched_customers'] ?? $this->lookupCustomer($searchQuery);
            $context['matched_agents'] = $context['matched_agents'] ?? $this->searchAgents($searchQuery);
            $context['matched_products'] = $context['matched_products'] ?? $this->searchProducts($searchQuery, 5);
        }

        if ($this->containsAny($normalized, ['customer', 'customers', 'عميل', 'عملاء', 'مستخدم', 'مستخدمين'])) {
            $context['customer_performance'] = $this->getCustomerPerformance($period);

            if (!isset($context['matched_customers'])) {
                if ($searchQuery !== null) {
                    $context['matched_customers'] = $this->lookupCustomer($searchQuery);
                }
            }
        }

        if ($this->containsAny($normalized, ['agent', 'agents', 'وكيل', 'وكلاء', 'مندوب', 'مناديب'])) {
            $context['agent_performance'] = array_slice($this->getAgentPerformance($period), 0, 10);

            if (!isset($context['matched_agents'])) {
                if ($searchQuery !== null) {
                    $context['matched_agents'] = $this->searchAgents($searchQuery);
                }
            }
        }

        if ($this->containsAny($normalized, ['product', 'products', 'منتج', 'منتجات', 'مخزون', 'stock', 'inventory'])) {
            $context['matched_products'] = $this->searchProducts($message, 8);
            $context['inventory_alerts'] = array_slice($this->getInventoryAlerts(), 0, 10);
            $context['top_products'] = array_slice($this->getTopProducts(5, $period), 0, 5);
        }

        if ($this->containsAny($normalized, ['order', 'orders', 'طلب', 'طلبات', 'اوردر', 'اوردرات'])) {
            $context['order_status'] = $this->getOrdersByStatus($period);

            if (!isset($context['matched_orders'])) {
                $context['recent_orders'] = $this->getRecentOrders(10);
            }
        }

        if (empty($context)) {
            $context['platform_snapshot'] = $this->getPlatformSnapshot($period);
        }

        return array_filter($context, function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }

            return $value !== null;
        });
    }

    /**
     * Helper: Get start date for a period.
     */
    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'quarter' => Carbon::now()->startOfQuarter(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function extractEmails(string $text): array
    {
        preg_match_all('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    private function extractSearchTerms(string $query): array
    {
        $normalized = $this->normalizeText($query);
        $tokens = preg_split('/[\s\-_\/,]+/u', $normalized) ?: [];
        $stopWords = [
            'all', 'for', 'with', 'from', 'this', 'that', 'about', 'show', 'give', 'details',
            'product', 'products', 'customer', 'customers', 'agent', 'agents', 'order', 'orders',
            'كل', 'جميع', 'عرض', 'هات', 'اديني', 'اعرض', 'تفاصيل', 'الخاصه', 'الخاصة', 'بالمستخدم',
            'للمستخدم', 'المستخدم', 'العميل', 'العملاء', 'الطلب', 'الطلبات', 'الاوردر', 'الاوردرات',
            'منتج', 'منتجات', 'مخزون', 'اداء', 'تحليل'
        ];

        return array_values(array_unique(array_filter($tokens, function ($token) use ($stopWords) {
            return mb_strlen($token) >= 3 && !in_array($token, $stopWords, true);
        })));
    }

    private function extractEntitySearchText(string $message): ?string
    {
        $emails = $this->extractEmails($message);
        if (!empty($emails)) {
            return $emails[0];
        }

        $terms = $this->extractSearchTerms($message);

        return empty($terms) ? null : implode(' ', array_slice($terms, 0, 4));
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text;
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace('ى', 'ي', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $this->normalizeText($keyword))) {
                return true;
            }
        }

        return false;
    }
}
