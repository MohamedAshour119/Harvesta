<?php

namespace App\Http\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class InvoiceService
{
    public function generatePdf(Order $order): \Barryvdh\DomPDF\PDF
    {
        if (empty($order->invoice_uuid)) {
            $order->update(['invoice_uuid' => (string) Str::uuid()]);
        }

        $order->loadMissing('items.product');

        $data = [
            'order' => $order,
            'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'customer_name' => trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: ($order->user?->full_name ?? 'N/A'),
            'customer_email' => $order->email ?? $order->user?->email ?? 'N/A',
            'order_date' => ($order->sold_at ?? $order->created_at)->format('d M Y'),
            'items' => $order->items,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
            'payment_status' => $order->payment_status ?? 'N/A',
            'source' => $order->source,
            'invoice_uuid' => $order->invoice_uuid,
        ];

        return Pdf::loadView('invoices.order', $data)
            ->setPaper('a4');
    }

    public function getFileName(Order $order): string
    {
        return 'invoice-ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT) . '.pdf';
    }
}
