<?php

namespace Database\Seeders;

use App\Models\IndustryCategory;
use Illuminate\Database\Seeder;

class IndustryCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        $categories = [
            ['en' => 'Electronics', 'ar' => 'إلكترونيات', 'icon' => 'https://picsum.photos/seed/icon_elec/256/256'],
            ['en' => 'Home & Kitchen', 'ar' => 'المنزل والمطبخ', 'icon' => 'https://picsum.photos/seed/icon_home/256/256'],
            ['en' => 'Automotive', 'ar' => 'السيارات', 'icon' => 'https://picsum.photos/seed/icon_auto/256/256'],
            ['en' => 'Health & Beauty', 'ar' => 'الصحة والجمال', 'icon' => 'https://picsum.photos/seed/icon_health/256/256'],
            ['en' => 'Industrial supplies', 'ar' => 'المستلزمات الصناعية', 'icon' => 'https://picsum.photos/seed/icon_industrial/256/256'],
            ['en' => 'Office', 'ar' => 'مكتبي', 'icon' => 'https://picsum.photos/seed/icon_office/256/256'],
        ];

        foreach ($categories as $data) {
            $category = IndustryCategory::firstOrCreate(
                ['name->en' => $data['en']],
                [
                    'name' => [
                        'en' => $data['en'],
                        'ar' => $data['ar'],
                    ],
                ]
            );

            // attach/update icon (singleFile collection)
            if ($data['icon']) {
                // Replace existing to keep it updated
                $category->clearMediaCollection('icon');
                $category->addMediaFromUrl($data['icon'])->toMediaCollection('icon');
            }
        }
    }
}
