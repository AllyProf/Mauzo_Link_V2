<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $order->order_number }}</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none !important; }
            @page { margin: 0.5cm; }
        }
        body {
            font-family: Arial, sans-serif;
            max-width: 300px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .order-info {
            margin-bottom: 15px;
        }
        .order-info p {
            margin: 5px 0;
            font-size: 12px;
        }
        .items {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
            margin: 15px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .item-name {
            flex: 1;
        }
        .item-qty {
            margin: 0 10px;
        }
        .item-price {
            text-align: right;
        }
        .total {
            margin-top: 15px;
            text-align: right;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 14px;
        }
        .total-amount {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #666;
        }
        .notes {
            margin-top: 10px;
            font-size: 11px;
            color: #666;
            font-style: italic;
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ORDER RECEIPT</h1>
        <p>{{ $order->user->business_name ?? 'Restaurant' }}</p>
        <p>{{ $order->created_at->format('M d, Y H:i') }}</p>
    </div>

    <div class="order-info">
        <p><strong>Order #:</strong> {{ $order->order_number }}</p>
        @if($order->table)
            <p><strong>Table:</strong> {{ $order->table->table_number }}</p>
        @endif
        @if($order->customer_name)
            <p><strong>Customer:</strong> {{ $order->customer_name }}</p>
        @endif
        @if($order->customer_phone)
            <p><strong>Phone:</strong> {{ $order->customer_phone }}</p>
        @endif
        <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        <p><strong>Payment:</strong> {{ ucfirst($order->payment_status) }}</p>
    </div>

    <div class="items">
        @foreach($order->items as $item)
            <div class="item">
                <div class="item-name">
                    {{ $item->productVariant->product->name ?? 'N/A' }}
                    @if($item->productVariant)
                        <br><small>{{ $item->productVariant->measurement ?? '' }}</small>
                    @endif
                </div>
                <div class="item-qty">{{ $item->quantity }}x</div>
                <div class="item-price">TSh {{ number_format($item->total_price, 2) }}</div>
            </div>
        @endforeach
    </div>

    <div class="total">
        <div class="total-line">
            <span>Subtotal:</span>
            <span>TSh {{ number_format($order->total_amount, 2) }}</span>
        </div>
        <div class="total-line total-amount">
            <span>TOTAL:</span>
            <span>TSh {{ number_format($order->total_amount, 2) }}</span>
        </div>
    </div>

    @if($order->notes)
        <div class="notes">
            <strong>Notes:</strong> {{ $order->notes }}
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your order!</p>
        <p>{{ $order->created_at->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="no-print">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <button class="btn" onclick="window.close()">Close</button>
    </div>

    <script>
        // Auto print when opened
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>







