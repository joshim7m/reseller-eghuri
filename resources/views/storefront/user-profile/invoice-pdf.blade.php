<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 40px; }
        .header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #4648D4; }
        .header h1 { font-size: 24px; color: #4648D4; margin: 0; }
        .header p { margin: 2px 0; color: #666; font-size: 11px; }
        .info { margin-bottom: 30px; }
        .info table { width: 100%; }
        .info td { vertical-align: top; width: 50%; }
        .info h3 { font-size: 13px; margin: 0 0 6px; text-transform: uppercase; color: #4648D4; }
        .info p { margin: 2px 0; font-size: 11px; color: #555; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; color: #666; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        table.items td:last-child, table.items th:last-child { text-align: right; }
        .total { text-align: right; margin-top: 10px; padding-top: 10px; border-top: 2px solid #4648D4; }
        .total .row { display: flex; justify-content: flex-end; gap: 40px; margin: 4px 0; font-size: 11px; }
        .total .grand { font-size: 15px; font-weight: bold; color: #4648D4; }
        .footer { margin-top: 50px; padding-top: 15px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>EHGURI</h1>
            <p>Online Clothing Marketplace</p>
            <p>Dhaka, Bangladesh</p>
        </div>
        <div style="text-align: right;">
            <h1 style="font-size: 18px; margin: 0;">INVOICE</h1>
            <p><strong>{{ $order->invoice->invoice_number }}</strong></p>
            <p>Date: {{ $order->invoice->invoice_date->format('M d, Y') }}</p>
        </div>
    </div>

    <div class="info">
        <table>
            <tr>
                <td>
                    <h3>Bill To</h3>
                    <p><strong>{{ $order->user->name }}</strong></p>
                    <p>{{ $order->shipping_address }}</p>
                </td>
                <td>
                    <h3>Order</h3>
                    <p>Order #: {{ $order->order_number }}</p>
                    <p>Payment: {{ ucfirst($order->payment_method) }}</p>
                    <p>Status: {{ ucfirst($order->status) }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->item_name }}</td>
                    <td>৳{{ number_format($item->price_at_purchase, 0) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>৳{{ number_format($item->total, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        @php
            $subtotal = $order->items->sum(fn ($i) => $i->total);
            $deliveryCharge = $order->delivery_charge ?? 0;
            $inv = $order->invoice;
        @endphp
        <div class="row"><span>Subtotal</span><span>৳{{ number_format($subtotal, 0) }}</span></div>
        @if ($deliveryCharge)
            <div class="row"><span>Delivery Charge</span><span>৳{{ number_format($deliveryCharge, 0) }}</span></div>
        @endif
        <div class="row" style="padding-top:6px;border-top:1px solid #e5e7eb"><span>Total</span><span>৳{{ number_format($order->total_amount, 0) }}</span></div>
        <div class="row"><span>Paid</span><span>৳{{ number_format($inv->paid_amount, 0) }}</span></div>
        <div class="row grand"><span>Due</span><span>৳{{ number_format($inv->due_amount, 0) }}</span></div>
    </div>

    <div style="text-align:center;margin-top:20px;padding:10px;border-radius:6px;font-size:12px;font-weight:bold;{{ $inv->payment_status === 'paid' ? 'background:#dcfce7;color:#166534;' : ($inv->payment_status === 'partial' ? 'background:#fef9c3;color:#854d0e;' : 'background:#fee2e2;color:#991b1b;') }}">
        Payment Status: {{ ucfirst($inv->payment_status) }}
    </div>

    <div class="footer">
        <p>Thank you for shopping with EHGURI!</p>
        <p>{{ $order->invoice->invoice_number }} | Generated on {{ now()->format('M d, Y h:i A') }}</p>
    </div>
</body>
</html>
