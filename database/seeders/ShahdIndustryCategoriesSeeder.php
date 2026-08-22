<?php

namespace Database\Seeders;

use App\Models\IndustryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShahdIndustryCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'en' => 'Fresh',
                'ar' => 'فرش',
                'image' => 'Fresh.webp',
            ],
            [
                'en' => 'Frozen',
                'ar' => 'مجمد',
                'image' => 'Frozen.webp',
            ],
            [
                'en' => 'Oil',
                'ar' => 'زيت',
                'image' => 'Oil.webp',
            ],
            [
                'en' => 'Fried',
                'ar' => 'مقلي',
                'image' => 'Fried.webp',
            ],
            [
                'en' => 'Frozen vegetables',
                'ar' => 'خضار مجمد',
                'image' => 'Frozen vegetables.webp',
            ],
            [
                'en' => 'Lanalou x shahd',
                'ar' => 'لانالاو × شهد',
                'image' => 'Lanalou  x shahd.webp',
            ],
            [
                'en' => 'Tea',
                'ar' => 'شاي',
                'image' => 'Tea.webp',
            ],
        ];

        foreach ($categories as $data) {
            $category = IndustryCategory::updateOrCreate(
                ['slug' => Str::slug($data['en'])],
                [
                    'name' => [
                        'en' => $data['en'],
                        'ar' => $data['ar'],
                    ],
                ]
            );

            $imagePath = public_path('categories/'.$data['image']);

            if (! file_exists($imagePath)) {
                $this->command?->warn("Missing category image: {$imagePath}");
                continue;
            }

            $category->clearMediaCollection('industry_category_images');
            $category
                ->addMedia($imagePath)
                ->preservingOriginal()
                ->toMediaCollection('industry_category_images');
        }
    }
}
