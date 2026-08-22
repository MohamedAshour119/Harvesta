<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a selected scenario.
     */
    public function run(): void
    {
        $scenario = env('SEED_SCENARIO', 'dashboard');

        switch ($scenario) {
            case 'customer_acquisition':
                $this->call([
                    CustomerAcquisitionMonthlyTestSeeder::class,
                ]);
                break;

            case 'dashboard':
                $this->call([
                    AgentDashboardScenarioSeeder::class,
                ]);
                break;
            default:
                $this->call([
                    // Seed base lookup data first
                    // ShahdIndustryCategoriesSeeder::class,
                    // Seed products and variants so other data can reference them
                    // ProductSeeder::class,
                    // ProductVariantSeeder::class,
                    // ImportShahdCatalogSeeder::class,
                    RandomInventoryStockSeeder::class,

                    // AgentSeeder::class,
                    // InventoryProductSeeder::class,
                    // OrderSeeder::class,
                    // RandomWeightVolumeSeeder::class,
                    // SalesPerformanceTestSeeder::class,
                    // ShippingDestinationsSeeder::class,
                    // NormalUserSeeder::class,
                ]);
                break;
        }
    }
}
