<?php

namespace Tests\Services\Feature\Inventory;

use App\Http\Services\InventoryAllocationService;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InventoryAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocate_decrements_inventory_records(): void
    {
        $variant = $this->createVariant();

        InventoryProduct::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'inventory_id' => $this->createInventory()->id,
            'quantity' => 5,
        ]);

        app(InventoryAllocationService::class)->allocate([
            ['productVariantId' => $variant->id, 'quantity' => 3],
        ]);

        $this->assertDatabaseHas('inventory_product', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_allocate_throws_when_stock_is_insufficient(): void
    {
        $variant = $this->createVariant();

        InventoryProduct::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'inventory_id' => $this->createInventory()->id,
            'quantity' => 1,
        ]);

        $this->expectException(RuntimeException::class);

        app(InventoryAllocationService::class)->allocate([
            ['productVariantId' => $variant->id, 'quantity' => 2],
        ]);
    }

    public function test_allocate_keeps_inventory_record_when_quantity_reaches_zero(): void
    {
        $variant = $this->createVariant();
        $inventory = $this->createInventory();

        $inventoryProduct = InventoryProduct::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'quantity' => 1,
        ]);

        app(InventoryAllocationService::class)->allocate([
            ['productVariantId' => $variant->id, 'quantity' => 1],
        ]);

        $this->assertDatabaseHas('inventory_product', [
            'id' => $inventoryProduct->id,
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'quantity' => 0,
        ]);
    }

    private function createVariant(): ProductVariant
    {
        $product = Product::factory()->create([
            'status' => 'approved',
        ]);

        return ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_after_discount' => 10,
            'unit_price_usd' => 10,
        ]);
    }

    private function createInventory(): Inventory
    {
        return Inventory::create([
            'name' => 'Main Warehouse',
            'maximum_quantity' => 100,
            'location' => 'Riyadh',
            'status' => 'normal-stock',
        ]);
    }
}
