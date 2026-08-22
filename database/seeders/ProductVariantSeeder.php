<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        $supplierIds = Schema::hasTable('suppliers') ? Supplier::pluck('id')->all() : [];
        $variantTypeIds = Schema::hasTable('variants_type') ? DB::table('variants_type')->pluck('id')->all() : [];

        $variantImagePool = [
            'https://picsum.photos/seed/variant1/1000/1000',
            'https://picsum.photos/seed/variant2/1000/1000',
            'https://picsum.photos/seed/variant3/1000/1000',
            'https://picsum.photos/seed/variant4/1000/1000',
            'https://picsum.photos/seed/variant5/1000/1000',
        ];

        // For each product, create 1-4 variants
        Product::query()->chunk(100, function ($products) use ($faker, $supplierIds, $variantTypeIds, $variantImagePool) {
            foreach ($products as $product) {
                $num = rand(1, 4);
                for ($i = 0; $i < $num; $i++) {
                    // Generate a variant value (color/size/volume)
                    $variantValue = $faker->randomElement([
                        $faker->safeColorName(),
                        $faker->randomElement(['250ml','500ml','1L','5L']),
                        $faker->randomElement(['S','M','L','XL']),
                        $faker->randomElement(['Red','Blue','Green','Black','White']),
                    ]);

                    $price = $faker->randomFloat(2, 5, 500);
                    $discount = $faker->randomElement([0, 5, 10, 15, 20, 25, 30]);
                    $priceAfter = round($price * (1 - $discount/100), 2);

                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'supplier_id' => !empty($supplierIds) ? $faker->randomElement($supplierIds) : $product->supplier_id,
                        'variant_type_id' => !empty($variantTypeIds) ? $faker->randomElement($variantTypeIds) : null,
                        'variant_value' => (string) $variantValue,
                        'unit_price_usd' => $price,
                        'cost_plus_logistics' => $faker->optional()->randomFloat(2, 0.5, 50),
                        'discount_percentage' => $discount,
                        'price_after_discount' => $priceAfter,
                        'availability' => $faker->randomElement(['in-stock','out-of-stock']),
                        'weight' => $faker->randomFloat(2, 0.1, 10),
                        'volume' => $faker->randomFloat(2, 0.1, 5),
                    ]);

                    // Attach single variant image
                    $variant->addMediaFromUrl($faker->randomElement($variantImagePool))
                        ->toMediaCollection('variants_images');
                }
            }
        });
    }
}
