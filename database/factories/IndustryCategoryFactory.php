<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\IndustryCategory;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IndustryCategory>
 */
class IndustryCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = IndustryCategory::class;

    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->unique()->word,
                'ar' => 'صناعة ' . $this->faker->unique()->word,
            ],
        ];
    }
}
