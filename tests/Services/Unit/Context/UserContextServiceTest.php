<?php

namespace Tests\Services\Unit\Context;

use App\Http\Services\UserContextService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserContextServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserContextService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = new UserContextService();
    }

    public function test_build_user_context_returns_an_empty_array_for_unknown_users(): void
    {
        $this->assertSame([], $this->service->buildUserContext(999999));
    }

    public function test_build_user_context_summarizes_non_cancelled_orders_and_products(): void
    {
        $user = User::factory()->create(['full_name' => 'Context Customer']);

        $tea = Product::query()->create([
            'name' => ['en' => 'Premium Tea', 'ar' => 'Premium Tea'],
            'description' => ['en' => 'Tea product', 'ar' => 'Tea product'],
            'status' => 'approved',
        ]);

        $oil = Product::query()->create([
            'name' => ['en' => 'Corn Oil', 'ar' => 'Corn Oil'],
            'description' => ['en' => 'Cooking oil', 'ar' => 'Cooking oil'],
            'status' => 'approved',
        ]);

        $ignored = Product::query()->create([
            'name' => ['en' => 'Ignored Product', 'ar' => 'Ignored Product'],
            'description' => ['en' => 'Cancelled order product', 'ar' => 'Cancelled order product'],
            'status' => 'approved',
        ]);

        $olderOrder = $this->createOrder($user, [
            'status' => 'approved',
            'total' => 120,
            'created_at' => now()->subDays(3),
        ]);

        $latestOrder = $this->createOrder($user, [
            'status' => 'shipped',
            'total' => 180,
            'created_at' => now()->subDay(),
        ]);

        $cancelledOrder = $this->createOrder($user, [
            'status' => 'cancelled',
            'total' => 999,
            'created_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $olderOrder->id,
            'product_id' => $tea->id,
            'quantity' => 1,
            'unit_price' => 120,
            'total' => 120,
        ]);

        OrderItem::query()->create([
            'order_id' => $latestOrder->id,
            'product_id' => $tea->id,
            'quantity' => 1,
            'unit_price' => 90,
            'total' => 90,
        ]);

        OrderItem::query()->create([
            'order_id' => $latestOrder->id,
            'product_id' => $oil->id,
            'quantity' => 1,
            'unit_price' => 90,
            'total' => 90,
        ]);

        OrderItem::query()->create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $ignored->id,
            'quantity' => 1,
            'unit_price' => 999,
            'total' => 999,
        ]);

        $context = $this->service->buildUserContext($user->id);

        $this->assertSame('Context Customer', $context['name']);
        $this->assertSame(2, $context['total_orders']);
        $this->assertSame($latestOrder->created_at->format('Y-m-d'), $context['last_order_date']);
        $this->assertSame(300.0, $context['total_spent']);
        $this->assertSame(['Premium Tea', 'Corn Oil'], $context['frequently_ordered_products']);
        $this->assertSame(['Premium Tea', 'Corn Oil'], $context['last_ordered_products']);
    }

    private function createOrder(User $user, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'user_id' => $user->id,
            'first_name' => 'Context',
            'last_name' => 'Customer',
            'email' => 'context@example.com',
            'phone_number' => '01000000000',
            'company_name' => 'Context Co',
            'address' => '123 Test St',
            'city' => 'Cairo',
            'province' => 'Cairo',
            'postal_code' => '12345',
            'payment_status' => 'completed',
            'status' => 'pending',
            'discount_amount' => 0,
            'subtotal' => 100,
            'total' => 100,
            'agent_code' => 'SYSTEM',
            'source' => 'customer_checkout',
            'manual_sale_status' => null,
        ], $overrides));
    }
}
