<?php

namespace App\Http\Services;

use App\Http\Requests\AgentManualOrderStoreRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgentManualOrderService
{
    public function __construct(
        protected CatalogSearchService $catalogSearchService,
        protected ManualOrderApprovalService $manualOrderApprovalService
    ) {
    }

    private const AUTO_APPROVAL_THRESHOLD = 1000;

    public function listForAgent(User $agent, int $limit = 3): Collection
    {
        return Order::query()
            ->manualSales()
            ->where('agent_code', $agent->code)
            ->with('items')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function findForAgent(User $agent, Order $order): Order
    {
        return Order::query()
            ->manualSales()
            ->where('agent_code', $agent->code)
            ->with(['items.product', 'items.variant'])
            ->findOrFail($order->id);
    }

    public function store(AgentManualOrderStoreRequest $request): Order
    {
        $agent = $request->user();
        $customerData = $request->customerSnapshot();

        return DB::transaction(function () use ($request, $agent, $customerData) {
            $products = collect($request->validated('products'))
                ->map(function (array $item) {
                    /** @var ProductVariant $variant */
                    $variant = ProductVariant::query()
                        ->with('product')
                        ->findOrFail($item['product_variant_id']);

                    return [
                        'product' => $variant->product,
                        'variant' => $variant,
                        'quantity' => (int) $item['quantity'],
                    ];
                });

            $subtotal = $products->sum(function (array $item) {
                return $item['variant']->resolveAgentPrice() * $item['quantity'];
            });

            $order = Order::create([
                ...$customerData,
                'status' => 'pending',
                'source' => 'agent_manual_sale',
                'manual_sale_status' => 'submitted',
                'sold_at' => $request->date('sold_at') ?? now(),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'discount_amount' => 0,
                'payment_status' => 'pending',
                'agent_code' => $agent->code,
                'special_sales_options' => $request->validated('special_sales_options'),
            ]);

            foreach ($products as $item) {
                /** @var Product $product */
                $product = $item['product'];
                /** @var ProductVariant $variant */
                $variant = $item['variant'];
                $quantity = $item['quantity'];
                $unitPrice = $variant->resolveAgentPrice();

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'supplier_id' => $variant->supplier_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_plus_logistics' => $variant->cost_plus_logistics,
                    'total' => $unitPrice * $quantity,
                ]);
            }

            if ($request->hasFile('proof_photo')) {
                $order->addMediaFromRequest('proof_photo')
                    ->toMediaCollection('manual_sale_proof_photo');
            }

            if ($order->total < self::AUTO_APPROVAL_THRESHOLD) {
                $order = $this->manualOrderApprovalService->approve($order, $agent, 'Auto-approved (System)');
            }

            return $order->load(['items.product', 'items.variant']);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function customerLookup(string $search): Collection
    {
        return User::query()
            ->where('is_agent', false)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->limit(10)
            ->get(['id', 'full_name', 'email', 'phone_number'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function productLookup(string $search): Collection
    {
        $products = trim($search) !== ''
            ? $this->smartSearchProducts($search)
            : Product::query()
                ->with(['variants.variantType', 'media'])
                ->where('status', 'approved')
                ->has('variants')
                ->limit(10)
                ->get();

        return $products
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->getFirstMediaUrl('main_images', 'product_card'),
                    'variants' => $product->variants->map(function (ProductVariant $variant) {
                        return [
                            'id' => $variant->id,
                            'variant_name' => $variant->variantType?->name,
                            'variant_value' => $variant->variant_value,
                            'agent_price' => $variant->resolveAgentPrice(),
                            'price_after_discount' => (float) ($variant->price_after_discount ?? 0),
                            'unit_price_usd' => (float) $variant->unit_price_usd,
                            'total_quantity' => (int) $variant->total_quantity,
                        ];
                    })->values(),
                ];
            });
    }

    private function smartSearchProducts(string $search): Collection
    {
        $searchResults = $this->catalogSearchService->search($search, 10, 10);
        $preferredResults = $searchResults['literal']->isNotEmpty()
            ? $searchResults['literal']
            : $searchResults['semantic'];

        if ($preferredResults->isEmpty()) {
            return collect();
        }

        $productIds = $preferredResults->pluck('id')
            ->filter()
            ->take(10)
            ->values();

        $productsById = Product::query()
            ->with(['variants.variantType', 'media'])
            ->where('status', 'approved')
            ->has('variants')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return $productIds
            ->map(fn (int $productId) => $productsById->get($productId))
            ->filter()
            ->values();
    }
}
