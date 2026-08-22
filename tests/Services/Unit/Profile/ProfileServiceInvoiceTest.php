<?php

namespace Tests\Services\Unit\Profile;

use App\Http\Repositories\ProfileRepository;
use App\Http\Services\InvoiceService;
use App\Http\Services\ProfileService;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\ServiceSmokeTestCase;

class ProfileServiceInvoiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return ProfileService::class;
    }

    public function test_download_invoice_falls_back_to_agent_order_lookup_when_needed(): void
    {
        $user = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-301',
        ]);
        $order = Order::factory()->create([
            'agent_code' => 'AGENT-301',
        ]);
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $this->actingAs($user);

        $repository = $this->mockDependency(ProfileRepository::class);
        $repository->shouldReceive('findOrderForUser')
            ->once()
            ->with($order->id, $user->id)
            ->andReturnNull();
        $repository->shouldReceive('findOrderForAgent')
            ->once()
            ->with($order->id, 'AGENT-301')
            ->andReturn($order);

        $invoiceService = $this->mockDependency(InvoiceService::class);
        $invoiceService->shouldReceive('generatePdf')
            ->once()
            ->with($order)
            ->andReturn($pdf);
        $invoiceService->shouldReceive('getFileName')
            ->once()
            ->with($order)
            ->andReturn('invoice-AGENT-301.pdf');

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->downloadInvoice($order->id);

        $this->assertSame($pdf, $result['pdf']);
        $this->assertSame('invoice-AGENT-301.pdf', $result['filename']);
    }

    public function test_download_invoice_returns_null_when_no_matching_order_exists(): void
    {
        $user = User::factory()->create([
            'is_agent' => true,
            'code' => 'AGENT-401',
        ]);
        $this->actingAs($user);

        $repository = $this->mockDependency(ProfileRepository::class);
        $repository->shouldReceive('findOrderForUser')
            ->once()
            ->with(999, $user->id)
            ->andReturnNull();
        $repository->shouldReceive('findOrderForAgent')
            ->once()
            ->with(999, 'AGENT-401')
            ->andReturnNull();

        $service = $this->instantiateService($this->serviceClass());

        $this->assertNull($service->downloadInvoice(999));
    }
}
