<?php

namespace App\Http\Services;

use App\Models\InventoryProduct;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InventoryAllocationService
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function allocate(array $items): void
    {
        foreach ($items as $variantData) {
            $variantId = data_get($variantData, 'productVariantId') ?? data_get($variantData, 'id');
            $quantity = (int) (data_get($variantData, 'quantity') ?? 1);

            if (!$variantId || $quantity <= 0) {
                continue;
            }

            $remaining = $quantity;
            $inventoryRecords = InventoryProduct::where('variant_id', $variantId)
                ->where('quantity', '>', 0)
                ->orderBy('quantity', 'asc')
                ->get();

            foreach ($inventoryRecords as $record) {
                if ($remaining <= 0) {
                    break;
                }

                $originalQty = (int) $record->quantity;
                $deduct = min($originalQty, $remaining);

                if ($deduct >= $originalQty) {
                    $remaining -= $deduct;
                    $record->update([
                        'quantity' => 0,
                        'last_sold_at' => now(),
                    ]);
                } else {
                    $record->decrement('quantity', $deduct);
                    $record->update(['last_sold_at' => now()]);
                    $remaining -= $deduct;
                }
            }

            if ($remaining > 0) {
                Log::warning("Insufficient stock for variant {$variantId}: {$remaining} units unfulfilled");
                throw new RuntimeException("Insufficient stock for variant {$variantId}.");
            }
        }
    }
}
