<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Models\InventoryProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInventoryStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $inventoryId;

    public function __construct(int $inventoryId)
    {
        $this->inventoryId = $inventoryId;
    }

    public function handle()
    {
        info("called in UpdateInventoryStatusJob");


        $total = InventoryProduct::where('inventory_id', $this->inventoryId)->sum('quantity');
        info('total: ' . $total);

        $status = '';

        if ($total < 500) {
            $status = 'low-stock';
        } elseif ($total >= 500 && $total < 1000) {
            $status = 'normal-stock';
        } elseif ($total >= 1000) {
            $status = 'high-stock';
        }

        Inventory::where('id', $this->inventoryId)->update(['status' => $status]);
    }
}
