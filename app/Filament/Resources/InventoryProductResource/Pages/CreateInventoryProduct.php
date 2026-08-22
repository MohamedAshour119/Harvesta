<?php

namespace App\Filament\Resources\InventoryProductResource\Pages;

use App\Filament\Resources\InventoryProductResource;
use App\Jobs\UpdateInventoryStatusJob;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInventoryProduct extends CreateRecord
{
    protected static string $resource = InventoryProductResource::class;

    /**
     * Override Filament’s record-creation hook.
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Loop through repeater items: upsert quantity if exists, else create
        $modelClass = $this->getModel();
        $lastModel = null;
        $inventoryIds = [];
        foreach ($data['inventory_items'] as $item) {
            $inventoryIds[] = $item['inventory_id'];
            // find existing record
            $existing = $modelClass::where('inventory_id', $item['inventory_id'])
                ->where('product_id', $item['product_id'])
                ->where('variant_id', $item['variant_id'])
                ->first();
            if ($existing) {
                // update quantity
                $existing->increment('quantity', $item['quantity']);
                $existing->update([
                    'show_alert_at_quantity' => $item['show_alert_at_quantity'],
                ]);
                $lastModel = $existing;
            } else {
                // create new
                $lastModel = $modelClass::create([
                    'product_id'   => $item['product_id'],
                    'variant_id'   => $item['variant_id'],
                    'quantity'     => $item['quantity'],
                    'inventory_id' => $item['inventory_id'],
                    'show_alert_at_quantity' => $item['show_alert_at_quantity'],
                ]);
            }
        }

        // Return one model to satisfy Filament (it only needs one)
        // If no items, you can throw or return a new instance
        return $lastModel ?? new ($this->getModel());
    }
}
