<?php

namespace Tests\Services\Unit\Payments;

use App\Http\Services\InventoryAllocationService;
use App\Http\Services\StripePaymentService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Support\ServiceSmokeTestCase;

class StripePaymentServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return StripePaymentService::class;
    }

    public function test_create_checkout_session_returns_created_session(): void
    {
        $sessionManager = Mockery::mock();
        $session = (object) ['id' => 'cs_test_123'];
        $sessionManager->shouldReceive('create')
            ->once()
            ->with(['line_items' => [['price' => 'price_123']]])
            ->andReturn($session);

        $service = $this->makeService($sessionManager);

        $this->assertSame($session, $service->createCheckoutSession([
            'line_items' => [['price' => 'price_123']],
        ]));
    }

    public function test_retrieve_session_returns_requested_session(): void
    {
        $sessionManager = Mockery::mock();
        $session = (object) ['id' => 'cs_test_456'];
        $sessionManager->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_456')
            ->andReturn($session);

        $service = $this->makeService($sessionManager);

        $this->assertSame($session, $service->retrieveSession('cs_test_456'));
    }

    public function test_create_checkout_session_logs_and_rethrows_sdk_failures(): void
    {
        $exception = new \Exception('Stripe is unavailable');
        $sessionManager = Mockery::mock();
        $sessionManager->shouldReceive('create')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with(Mockery::on(fn (string $message): bool => str_contains($message, 'Stripe Create Session Exception')));

        $service = $this->makeService($sessionManager);

        $this->expectExceptionObject($exception);

        $service->createCheckoutSession(['line_items' => []]);
    }

    public function test_update_items_quantity_in_inventories_delegates_to_allocator(): void
    {
        $items = [['productVariantId' => 5, 'quantity' => 2]];

        $allocator = Mockery::mock(InventoryAllocationService::class);
        $allocator->shouldReceive('allocate')
            ->once()
            ->with($items);

        $service = $this->makeService(Mockery::mock(), $allocator);
        $service->updateItemsQuantityInInventories($items);
    }

    private function makeService($sessionManager, ?InventoryAllocationService $allocator = null): StripePaymentService
    {
        $allocator ??= Mockery::mock(InventoryAllocationService::class);
        $stripe = (object) [
            'checkout' => (object) [
                'sessions' => $sessionManager,
            ],
        ];

        return new class($allocator, $stripe) extends StripePaymentService
        {
            public function __construct(InventoryAllocationService $inventoryAllocationService, object $stripe)
            {
                $this->inventoryAllocationService = $inventoryAllocationService;
                $this->stripe = $stripe;
            }
        };
    }
}
