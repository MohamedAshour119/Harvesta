<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class RandomInventoryStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $variants = ProductVariant::query()
            ->select('id', 'product_id')
            ->orderBy('id')
            ->get();

        if ($variants->isEmpty()) {
            $this->command?->warn('No product variants found. Seed products and variants first.');

            return;
        }

        $stockPlan = $variants->map(function (ProductVariant $variant): array {
            $quantity = random_int(5, 120);

            return [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'show_alert_at_quantity' => min(
                    $quantity - 1,
                    max(3, (int) ceil($quantity * 0.2))
                ),
            ];
        });

        $plannedTotalQuantity = (int) $stockPlan->sum('quantity');

        $inventory = Inventory::query()->first();

        if (! $inventory) {
            $inventory = Inventory::query()->create([
                'name' => 'Main Warehouse',
                'maximum_quantity' => $plannedTotalQuantity + 1000,
                'location' => 'Default Warehouse',
                'status' => 'empty',
            ]);
        } elseif ((int) $inventory->maximum_quantity < $plannedTotalQuantity) {
            $inventory->update([
                'maximum_quantity' => $plannedTotalQuantity + 1000,
            ]);
        }

        InventoryProduct::withoutEvents(function () use ($stockPlan, $inventory): void {
            foreach ($stockPlan as $item) {
                InventoryProduct::query()->updateOrCreate(
                    [
                        'inventory_id' => $inventory->id,
                        'variant_id' => $item['variant_id'],
                    ],
                    [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'show_alert_at_quantity' => $item['show_alert_at_quantity'],
                        'last_sold_at' => null,
                    ]
                );
            }
        });

        $this->updateInventoryStatus($inventory);

        $this->command?->info(sprintf(
            'Seeded random stock for %d product variants into inventory "%s".',
            $stockPlan->count(),
            $inventory->name
        ));
    }

    private function updateInventoryStatus(Inventory $inventory): void
    {
        $total = (int) InventoryProduct::query()
            ->where('inventory_id', $inventory->id)
            ->sum('quantity');

        $status = match (true) {
            $total === 0 => 'empty',
            $total < 500 => 'low-stock',
            $total < 1000 => 'normal-stock',
            default => 'high-stock',
        };

        $inventory->update([
            'status' => $status,
        ]);
    }
}
