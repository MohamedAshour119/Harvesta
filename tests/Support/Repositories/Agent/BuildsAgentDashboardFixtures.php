<?php

namespace Tests\Support\Repositories\Agent;

use App\Models\IndustryCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;

trait BuildsAgentDashboardFixtures
{
    private function createCategory(string $englishName): IndustryCategory
    {
        return IndustryCategory::query()->create([
            'name' => ['en' => $englishName, 'ar' => $englishName],
        ]);
    }

    private function createAgentOrder(
        string $agentCode,
        float $total,
        string $status,
        Carbon $effectiveDate,
        ?User $user = null,
        array $overrides = []
    ): Order {
        return Order::factory()->create(array_merge([
            'user_id' => $user?->id,
            'first_name' => 'Repo',
            'last_name' => 'Buyer',
            'email' => $user?->email ?? 'repo@example.com',
            'phone_number' => '123456789',
            'address' => 'Dashboard Street',
            'city' => 'Cairo',
            'province' => 'Cairo',
            'postal_code' => '11511',
            'payment_status' => $status === 'delivered' ? 'completed' : 'pending',
            'status' => $status,
            'source' => 'customer_checkout',
            'subtotal' => $total,
            'total' => $total,
            'discount_amount' => 0,
            'agent_code' => $agentCode,
            'created_at' => $effectiveDate,
            'updated_at' => $effectiveDate,
            'sold_at' => $effectiveDate,
        ], $overrides));
    }

    private function attachOrderItem(Order $order, IndustryCategory $category, float $lineTotal, int $quantity): void
    {
        $product = Product::factory()->create([
            'industry_category_id' => $category->id,
            'status' => 'approved',
            'name' => ['en' => $category->getTranslation('name', 'en') . ' Product', 'ar' => $category->getTranslation('name', 'ar')],
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'supplier_id' => null,
            'quantity' => $quantity,
            'unit_price' => $quantity > 0 ? $lineTotal / $quantity : $lineTotal,
            'cost_plus_logistics' => 0,
            'total' => $lineTotal,
        ]);
    }
}
