<?php

namespace Tests\Repositories\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Tests\Support\RepositoryTestCase;
use Tests\Support\Repositories\Profile\BuildsProfileRepositoryOrders;

class ProfileRepositoryOrderLookupTest extends RepositoryTestCase
{
    use BuildsProfileRepositoryOrders;

    private ProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProfileRepository::class);
    }

    public function test_find_order_for_user_and_agent_return_matching_order_with_items_product_loaded(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'approved']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $order = $this->createOrderWithItem($user, $product, $variant, [
            'agent_code' => 'AGENT-FIND-REPO',
            'status' => 'delivered',
        ]);

        $byUser = $this->repository->findOrderForUser($order->id, $user->id);
        $byAgent = $this->repository->findOrderForAgent($order->id, 'AGENT-FIND-REPO');

        $this->assertSame($order->id, $byUser?->id);
        $this->assertSame($order->id, $byAgent?->id);
        $this->assertTrue($byUser?->relationLoaded('items'));
        $this->assertTrue($byUser?->items->first()->relationLoaded('product'));
    }
}
