<?php

namespace App\Http\Services;

class AdminIntentRouter
{
    protected AdminDataQueryService $dataService;

    public function __construct(AdminDataQueryService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Classify admin query and return relevant data.
     *
     * @return array ['intent' => string, 'data' => array, 'data_label' => string]
     */
    public function route(string $message): array
    {
        $normalized = $this->normalizeText($message);
        $period = $this->detectPeriod($normalized);
        $email = $this->extractEmail($message);

        if ($this->containsAny($normalized, [
            'first order',
            'earliest order',
            'details of first order',
            'تفاصيل الاوردر الاول',
            'تفاصيل الطلب الاول',
            'الاوردر الاول',
            'الطلب الاول',
            'اول اوردر',
            'اول طلب',
        ])) {
            $order = $this->dataService->getBoundaryOrderDetails('first');

            return [
                'intent' => 'first_order_details',
                'data' => $order ? [$order] : [],
                'data_label' => $order ? 'First Stored Order Details' : 'No orders found',
            ];
        }

        if ($this->containsAny($normalized, [
            'last order',
            'latest order',
            'most recent order',
            'تفاصيل الاوردر الاخير',
            'تفاصيل الطلب الاخير',
            'الاوردر الاخير',
            'الطلب الاخير',
            'اخر اوردر',
            'اخر طلب',
        ])) {
            $order = $this->dataService->getBoundaryOrderDetails('latest');

            return [
                'intent' => 'latest_order_details',
                'data' => $order ? [$order] : [],
                'data_label' => $order ? 'Latest Order Details' : 'No orders found',
            ];
        }

        if (
            ($email && $this->containsAny($normalized, [
                'all orders',
                'user orders',
                'customer orders',
                'orders for user',
                'orders for customer',
                'جميع الاوردرات',
                'كل الاوردرات',
                'طلبات المستخدم',
                'اوردرات المستخدم',
                'طلبات العميل',
                'اوردرات العميل',
            ])) ||
            preg_match('/\b(order|orders|اوردر|اوردرات|طلب|طلبات)\b/iu', $normalized) &&
            preg_match('/\b(user|customer|client|مستخدم|عميل)\b/iu', $normalized) &&
            ($email !== null)
        ) {
            $orders = $this->dataService->getOrdersForCustomer($email);

            return [
                'intent' => 'customer_orders',
                'data' => $orders,
                'data_label' => "Orders for customer '{$email}'",
            ];
        }

        if (preg_match('/\b(order|طلب)\s*#?\s*(\d+)\b/iu', $normalized, $matches)) {
            $order = $this->dataService->lookupOrder($matches[2]);

            return [
                'intent' => 'order_lookup',
                'data' => $order ? [$order] : [],
                'data_label' => $order ? "Order #{$matches[2]} Details" : "Order #{$matches[2]} not found",
            ];
        }

        if (preg_match('/\b(customer|client|user|عميل|مستخدم)\s+(.+)/iu', $normalized, $matches)) {
            $name = trim($matches[2]);

            return [
                'intent' => 'customer_lookup',
                'data' => $this->dataService->lookupCustomer($name),
                'data_label' => "Customer search results for '{$name}'",
            ];
        }

        if ($this->containsAny($normalized, [
            'customer performance',
            'customers performance',
            'customer analysis',
            'customer insights',
            'تحليل اداء العملاء',
            'اداء العملاء',
            'تحليل العملاء',
            'تحليل سلوك العملاء',
            'تحليل العملاء والميول',
        ])) {
            return [
                'intent' => 'customer_performance',
                'data' => $this->dataService->getCustomerPerformance($period),
                'data_label' => "Customer Performance ({$period})",
            ];
        }

        if ($this->containsAny($normalized, [
            'top customer',
            'top customers',
            'biggest buyer',
            'highest spending customers',
            'top spending customers',
            'العملاء الاكثر انفاقا',
            'العميل الاكثر انفاقا',
            'اكثر العملاء انفاقا',
            'اكبر عميل',
            'اعلى العملاء انفاقا',
            'العملاء الاكثر شراء',
        ])) {
            return [
                'intent' => 'top_customers',
                'data' => $this->dataService->getTopCustomers(10, $period),
                'data_label' => "Top Customers by Spending ({$period})",
            ];
        }

        if (
            $this->containsAny($normalized, [
                'agent performance',
                'agents performance',
                'performance of agents',
                'comparison of agents',
                'compare agents',
                'اداء الوكلاء',
                'اداء الوكيل',
                'اداء المندوب',
                'اداء المناديب',
                'مبيعات الوكلاء',
                'مبيعات الوكيل',
                'مقارنه اداء الوكلاء',
                'مقارنة اداء الوكلاء',
            ]) ||
            $this->matchesAny($normalized, [
                '/\b(agent|agents|rep|representative|مندوب|مناديب|وكيل|وكلاء)\s*(performance|sales|comparison|اداء|مبيعات|مقارنه|مقارنة)/iu',
                '/\b(performance|sales|comparison|اداء|مبيعات|مقارنه|مقارنة)\s*(agent|agents|rep|مندوب|مناديب|وكيل|وكلاء)/iu',
            ])
        ) {
            return [
                'intent' => 'agent_performance',
                'data' => $this->dataService->getAgentPerformance($period),
                'data_label' => "Agent Performance ({$period})",
            ];
        }

        if ($this->containsAny($normalized, [
            'inventory',
            'stock',
            'low stock',
            'out of stock',
            'المخزون',
            'مراقبه المخزون',
            'مراقبة المخزون',
            'نفاد المخزون',
            'المنتجات النافده',
            'المنتجات منخفضه المخزون',
        ])) {
            return [
                'intent' => 'inventory_alerts',
                'data' => $this->dataService->getInventoryAlerts(),
                'data_label' => 'Inventory Alerts',
            ];
        }

        if ($this->containsAny($normalized, [
            'sales trends',
            'trend analysis',
            'growth analysis',
            'تحليل الاتجاهات والميول',
            'الاتجاهات والميول',
            'اتجاهات المبيعات',
            'نمو المبيعات',
            'مقارنه المبيعات',
            'مقارنة المبيعات',
        ])) {
            $months = 6;

            if (preg_match('/(\d+)\s*(month|شهر)/iu', $normalized, $matches)) {
                $months = min((int) $matches[1], 12);
            }

            return [
                'intent' => 'sales_trends',
                'data' => $this->dataService->getSalesTrends($months),
                'data_label' => "Sales Trends (Last {$months} Months)",
            ];
        }

        if ($this->containsAny($normalized, [
            'top products',
            'best selling products',
            'popular products',
            'product performance',
            'تحليل اداء المنتجات',
            'اداء المنتجات',
            'المنتجات الاكثر مبيعا',
            'الاكثر مبيعا',
            'افضل المنتجات',
            'المنتجات الاعلي مبيعا',
        ])) {
            return [
                'intent' => 'top_products',
                'data' => $this->dataService->getTopProducts(10, $period),
                'data_label' => "Top Selling Products ({$period})",
            ];
        }

        if (
            $this->containsAny($normalized, [
                'order status',
                'orders status',
                'status of orders',
                'اداره الطلبات',
                'ادارة الطلبات',
                'حاله الطلبات',
                'حاله الطلب',
                'حالات الطلبات',
            ]) ||
            $this->matchesAny($normalized, [
                '/\b(order\s*status|pending|approved|shipped|delivered|cancelled|حاله\s*الطلب|معلق|شحن|تم\s*التسليم|تم\s*الموافقه|ملغي)\b/iu',
            ])
        ) {
            return [
                'intent' => 'order_status',
                'data' => $this->dataService->getOrdersByStatus($period),
                'data_label' => "Orders by Status ({$period})",
            ];
        }

        return [
            'intent' => 'sales_overview',
            'data' => $this->dataService->getSalesOverview($period),
            'data_label' => "Sales Overview ({$period})",
        ];
    }

    private function detectPeriod(string $text): string
    {
        if ($this->containsAny($text, ['today', 'اليوم'])) {
            return 'today';
        }

        if ($this->containsAny($text, ['week', 'اسبوع', 'الاسبوع'])) {
            return 'week';
        }

        if ($this->containsAny($text, ['quarter', 'ربع'])) {
            return 'quarter';
        }

        if ($this->containsAny($text, ['year', 'سنه', 'عام', 'السنه'])) {
            return 'year';
        }

        return 'month';
    }

    private function extractEmail(string $text): ?string
    {
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function matchesAny(string $text, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
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
}
