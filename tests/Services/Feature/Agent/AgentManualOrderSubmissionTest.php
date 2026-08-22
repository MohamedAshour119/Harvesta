<?php

namespace Tests\Services\Feature\Agent;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentManualOrderSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_submit_and_list_manual_orders(): void
    {
        Storage::fake('public');

        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-001',
            'commission' => '10',
        ]);
        $customer = User::factory()->create([
            'is_agent' => false,
        ]);
        $product = Product::factory()->create([
            'status' => 'approved',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_after_discount' => 25,
            'unit_price_usd' => 25,
            'agent_price' => 18,
        ]);
        $inventory = Inventory::create([
            'name' => 'Manual Order Warehouse',
            'maximum_quantity' => 100,
            'location' => 'Riyadh',
            'status' => 'normal-stock',
        ]);
        InventoryProduct::create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'inventory_id' => $inventory->id,
            'quantity' => 5,
        ]);

        Sanctum::actingAs($agent);

        $submitResponse = $this->withHeader('Accept', 'application/json')
            ->post('/api/agents/manual-orders', [
                'user_id' => $customer->id,
                'first_name' => 'Customer',
                'last_name' => 'One',
                'email' => 'customer@example.com',
                'phone_number' => '123456789',
                'address' => 'Street 1',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'postal_code' => '12345',
                'sold_at' => now()->toDateTimeString(),
                'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
                'products' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $submitResponse->assertStatus(201);
        $order = Order::query()->with('items')->latest('id')->firstOrFail();

        $this->assertDatabaseHas('orders', [
            'agent_code' => 'AGENT-001',
            'source' => 'agent_manual_sale',
            'manual_sale_status' => 'approved',
            'status' => 'delivered',
            'payment_status' => 'completed',
        ]);
        $this->assertSame(36.0, (float) $order->subtotal);
        $this->assertSame(36.0, (float) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame(18.0, (float) $order->items->first()->unit_price);
        $this->assertSame(36.0, (float) $order->items->first()->total);

        $listResponse = $this->get('/api/agents/manual-orders');
        $listResponse->assertOk()
            ->assertJsonPath('data.data.0.source', 'agent_manual_sale')
            ->assertJsonPath('data.data.0.manual_sale_status', 'approved')
            ->assertJsonPath('data.data.0.status', 'delivered');
    }

    public function test_agent_manual_orders_list_returns_latest_three_without_pagination(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-003',
            'commission' => '10',
        ]);
        $product = Product::factory()->create([
            'status' => 'approved',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_after_discount' => 10,
            'unit_price_usd' => 10,
        ]);

        Sanctum::actingAs($agent);

        foreach (range(1, 4) as $index) {
            $order = \App\Models\Order::factory()->create([
                'agent_code' => 'AGENT-003',
                'source' => 'agent_manual_sale',
                'manual_sale_status' => 'submitted',
                'created_at' => now()->subMinutes(5 - $index),
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'supplier_id' => null,
                'quantity' => 1,
                'unit_price' => 10,
                'cost_plus_logistics' => 0,
                'total' => 10,
            ]);
        }

        $response = $this->get('/api/agents/manual-orders?per_page=99&page=2');

        $response->assertOk()
            ->assertJsonCount(3, 'data.data')
            ->assertJsonMissingPath('data.current_page')
            ->assertJsonMissingPath('data.last_page')
            ->assertJsonMissingPath('data.per_page')
            ->assertJsonMissingPath('data.total')
            ->assertJsonMissingPath('data.next_page_url');
    }

    public function test_proof_photo_is_required(): void
    {
        $agent = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-002',
            'commission' => '10',
        ]);
        $product = Product::factory()->create([
            'status' => 'approved',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_after_discount' => 25,
            'unit_price_usd' => 25,
            'agent_price' => 25,
        ]);

        Sanctum::actingAs($agent);

        $response = $this->withHeader('Accept', 'application/json')
            ->post('/api/agents/manual-orders', [
                'first_name' => 'Guest',
                'last_name' => 'Customer',
                'email' => 'guest@example.com',
                'phone_number' => '123456789',
                'address' => 'Street 1',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'postal_code' => '12345',
                'sold_at' => now()->toDateTimeString(),
                'products' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proof_photo']);
        $this->assertDatabaseCount('orders', 0);
    }
}
