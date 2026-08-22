<?php

namespace Tests\Services\Unit\Payments;

use App\Http\Services\InventoryAllocationService;
use App\Http\Services\TapPaymentService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Support\ServiceSmokeTestCase;

class TapPaymentServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return TapPaymentService::class;
    }

    public function test_create_charge_posts_payload_to_tap_api(): void
    {
        Http::fake([
            'https://api.tap.company/v2/charges' => Http::response(['id' => 'chg_123'], 200),
        ]);

        $service = $this->makeService();
        $response = $service->createCharge(['amount' => 100]);

        $this->assertSame('chg_123', $response['id']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.tap.company/v2/charges'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer sk_test_XKokBfNWv6FIYuTMg5sLPjhJ')
                && $request['amount'] === 100;
        });
    }

    public function test_retrieve_charge_issues_get_request(): void
    {
        Http::fake([
            'https://api.tap.company/v2/charges/chg_456' => Http::response(['id' => 'chg_456'], 200),
        ]);

        $service = $this->makeService();
        $response = $service->retrieveCharge('chg_456');

        $this->assertSame('chg_456', $response['id']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.tap.company/v2/charges/chg_456'
                && $request->method() === 'GET';
        });
    }

    public function test_refund_charge_includes_amount_when_provided(): void
    {
        Http::fake([
            'https://api.tap.company/v2/refunds' => Http::response(['id' => 'rfnd_123'], 200),
        ]);

        $service = $this->makeService();
        $response = $service->refundCharge('chg_789', 55.5);

        $this->assertSame('rfnd_123', $response['id']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.tap.company/v2/refunds'
                && $request->method() === 'POST'
                && $request['charge'] === 'chg_789'
                && $request['amount'] === 55.5;
        });
    }

    public function test_update_items_quantity_in_inventories_delegates_to_allocator(): void
    {
        $items = [['productVariantId' => 9, 'quantity' => 4]];
        $allocator = Mockery::mock(InventoryAllocationService::class);
        $allocator->shouldReceive('allocate')
            ->once()
            ->with($items);

        $service = new TapPaymentService($allocator);
        $service->updateItemsQuantityInInventories($items);
    }

    private function makeService(): TapPaymentService
    {
        return new TapPaymentService(Mockery::mock(InventoryAllocationService::class));
    }
}
