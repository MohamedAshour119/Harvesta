<?php

namespace Database\Seeders;

use App\Models\IndustryCategory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Optional: pick random existing categories/suppliers if tables exist
        $categoryIds = Schema::hasTable('industry_categories') ? IndustryCategory::pluck('id')->all() : [];
        $supplierIds = Schema::hasTable('suppliers') ? Supplier::pluck('id')->all() : [];

        // Sample images (royalty-free placeholders)
        $mainImageUrls = [
            'https://picsum.photos/seed/product_main_1/1200/900',
            'https://picsum.photos/seed/product_main_2/1200/900',
            'https://picsum.photos/seed/product_main_3/1200/900',
            'https://picsum.photos/seed/product_main_4/1200/900',
            'https://picsum.photos/seed/product_main_5/1200/900',
        ];
        $galleryImageUrls = [
            'https://picsum.photos/seed/product_gallery_1/1200/900',
            'https://picsum.photos/seed/product_gallery_2/1200/900',
            'https://picsum.photos/seed/product_gallery_3/1200/900',
            'https://picsum.photos/seed/product_gallery_4/1200/900',
            'https://picsum.photos/seed/product_gallery_5/1200/900',
            'https://picsum.photos/seed/product_gallery_6/1200/900',
        ];

        // Create a handful of products with translations and media
        $count = 10;
        for ($i = 0; $i < $count; $i++) {
            $englishName = ucfirst($faker->unique()->words(rand(2, 4), true));
            $arabicName = "منتج " . ($i + 1);

            $product = Product::create([
                'industry_category_id' => !empty($categoryIds) ? $faker->randomElement($categoryIds) : null,
                'name' => [
                    'en' => $englishName,
                    'ar' => $arabicName,
                ],
                'description' => [
                    'en' => $faker->paragraphs(2, true),
                    'ar' => 'وصف المنتج ' . ($i + 1),
                ],
                'min_order_value' => $faker->randomFloat(2, 20, 500),
                'supplier_id' => !empty($supplierIds) ? $faker->randomElement($supplierIds) : null,
                'is_approved' => $faker->boolean(80),
                'status' => $faker->randomElement(['pending', 'approved']),
                'oem' => $faker->boolean(20),
                'notes' => $faker->optional()->sentence(),
                // stock_availability, order_count, minimum_order_quantity filled by migrations/defaults
                'minimum_order_quantity' => $faker->optional()->numberBetween(1, 50),
            ]);

            // Attach main image (single file collection)
            $product->addMediaFromUrl($faker->randomElement($mainImageUrls))
                ->toMediaCollection('main_images');

            // Attach 2-4 gallery images
            $galleryCount = rand(2, 4);
            $picked = $faker->randomElements($galleryImageUrls, $galleryCount);
            foreach ($picked as $url) {
                $product->addMediaFromUrl($url)
                    ->toMediaCollection('products_other_images');
            }
        }
    }
}
