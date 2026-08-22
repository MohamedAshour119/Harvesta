<?php

namespace Tests\Repositories\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Models\Order;
use App\Models\User;
use Tests\Support\RepositoryTestCase;

class ProfileRepositoryCustomerSummaryTest extends RepositoryTestCase
{
    private ProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ProfileRepository::class);
    }

    public function test_get_customer_summary_counts_all_orders_and_only_sums_delivered_totals(): void
    {
        $user = User::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
            'total' => 150,
            'subtotal' => 150,
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100,
            'subtotal' => 100,
        ]);

        $summary = $this->repository->getCustomerSummary($user->id);

        $this->assertSame(2, $summary['total_orders']);
        $this->assertSame(150.0, $summary['total_spent']);
    }
}
