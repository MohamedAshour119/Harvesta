<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $variantTypes = ['Color', 'Size', 'Capacity'];
        $variantValues = [
            'Color' => ['Red', 'Blue', 'Black', 'White'],
            'Size' => ['S', 'M', 'L', 'XL'],
            'Capacity' => ['250ml', '500ml', '1L'],
        ];

        $variantName = $this->faker->randomElement($variantTypes);
        $variantValue = $this->faker->randomElement($variantValues[$variantName]);

        $unitPrice = $this->faker->randomFloat(2, 1, 100);
        $discountPercentage = $this->faker->boolean(50) ? $this->faker->randomFloat(2, 5, 50) : 0;
        $priceAfterDiscount = round($unitPrice * (1 - $discountPercentage / 100), 2);

        return [
            'variant_value' => $variantValue,
            'unit_price_usd' => $unitPrice,
            'agent_price' => $unitPrice,
            'discount_percentage' => $discountPercentage,
            'price_after_discount' => $priceAfterDiscount,
            'availability' => $this->faker->randomElement(['in-stock', 'out-of-stock']),
            'weight' => $this->faker->randomFloat(2, 0.1, 10),
            'volume' => $this->faker->randomFloat(2, 0.1, 5),
        ];
    }
}
