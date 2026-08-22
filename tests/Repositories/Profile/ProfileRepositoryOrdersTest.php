<?php

namespace Tests\Repositories\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Tests\Support\RepositoryTestCase;
use Tests\Support\Repositories\Profile\BuildsProfileRepositoryOrders;

class ProfileRepositoryOrdersTest extends RepositoryTestCase
{
    use BuildsProfileRepositoryOrders;

    private ProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProfileRepository::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_get_recent_orders_and_paginated_orders_format_sorted_order_data(): void
    {
        Carbon::setTestNow('2026-04-20 12:00:00');

        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'approved', 'slug' => 'premium-box']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'variant_value' => 'Default']);

        $older = $this->createOrderWithItem($user, $product, $variant, [
            'status' => 'pending',
            'total' => 80,
            'subtotal' => 100,
            'discount_amount' => 20,
            'source' => 'agent_manual_sale',
            'sold_at' => now()->subDays(3),
            'invoice_uuid' => null,
        ]);
        $newer = $this->createOrderWithItem($user, $product, $variant, [
            'status' => 'delivered',
            'total' => 120,
            'subtotal' => 120,
            'source' => 'customer_checkout',
            'sold_at' => now()->subDay(),
            'invoice_uuid' => 'invoice-123',
        ]);

        $recent = $this->repository->getRecentOrders($user->id, 2);
        $paginated = $this->repository->getPaginatedOrders($user->id, 2);

        $this->assertSame([$newer->id, $older->id], $recent->pluck('id')->all());
        $this->assertTrue($recent[0]['has_invoice']);
        $this->assertSame('ORD-' . str_pad((string) $newer->id, 6, '0', STR_PAD_LEFT), $recent[0]['order_number']);
        $this->assertSame(2, $paginated->total());
        $this->assertSame(20.0, $paginated->items()[1]['discount_amount']);
    }
}
