<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display Screen (KDS)</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background: #1a1a1a;
            color: #fff;
            font-family: 'Arial', sans-serif;
            padding: 20px;
            overflow-x: hidden;
        }
        .kds-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .kds-header h1 {
            font-size: 3rem;
            font-weight: bold;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .kds-header .time {
            font-size: 1.5rem;
            margin-top: 10px;
        }
        .status-section {
            margin-bottom: 40px;
        }
        .status-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .status-title.pending {
            background: #dc3545;
            color: white;
        }
        .status-title.preparing {
            background: #ffc107;
            color: #000;
        }
        .status-title.ready {
            background: #28a745;
            color: white;
        }
        .order-card {
            background: #2d2d2d;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 6px solid;
        }
        .order-card.pending {
            border-left-color: #dc3545;
        }
        .order-card.preparing {
            border-left-color: #ffc107;
        }
        .order-card.ready {
            border-left-color: #28a745;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.5);
        }
        .order-number {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 15px;
        }
        .order-info {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        .order-info i {
            margin-right: 10px;
            width: 25px;
        }
        .item-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
            margin: 15px 0;
        }
        .item-variant {
            font-size: 1.2rem;
            color: #aaa;
            margin-bottom: 10px;
        }
        .special-instructions {
            background: #ffc107;
            color: #000;
            padding: 10px;
            border-radius: 5px;
            font-size: 1.1rem;
            margin: 10px 0;
            font-weight: bold;
        }
        .order-notes {
            background: #17a2b8;
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 1.1rem;
            margin: 10px 0;
        }
        .action-btn {
            font-size: 1.5rem;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 15px;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .action-btn.start {
            background: #28a745;
            color: white;
        }
        .action-btn.ready {
            background: #007bff;
            color: white;
        }
        .action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .time-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 1.1rem;
            display: inline-block;
            margin-top: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            font-size: 1.8rem;
            color: #888;
        }
        .stats-bar {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .stat-box {
            background: #2d2d2d;
            padding: 20px 40px;
            border-radius: 10px;
            text-align: center;
            min-width: 200px;
            margin: 10px;
        }
        .stat-box .number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-box .label {
            font-size: 1.2rem;
            color: #aaa;
        }
        .stat-box.pending .number { color: #dc3545; }
        .stat-box.preparing .number { color: #ffc107; }
        .stat-box.ready .number { color: #28a745; }
        @media (max-width: 768px) {
            .kds-header h1 { font-size: 2rem; }
            .status-title { font-size: 1.8rem; }
            .order-number { font-size: 1.5rem; }
            .item-name { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="kds-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fa fa-tv"></i> Kitchen Display Screen <small style="font-size: 1.5rem; opacity: 0.9;">(Read Only)</small></h1>
                    <div class="time" id="current-time"></div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-light btn-lg" onclick="location.reload()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Bar -->
        <div class="stats-bar">
            <div class="stat-box pending">
                <div class="number">{{ $pendingOrders->count() }}</div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-box preparing">
                <div class="number">{{ $preparingOrders->count() }}</div>
                <div class="label">Preparing</div>
            </div>
            <div class="stat-box ready">
                <div class="number">{{ $readyOrders->count() }}</div>
                <div class="label">Ready</div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="status-section">
            <div class="status-title pending">
                <i class="fa fa-clock-o"></i> PENDING ORDERS ({{ $pendingOrders->count() }})
            </div>
            <div class="row" id="pending-orders">
                @forelse($pendingOrders as $orderData)
                    @foreach($orderData['kitchen_items'] as $item)
                        <div class="col-lg-4 col-md-6" data-order-id="{{ $orderData['order']->id }}" data-item-id="{{ $item->id }}">
                            <div class="order-card pending">
                                <div class="order-number">
                                    Order #{{ $orderData['order']->order_number }}
                                    @if($orderData['order']->table)
                                        <span class="badge badge-light" style="font-size: 1rem; margin-left: 10px;">
                                            Table: {{ $orderData['order']->table->table_number }}
                                        </span>
                                    @endif
                                </div>
                                <div class="order-info">
                                    <i class="fa fa-user"></i> <strong>Waiter:</strong> {{ $orderData['order']->waiter->full_name ?? 'N/A' }}
                                </div>
                                @if($orderData['order']->customer_name)
                                    <div class="order-info">
                                        <i class="fa fa-user-circle"></i> <strong>Customer:</strong> {{ $orderData['order']->customer_name }}
                                    </div>
                                @endif
                                <div class="order-info">
                                    <i class="fa fa-clock-o"></i> {{ $orderData['order']->created_at->format('H:i') }}
                                </div>
                                <hr style="border-color: #555; margin: 15px 0;">
                                <div class="item-name">
                                    {{ $item->quantity }}x {{ $item->food_item_name }}
                                </div>
                                @if($item->variant_name)
                                    <div class="item-variant">{{ $item->variant_name }}</div>
                                @endif
                                @if($item->special_instructions)
                                    <div class="special-instructions">
                                        <i class="fa fa-exclamation-triangle"></i> {{ $item->special_instructions }}
                                    </div>
                                @endif
                                @if($orderData['order']->notes && strpos($orderData['order']->notes, 'ORDER NOTES:') !== false)
                                    <div class="order-notes">
                                        <strong>Order Notes:</strong> {{ str_replace('ORDER NOTES: ', '', explode(' | ', $orderData['order']->notes)[1] ?? '') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa fa-check-circle" style="font-size: 4rem; color: #28a745;"></i>
                            <p>No pending orders</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Preparing Orders -->
        <div class="status-section">
            <div class="status-title preparing">
                <i class="fa fa-fire"></i> PREPARING ({{ $preparingOrders->count() }})
            </div>
            <div class="row" id="preparing-orders">
                @forelse($preparingOrders as $orderData)
                    @foreach($orderData['kitchen_items'] as $item)
                        <div class="col-lg-4 col-md-6" data-order-id="{{ $orderData['order']->id }}" data-item-id="{{ $item->id }}">
                            <div class="order-card preparing">
                                <div class="order-number">
                                    Order #{{ $orderData['order']->order_number }}
                                    @if($orderData['order']->table)
                                        <span class="badge badge-dark" style="font-size: 1rem; margin-left: 10px;">
                                            Table: {{ $orderData['order']->table->table_number }}
                                        </span>
                                    @endif
                                </div>
                                <div class="order-info">
                                    <i class="fa fa-user"></i> <strong>Waiter:</strong> {{ $orderData['order']->waiter->full_name ?? 'N/A' }}
                                </div>
                                @if($item->prepared_at)
                                    <div class="time-badge">
                                        ⏱️ Started {{ $item->prepared_at->diffForHumans() }}
                                    </div>
                                @endif
                                <hr style="border-color: #555; margin: 15px 0;">
                                <div class="item-name">
                                    {{ $item->quantity }}x {{ $item->food_item_name }}
                                </div>
                                @if($item->variant_name)
                                    <div class="item-variant">{{ $item->variant_name }}</div>
                                @endif
                                @if($item->special_instructions)
                                    <div class="special-instructions">
                                        <i class="fa fa-exclamation-triangle"></i> {{ $item->special_instructions }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa fa-info-circle" style="font-size: 4rem; color: #ffc107;"></i>
                            <p>No orders being prepared</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Ready Orders -->
        <div class="status-section">
            <div class="status-title ready">
                <i class="fa fa-check-circle"></i> READY FOR PICKUP ({{ $readyOrders->count() }})
            </div>
            <div class="row" id="ready-orders">
                @forelse($readyOrders as $orderData)
                    @foreach($orderData['kitchen_items'] as $item)
                        <div class="col-lg-4 col-md-6" data-order-id="{{ $orderData['order']->id }}" data-item-id="{{ $item->id }}">
                            <div class="order-card ready">
                                <div class="order-number">
                                    Order #{{ $orderData['order']->order_number }}
                                    @if($orderData['order']->table)
                                        <span class="badge badge-light" style="font-size: 1rem; margin-left: 10px;">
                                            Table: {{ $orderData['order']->table->table_number }}
                                        </span>
                                    @endif
                                </div>
                                <div class="order-info">
                                    <i class="fa fa-user"></i> <strong>Waiter:</strong> {{ $orderData['order']->waiter->full_name ?? 'N/A' }}
                                </div>
                                @if($item->ready_at)
                                    <div class="time-badge">
                                        ⏱️ Ready {{ $item->ready_at->diffForHumans() }}
                                    </div>
                                @endif
                                <hr style="border-color: #555; margin: 15px 0;">
                                <div class="item-name">
                                    {{ $item->quantity }}x {{ $item->food_item_name }}
                                </div>
                                @if($item->variant_name)
                                    <div class="item-variant">{{ $item->variant_name }}</div>
                                @endif
                                <div class="alert alert-success" style="background: #28a745; color: white; border: none; margin-top: 15px; font-size: 1.2rem; font-weight: bold;">
                                    <i class="fa fa-bell"></i> WAITING FOR PICKUP
                                </div>
                            </div>
                        </div>
                    @endforeach
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa fa-check-circle" style="font-size: 4rem; color: #28a745;"></i>
                            <p>No ready orders</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            const dateString = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('current-time').textContent = dateString + ' | ' + timeString;
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Auto-refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

