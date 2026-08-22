<?php

namespace Tests\Services\Unit\Inventory;

use App\Http\Services\InventoryAllocationService;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\ServiceSmokeTestCase;

class InventoryAllocationServiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return InventoryAllocationService::class;
    }

    public function test_allocate_ignores_items_without_variant_or_positive_quantity(): void
    {
        $variant = $this->createVariant();
        $record = $this->createInventoryRecord($variant, 5);

        $service = $this->instantiateService($this->serviceClass());
        $service->allocate([
            ['quantity' => 3],
            ['productVariantId' => $variant->id, 'quantity' => 0],
            ['productVariantId' => $variant->id, 'quantity' => -2],
        ]);

        $this->assertSame(5, $record->fresh()->quantity);
        $this->assertNull($record->fresh()->last_sold_at);
    }

    public function test_allocate_consumes_lowest_quantity_records_first(): void
    {
        $variant = $this->createVariant();
        $smallestRecord = $this->createInventoryRecord($variant, 2);
        $largestRecord = $this->createInventoryRecord($variant, 5);

        $service = $this->instantiateService($this->serviceClass());
        $service->allocate([
            ['productVariantId' => $variant->id, 'quantity' => 3],
        ]);

        $this->assertSame(0, $smallestRecord->fresh()->quantity);
        $this->assertNotNull($smallestRecord->fresh()->last_sold_at);
        $this->assertSame(4, $largestRecord->fresh()->quantity);
        $this->assertNotNull($largestRecord->fresh()->last_sold_at);
    }

    public function test_allocate_throws_when_available_stock_is_insufficient(): void
    {
        $variant = $this->createVariant();
        $record = $this->createInventoryRecord($variant, 2);

        $service = $this->instantiateService($this->serviceClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Insufficient stock for variant {$variant->id}.");

        try {
            $service->allocate([
                ['productVariantId' => $variant->id, 'quantity' => 3],
            ]);
        } finally {
            $this->assertSame(0, $record->fresh()->quantity);
            $this->assertNotNull($record->fresh()->last_sold_at);
        }
    }

    private function createVariant(): ProductVariant
    {
        $product = Product::factory()->create(['status' => 'approved']);

        return ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);
    }

    private function createInventoryRecord(ProductVariant $variant, int $quantity): InventoryProduct
    {
        $inventory = Inventory::create([
            'name' => 'Primary Warehouse',
            'maximum_quantity' => 200,
            'location' => 'Cairo',
            'status' => 'normal-stock',
        ]);

        return InventoryProduct::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'quantity' => $quantity,
        ]);
    }
}
