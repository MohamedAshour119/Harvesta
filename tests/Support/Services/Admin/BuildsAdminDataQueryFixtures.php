<?php

namespace Tests\Support\Services\Admin;

use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

trait BuildsAdminDataQueryFixtures
{
    /**
     * @return array{0: Product, 1: ProductVariant}
     */
    private function createProductWithStock(
        string $englishName,
        string $slug,
        int $quantity,
        float $price,
        array $productOverrides = []
    ): array {
        $product = Product::factory()->create(array_merge([
            'status' => 'approved',
            'slug' => $slug,
            'name' => [
                'en' => $englishName,
                'ar' => $englishName . ' AR',
            ],
            'description' => [
                'en' => $englishName . ' description',
                'ar' => $englishName . ' description ar',
            ],
        ], $productOverrides));

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'availability' => $quantity > 0 ? 'in-stock' : 'out-of-stock',
            'price_after_discount' => $price,
            'unit_price_usd' => $price,
            'agent_price' => $price,
        ]);

        $inventory = Inventory::create([
            'name' => $englishName . ' Warehouse',
            'maximum_quantity' => 200,
            'location' => 'Cairo',
            'status' => 'normal-stock',
        ]);

        InventoryProduct::create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'quantity' => $quantity,
        ]);

        return [$product, $variant];
    }

    private function createOrderWithItem(
        User $customer,
        Product $product,
        ProductVariant $variant,
        array $orderOverrides = [],
        array $itemOverrides = []
    ): Order {
        $order = Order::factory()->create(array_merge([
            'user_id' => $customer->id,
            'first_name' => 'Buyer',
            'last_name' => 'One',
            'email' => $customer->email,
            'phone_number' => $customer->phone_number ?? '123456789',
            'address' => 'Admin Street',
            'city' => 'Cairo',
            'province' => 'Cairo',
            'postal_code' => '11511',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 50,
            'discount_amount' => 0,
            'total' => 50,
            'agent_code' => 'SYSTEM',
            'source' => 'customer_checkout',
        ], $orderOverrides));

        $order->items()->create(array_merge([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'supplier_id' => null,
            'quantity' => 1,
            'unit_price' => 50,
            'cost_plus_logistics' => 0,
            'total' => 50,
        ], $itemOverrides));

        return $order->fresh(['items.product', 'user']);
    }
}
