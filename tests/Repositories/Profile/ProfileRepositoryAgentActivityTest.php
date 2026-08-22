<?php

namespace Tests\Repositories\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Tests\Support\RepositoryTestCase;
use Tests\Support\Repositories\Profile\BuildsProfileRepositoryOrders;

class ProfileRepositoryAgentActivityTest extends RepositoryTestCase
{
    use BuildsProfileRepositoryOrders;

    private ProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProfileRepository::class);
    }

    public function test_get_agent_activity_formats_paginated_orders_and_fallback_customer_name(): void
    {
        $agentCode = 'AGENT-ACTIVITY-1';
        $product = Product::factory()->create(['status' => 'approved', 'slug' => 'warehouse-box']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $user = User::factory()->create(['full_name' => 'Named Buyer']);

        $withUser = $this->createOrderWithItem($user, $product, $variant, [
            'agent_code' => $agentCode,
            'status' => 'delivered',
            'total' => 80,
            'subtotal' => 80,
            'source' => 'customer_checkout',
            'invoice_uuid' => 'invoice-444',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
            'sold_at' => now()->subHour(),
        ]);
        $withoutUser = $this->createOrderWithItem(null, $product, $variant, [
            'agent_code' => $agentCode,
            'status' => 'pending',
            'total' => 40,
            'subtotal' => 40,
            'first_name' => 'Guest',
            'last_name' => 'Buyer',
            'email' => 'guest@example.com',
            'source' => 'agent_manual_sale',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
            'sold_at' => now()->subHours(2),
        ]);

        $activity = $this->repository->getAgentActivity($agentCode, 10);

        $this->assertSame([$withUser->id, $withoutUser->id], collect($activity->items())->pluck('id')->all());
        $this->assertSame('Named Buyer', $activity->items()[0]['customer']);
        $this->assertSame('Guest Buyer', $activity->items()[1]['customer']);
    }
}
