<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docket - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto; /* Standard Thermal Paper Width */
        }
        @media print {
            body { 
                width: 72mm; /* Printable area */
                margin: 0 auto;
                padding: 5mm;
            }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Courier New', Courier, monospace; /* Classic Receipt Font */
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            width: 72mm;
            margin: 0 auto;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .header {
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .business-info {
            font-size: 11px;
            margin-top: 2px;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        
        .order-meta {
            margin: 8px 0;
            font-size: 11px;
        }
        .order-meta div {
            display: flex;
            justify-content: space-between;
        }

        .items-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 10px;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .item-details {
            flex: 1;
            padding-right: 5px;
        }
        .item-qty {
            width: 30px;
            text-align: center;
        }
        .item-total {
            width: 70px;
            text-align: right;
        }
        
        .totals {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px solid #000;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            margin-top: 5px;
            padding-top: 3px;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .qr-placeholder {
            margin: 15px 0;
            display: inline-block;
            border: 1px solid #000;
            padding: 5px;
            font-size: 8px;
        }
        
        .btn-container {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
        }
        .btn {
            padding: 8px 15px;
            background: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 3px;
            font-family: sans-serif;
            font-size: 11px;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <div class="header text-center">
        <h1>{{ $order->user->business_name ?? 'RECEIPT' }}</h1>
        <div class="business-info">
            {{ $order->user->email ?? '' }}<br>
            {{ $order->user->phone ?? '' }}
        </div>
    </div>

    <div class="order-meta">
        <div><span>NO:</span> <span class="font-bold">{{ $order->order_number }}</span></div>
        <div><span>DATE:</span> <span>{{ $order->created_at->format('d/m/Y H:i') }}</span></div>
        @if($order->table)
            <div><span>TABLE:</span> <span class="font-bold">#{{ $order->table->table_number }}</span></div>
        @endif
        <div><span>STAFF:</span> <span>{{ $order->waiter->full_name ?? 'COUNTER' }}</span></div>
    </div>

    <div class="divider"></div>

    <div class="items-header">
        <span>Description</span>
        <div style="display:flex;">
            <span style="width:30px;text-align:center;">Qty</span>
            <span style="width:70px;text-align:right;">Sub</span>
        </div>
    </div>

    <div class="items-list">
        @foreach($order->items as $item)
            <div class="item">
                <div class="item-details">
                    <div class="font-bold">{{ $displayName = $item->productVariant->display_name ?? ($item->productVariant->product->name ?? 'ITEM') }}</div>
                    @if($item->sell_type === 'tot')
                      <div style="font-size: 9px; color: #333;">({{ $item->productVariant->portion_unit_name }})</div>
                    @elseif($item->productVariant->name && stripos($displayName, $item->productVariant->name) === false)
                      <div style="font-size: 9px; color: #333;">{{ $item->productVariant->name }}</div>
                    @endif
                </div>
                <div style="display:flex;">
                    <div class="item-qty">{{ (int)$item->quantity }}</div>
                    <div class="item-total">{{ number_format($item->total_price) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>{{ number_format($order->total_amount) }}</span>
        </div>
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span>TSh {{ number_format($order->total_amount) }}</span>
        </div>
        
        <div class="total-row" style="margin-top: 5px; font-size: 11px;">
            <span>PAID VIA:</span>
            <span class="font-bold">{{ strtoupper(str_replace('_', ' ', $order->payment_method ?? 'PENDING')) }}</span>
        </div>
        @if($order->transaction_reference)
        <div class="total-row" style="font-size: 10px;">
            <span>REF:</span>
            <span>{{ $order->transaction_reference }}</span>
        </div>
        @endif
    </div>

    @if($order->notes)
        <div class="divider"></div>
        <div style="font-size: 9px; font-style: italic;">
            NOTE: {{ $order->notes }}
        </div>
    @endif

    <div class="footer text-center">
        <div class="divider"></div>
        <p>*** THANK YOU - WELCOME AGAIN ***</p>
        <div class="qr-placeholder">
            [ QR CODE READY ]<br>
            VERIFIED BY MLINK
        </div>
        <p style="font-size: 8px;">{{ $order->created_at->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="btn-container no-print">
        <button class="btn" onclick="window.print()">PRINT DOCKET</button>
        <button class="btn" onclick="window.close()" style="background:#cc0000;">CLOSE</button>
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => { window.print(); }, 500);
        };
    </script>
</body>
</html>
