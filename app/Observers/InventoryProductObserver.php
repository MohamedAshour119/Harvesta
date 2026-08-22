<?php

namespace App\Observers;

use App\Jobs\UpdateInventoryStatusJob;
use App\Models\InventoryProduct;

class InventoryProductObserver
{
    /**
     * Handle the InventoryProduct "created" event.
     */
    public function created(InventoryProduct $ip): void
    {
        UpdateInventoryStatusJob::dispatch($ip->inventory_id);
    }

    /**
     * Handle the InventoryProduct "updated" event.
     */
    public function updated(InventoryProduct $ip): void
    {
        UpdateInventoryStatusJob::dispatch($ip->inventory_id);
    }

    /**
     * Handle the InventoryProduct "deleted" event.
     */
    public function deleted(InventoryProduct $ip): void
    {
        UpdateInventoryStatusJob::dispatch($ip->inventory_id);
    }

    /**
     * Handle the InventoryProduct "restored" event.
     */
    public function restored(InventoryProduct $inventoryProduct): void
    {
        //
    }

    /**
     * Handle the InventoryProduct "force deleted" event.
     */
    public function forceDeleted(InventoryProduct $inventoryProduct): void
    {
        //
    }
}
