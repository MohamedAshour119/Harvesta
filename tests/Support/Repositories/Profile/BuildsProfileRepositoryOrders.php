<?php

namespace Tests\Support\Repositories\Profile;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

trait BuildsProfileRepositoryOrders
{
    private function createOrderWithItem(?User $user, Product $product, ProductVariant $variant, array $orderOverrides = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'user_id' => $user?->id,
            'first_name' => $user ? 'User' : 'Guest',
            'last_name' => $user ? 'Buyer' : 'Fallback',
            'email' => $user?->email ?? 'guest@example.com',
            'phone_number' => '123456789',
            'address' => 'Repository Street',
            'city' => 'Cairo',
            'province' => 'Cairo',
            'postal_code' => '11511',
            'payment_status' => 'pending',
            'status' => 'pending',
            'source' => 'customer_checkout',
            'subtotal' => 50,
            'total' => 50,
            'discount_amount' => 0,
            'agent_code' => 'SYSTEM',
        ], $orderOverrides));

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'supplier_id' => null,
            'quantity' => 1,
            'unit_price' => 50,
            'cost_plus_logistics' => 0,
            'total' => 50,
        ]);

        return $order->fresh(['items.product', 'items.variant', 'user']);
    }
}
