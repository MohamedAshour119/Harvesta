<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .brand { font-size: 24px; font-weight: bold; color: #B12C2E; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #333; text-align: right; }
        .invoice-meta { text-align: right; margin-top: 5px; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; color: #B12C2E; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #B12C2E; padding-bottom: 5px; margin-bottom: 10px; }
        .info-row { display: flex; margin-bottom: 3px; }
        .info-label { width: 120px; font-weight: bold; color: #666; }
        .info-value { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f8f8f8; text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; border-bottom: 2px solid #ddd; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; float: right; width: 280px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .totals-row.total { font-size: 16px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; margin-top: 5px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .footer { margin-top: 60px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #eee; padding-top: 15px; }
        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>
<div class="container">
    <table style="border: none; margin-bottom: 30px;">
        <tr>
            <td style="border: none; padding: 0; vertical-align: top; width: 50%;">
                <div class="brand">CAIRO 3A</div>
                <div style="color: #666; margin-top: 5px;">B2B Industrial Commerce</div>
            </td>
            <td style="border: none; padding: 0; vertical-align: top; width: 50%; text-align: right;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">{{ $order_number }}</div>
                <div class="invoice-meta">{{ $order_date }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Bill To</div>
        <div><strong>{{ $customer_name }}</strong></div>
        <div>{{ $customer_email }}</div>
        @if($order->company_name)
            <div>{{ $order->company_name }}</div>
        @endif
        @if($order->address)
            <div>{{ $order->address }}@if($order->city), {{ $order->city }}@endif</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Order Details</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->product?->getTranslation('name', 'en') ?? 'Product #'.$item->product_id }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }} EGP</td>
                    <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2) }} EGP</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="clearfix">
        <div class="totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>{{ number_format($subtotal, 2) }} EGP</span>
            </div>
            @if($discount > 0)
            <div class="totals-row">
                <span>Discount</span>
                <span>-{{ number_format($discount, 2) }} EGP</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>Total</span>
                <span>{{ number_format($total, 2) }} EGP</span>
            </div>
        </div>
    </div>

    <div style="clear: both;"></div>

    <table style="border: none; margin-top: 30px;">
        <tr>
            <td style="border: none; padding: 0;">
                <strong>Payment Status:</strong>
                <span class="badge {{ $payment_status === 'completed' ? 'badge-paid' : 'badge-pending' }}">
                    {{ ucfirst($payment_status) }}
                </span>
            </td>
            <td style="border: none; padding: 0; text-align: right;">
                <strong>Source:</strong>
                {{ $source === 'agent_manual_sale' ? 'Agent Manual Sale' : 'Customer Checkout' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Invoice ID: {{ $invoice_uuid }}</p>
        <p>Thank you for your business!</p>
    </div>
</div>
</body>
</html>
