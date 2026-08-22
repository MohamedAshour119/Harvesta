<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ShippingDestinationsSeeder extends Seeder
{
    /**
     * A simple parser to extract city data from the TypeScript file.
     * This avoids complex regex and is more robust if the file format is stable.
     *
     * @param string $content
     * @return array
     */
    private function parseTsCities(string $content): array
    {
        $lines = explode("\n", $content);
        $cities = [];
    
        foreach ($lines as $line) {
            if (str_contains($line, '{ label:')) {
                if (preg_match("/value:\s*'([^']*)'/", $line, $matches)) {
                    $cities[] = ['value' => stripslashes($matches[1])];
                }
            }
        }
        return $cities;
    }
    

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the table first to avoid duplicates on re-seeding
        DB::table('shipping_destinations')->truncate();

        // Path to the Arabic translations
        $arJsonPath = resource_path('frontend/src/i18n/locales/ar/checkout.json');
        if (!File::exists($arJsonPath)) {
            $this->command->error('Arabic translation file not found at: ' . $arJsonPath);
            return;
        }
        $arJson = File::get($arJsonPath);
        $arData = json_decode($arJson, true);
        $arCities = $arData['cities'] ?? [];

        // Path to the TypeScript file with English city names
        $tsPath = resource_path('frontend/src/SaudiArabiaCities.ts');
        if (!File::exists($tsPath)) {
            $this->command->error('SaudiArabiaCities.ts file not found at: ' . $tsPath);
            return;
        }
        $tsContent = File::get($tsPath);

        // Parse the TypeScript file content to get city data
        $citiesData = $this->parseTsCities($tsContent);

        if (empty($citiesData)) {
            $this->command->error('No cities found or could not parse SaudiArabiaCities.ts');
            return;
        }

        $destinations = [];
        foreach ($citiesData as $city) {
            $englishName = $city['value'];
            // Fallback to English name if an Arabic translation is missing
            $arabicName = $arCities[$englishName] ?? $englishName;

            $destinations[] = [
                'city' => json_encode([
                    'en' => $englishName,
                    'ar' => $arabicName,
                ]),
                'rate_per_kg' => fake()->randomFloat(2, 10, 30),
                'rate_per_volume_unit' => fake()->randomFloat(2, 8, 20),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert data in chunks to be efficient
        foreach (array_chunk($destinations, 50) as $chunk) {
            DB::table('shipping_destinations')->insert($chunk);
        }

        $this->command->info('Successfully seeded ' . count($destinations) . ' shipping destinations.');
    }
}
