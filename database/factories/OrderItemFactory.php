<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $products    = Product::pluck('id')->toArray();
        $variants    = ProductVariant::pluck('id')->toArray();
        $suppliers   = Supplier::pluck('id')->toArray();
        $qty         = $this->faker->numberBetween(1,5);
        $unitPrice   = $this->faker->randomFloat(2,10,200);
        $logistics   = $this->faker->optional(0.5)->randomFloat(2,5,50);
        $total       = round($qty * $unitPrice, 2);

        return [
            'order_id'            => Order::factory(),
            'product_id'          => $this->faker->randomElement($products),
            'product_variant_id'  => $this->faker->optional()->randomElement($variants),
            'supplier_id'         => $this->faker->optional()->randomElement($suppliers),
            'quantity'            => $qty,
            'unit_price'          => $unitPrice,
            'cost_plus_logistics' => $logistics,
            'total'               => $total,
            'created_at'          => now(),
            'updated_at'          => now(),
        ];
    }
}
