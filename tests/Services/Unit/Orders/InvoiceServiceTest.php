<?php

namespace Tests\Services\Unit\Orders;

use App\Http\Services\InvoiceService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\ServiceSmokeTestCase;

class InvoiceServiceTest extends ServiceSmokeTestCase
{
    use RefreshDatabase;

    protected function serviceClass(): string
    {
        return InvoiceService::class;
    }

    public function test_generate_pdf_assigns_invoice_uuid_and_passes_expected_view_data(): void
    {
        $product = Product::factory()->create([
            'name' => ['en' => 'Industrial Cleaner', 'ar' => 'منظف صناعي'],
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);
        $order = Order::factory()->create([
            'first_name' => 'Mona',
            'last_name' => 'Ali',
            'email' => 'mona@example.com',
            'subtotal' => 180.0,
            'discount_amount' => 20.0,
            'total' => 160.0,
            'source' => 'customer_checkout',
        ]);
        $order->forceFill(['invoice_uuid' => null])->saveQuietly();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 80,
            'total' => 160,
        ]);

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')
            ->once()
            ->with('a4')
            ->andReturnSelf();

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('invoices.order', Mockery::on(function (array $data) use ($order): bool {
                return $data['order']->is($order)
                    && $data['order_number'] === 'ORD-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)
                    && $data['customer_name'] === 'Mona Ali'
                    && $data['customer_email'] === 'mona@example.com'
                    && $data['subtotal'] === 180.0
                    && $data['discount'] === 20.0
                    && $data['total'] === 160.0
                    && !empty($data['invoice_uuid']);
            }))
            ->andReturn($pdf);

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->generatePdf($order->fresh());

        $this->assertSame($pdf, $result);
        $this->assertNotEmpty($order->fresh()->invoice_uuid);
    }

    public function test_get_file_name_formats_order_number_with_zero_padding(): void
    {
        $order = new Order(['id' => 42]);
        $order->id = 42;

        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame('invoice-ORD-000042.pdf', $service->getFileName($order));
    }
}
