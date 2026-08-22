<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductVariant;
use Faker\Factory as Faker;

class RandomWeightVolumeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        ProductVariant::chunk(100, function ($variants) use ($faker) {
            foreach ($variants as $variant) {
                $variant->update([
                    // generate a decimal with 2 dp, between 0.1 and 100.00
                    'weight' => $faker->randomFloat(2, 0.1, 100),
                    'volume' => $faker->randomFloat(2, 0.1, 100),
                ]);
            }
        });
    }
}