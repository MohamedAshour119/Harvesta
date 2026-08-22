<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class InventoryProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // grab all inventory IDs (you currently have 3)
        $inventoryIds = Inventory::pluck('id')->all();

        // loop every variant
        ProductVariant::chunk(100, function ($variants) use ($inventoryIds) {
            foreach ($variants as $variant) {
                foreach ($inventoryIds as $inventoryId) {
                    InventoryProduct::create([
                        'product_id'    => $variant->product_id,
                        'variant_id'    => $variant->id,
                        'inventory_id'  => $inventoryId,
                        'quantity'      => rand(0, 500),    // ← adjust max as you like
                        'last_sold_at'  => null,
                    ]);
                }
            }
        });
    }
}
