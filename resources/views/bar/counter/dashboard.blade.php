@extends('layouts.dashboard')

@section('title', 'Counter Dashboard')

@section('content')
<style>
    :root {
        --brand: #940000;
        --brand-dark: #6b0000;
        --brand-light: rgba(148,0,0,0.08);
    }
    /* POS Styling */
    .product-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #eee;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(148,0,0,0.18);
        border-color: var(--brand);
    }
    .product-card .card-body {
        padding: 10px;
    }
    .product-card .product-title {
        font-size: 0.9rem;
        font-weight: bold;
        margin-bottom: 5px;
        height: 40px;
        overflow: hidden;
    }
    .product-card .product-price {
        color: var(--brand);
        font-weight: bold;
    }
    .product-card .stock-badge {
        font-size: 0.7rem;
    }
    .cart-tile {
        height: calc(100vh - 90px);
        min-height: 550px;
        display: flex;
        flex-direction: column;
    }
    #cart-items-container {
        flex-grow: 1;
        min-height: 200px;
        overflow-y: auto;
    }
    .sell-type-btn.active {
        background-color: var(--brand) !important;
        color: white !important;
        border-color: var(--brand) !important;
    }
    .payment-method-btn.active {
        background-color: #28a745 !important;
        color: white !important;
    }

    /* Animation for POS transition */
    #pos-section, #dashboard-content {
        transition: opacity 0.4s ease-in-out;
    }
    #pos-section { opacity: 0; display: none; }
    #dashboard-content { opacity: 1; }

    /* Category pills — brand colour when active */
    .category-pill {
        cursor: pointer;
        margin-right: 5px;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }
    .category-pill.active,
    .category-pill.badge-primary {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
        color: #fff !important;
    }

    /* Search bar accent */
    .input-group-text.bg-primary {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
    }

    /* POS modal header */
    .modal-header.bg-success { background-color: var(--brand) !important; }

    /* Place Order primary button */
    #btn-place-only.btn-primary {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
    }
    #btn-place-only.btn-primary:hover {
        background-color: var(--brand-dark) !important;
    }

    /* Cart total text in brand colour */
    #cart-total { color: var(--brand) !important; }

    /* Text-primary override for brand */
    .text-primary { color: var(--brand) !important; }
    .border-primary { border-color: var(--brand) !important; }
    .btn-primary { background-color: var(--brand) !important; border-color: var(--brand) !important; }
    .btn-primary:hover { background-color: var(--brand-dark) !important; border-color: var(--brand-dark) !important; }
    .btn-outline-primary { color: var(--brand) !important; border-color: var(--brand) !important; }
    .btn-outline-primary:hover, .btn-outline-primary.active { background-color: var(--brand) !important; color: #fff !important; }

    /* Food card top accent */
    .border-info.product-card:hover { border-color: var(--brand) !important; }

    /* POS header pill / badge-primary */
    .badge-primary { background-color: var(--brand) !important; }

    /* Complete Payment button */
    #btn-place-order-final { background-color: var(--brand) !important; border-color: var(--brand) !important; }
    #btn-place-order-final:hover { background-color: var(--brand-dark) !important; }

    /* Professional Loading Overlay */
    #pos-loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #fff;
        z-index: 9999;
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        transition: opacity 0.4s ease;
    }
    .pos-loader-spinner {
        width: 60px;
        height: 60px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--brand);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }
    /* Simple Compact Widgets */
    .widget-small { height: 90px !important; border-radius: 8px !important; margin-bottom: 20px; overflow: hidden; transition: transform 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important; }
    .widget-small:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important; }
    .widget-small.coloured-icon { background-color: #fff !important; }
    .widget-small.coloured-icon .info { color: #000 !important; }
    .widget-small .icon { min-width: 70px !important; padding: 10px !important; font-size: 1.8rem !important; }
    .widget-small .info h4 { font-size: 0.75rem !important; margin-bottom: 2px !important; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; color: #666; }
    .widget-small .info p { font-size: 16px !important; margin: 0 !important; font-weight: 700; }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    /* Print Styling for Verification Sheet */
    @media print {
        @page { size: auto; margin: 10mm; }
        /* Hide everything by default for surgical precision */
        .app-sidebar, .app-header, .app-breadcrumb, .app-title, 
        .d-print-none, .mt-4.pt-3.border-top, .tile-title-w-btn .text-right, 
        button, .app-footer, .view-toggle-btn,
        #verifyStockGrid, #pos-loader-overlay, #pos-section { 
            display: none !important; 
        }
        
        .app-content { margin: 0 !important; padding: 0 !important; }
        .tile.shadow-lg.border-primary {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* FORCE Table View on Print */
        #verifyStockList { 
            display: block !important; 
            max-height: none !important; 
            overflow: visible !important; 
            height: auto !important;
        }
        
        .d-print-block { display: block !important; }
        .d-print-table-cell { display: table-cell !important; }
        
        table { width: 100% !important; border-collapse: collapse !important; border: 1px solid #000 !important; }
        th, td { border: 1px solid #000 !important; padding: 10px !important; color: #000 !important; vertical-align: middle; }
        thead th { background-color: #f1f1f1 !important; -webkit-print-color-adjust: exact; font-weight: bold !important; text-transform: uppercase; font-size: 12px; }
        
        .text-success { color: #000 !important; } /* Print in black for contrast */
        .text-info { color: #555 !important; }
        .badge.badge-light { border: none !important; padding: 0; }
    }
</style>

<div id="pos-loader-overlay">
    <div class="pos-loader-spinner"></div>
    <h5 class="text-muted font-weight-bold">Loading POS Mode...</h5>
</div>

<div class="app-title">
  <div>
    <h1><i class="fa fa-tachometer"></i> Counter Dashboard</h1>
    <p>Welcome back, {{ session('staff_name') }}!</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Counter Dashboard</li>
  </ul>
</div>

<!-- DASHBOARD MAIN CONTENT -->
<div id="dashboard-content">
    @if(isset($needs_shift) && $needs_shift)
        <!-- Full Stock Verification View (Like Counter Stock Page) -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="tile shadow-lg border-primary" style="border-radius: 20px !important;">
                    <div class="tile-title-w-btn border-bottom pb-3 mb-4">
                        <h3 class="title"><i class="fa fa-clock-o text-primary"></i> <span class="d-print-none">Physical Stock Verification & Open Shift</span><span class="d-none d-print-block">Shift Opening Stock Sheet</span></h3>
                        
                        <div class="d-none d-print-block text-right mb-2">
                            <h5 class="mb-0">Date: {{ date('d M, Y') }}  |  Staff: {{ session('staff_name') }}</h5>
                        </div>

                        <div class="text-right d-none d-md-flex align-items-center">
                            <div class="btn-group mr-3 shadow-sm d-print-none">
                                <button type="button" class="btn btn-light btn-sm view-toggle-btn active" data-view="grid" title="Grid View">
                                    <i class="fa fa-th"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm view-toggle-btn" data-view="list" title="List View">
                                    <i class="fa fa-list"></i>
                                </button>
                            </div>
                            <a href="{{ route('bar.counter.stock.print-sheet') }}?print=true" target="_blank" class="btn btn-outline-secondary btn-sm mr-3 shadow-sm d-print-none">
                                <i class="fa fa-print"></i> Print Stock Sheet
                            </a>
                            <span class="badge badge-light border text-muted px-3 py-2" style="border-radius: 20px;">
                                <i class="fa fa-info-circle"></i> Verification required to start shift
                            </span>
                        </div>
                    </div>

                    <!-- SEARCH & FILTERS (Identical to Counter Stock) -->
                    <div class="row mb-4 d-print-none">
                        <div class="col-md-3">
                            <div class="form-group border-right pr-3">
                                <label class="smallest font-weight-bold text-uppercase text-muted">Search Products</label>
                                <div class="input-group input-group-sm shadow-xs">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-search"></i></span></div>
                                    <input type="text" id="verifySearch" class="form-control" placeholder="Type to search...">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <label class="smallest font-weight-bold text-uppercase text-muted">Quick Filters</label>
                            <div class="d-flex align-items-center overflow-auto no-scrollbar py-1" id="verifyFilterContainer">
                                <button class="btn btn-xs btn-outline-primary active verify-filter-pill mr-2 mb-1" data-filter="all">ALL ITEMS</button>
                                @foreach($categories as $cat)
                                    <button class="btn btn-xs btn-outline-primary verify-filter-pill mr-2 mb-1 text-uppercase" data-filter="{{ Str::slug($cat) }}">{{ $cat }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <form id="openShiftForm" action="{{ route('bar.counter.shift.open') }}" method="POST">
                        @csrf
                        <div class="tile-body">
                            <!-- 1. GRID VIEW (Current) -->
                            <div id="verifyStockGrid" class="row mx-n2" style="max-height: 500px; overflow-y: auto; padding-bottom: 20px;">
                                @forelse($variants as $variant)
                                    <div class="col-xl-2 col-lg-3 col-md-4 col-6 px-2 mb-3 verify-item-wrapper" 
                                         data-category="{{ Str::slug($variant['category']) }}"
                                         data-name="{{ strtolower($variant['product_name'] . ' ' . $variant['variant_name']) }}">
                                        
                                        <div class="p-2 bg-white rounded border shadow-xs h-100 transition-all hover-grow" 
                                             style="border-radius: 12px; border-top: 3px solid var(--brand) !important;">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div class="overflow-hidden">
                                                    <h6 class="smallest font-weight-bold text-dark mb-0 text-truncate" style="font-size: 11px;" title="{{ $variant['variant_name'] }}">{{ $variant['variant_name'] }}</h6>
                                                    <span class="smallest text-muted text-uppercase" style="font-size: 8px;">{{ $variant['category'] }}</span>
                                                </div>
                                                <span class="badge badge-light border text-muted" style="font-size: 8px; border-radius: 4px;">{{ $variant['measurement'] }}{{ $variant['unit'] }}</span>
                                            </div>

                                            <div class="bg-light rounded p-2 text-center mt-1" style="border: 1px dashed #ddd;">
                                                <div class="smallest text-muted text-uppercase" style="font-size: 8px; letter-spacing: 0.5px;">Counter Stock</div>
                                                <h5 class="mb-0 font-weight-bold text-success" style="font-size: 14px;">{{ $variant['formatted_quantity'] }}</h5>
                                                
                                                @if(isset($variant['can_sell_in_tots']) && $variant['can_sell_in_tots'] && $variant['quantity_in_tots'] > 0 && !str_contains($variant['formatted_quantity'], $variant['portion_unit_name']))
                                                    <div class="smallest font-weight-bold text-info border-top mt-1 pt-1" style="font-size: 9px;">
                                                        {{ number_format($variant['quantity_in_tots']) }} {{ $variant['portion_unit_name'] }}s
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center py-5">
                                        <i class="fa fa-cubes fa-3x text-muted opacity-25 mb-3"></i>
                                        <p class="h5 text-muted">No stock items found in counter location.</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- 2. LIST VIEW (Table) -->
                            <div id="verifyStockList" class="table-responsive d-none" style="max-height: 450px; overflow-y: auto;">
                                <table class="table table-hover table-bordered table-sm bg-white">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Product Item</th>
                                            <th>Category</th>
                                            <th>Size</th>
                                            <th class="text-center">System Stock</th>
                                            <th class="text-center d-none d-print-table-cell" style="width: 150px;">Physical Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($variants as $variant)
                                            <tr class="verify-item-wrapper" 
                                                data-category="{{ Str::slug($variant['category']) }}"
                                                data-name="{{ strtolower($variant['product_name'] . ' ' . $variant['variant_name']) }}">
                                                <td class="font-weight-bold">{{ $variant['variant_name'] }}</td>
                                                <td><span class="badge badge-light border">{{ $variant['category'] }}</span></td>
                                                <td>{{ $variant['measurement'] }}{{ $variant['unit'] }}</td>
                                                <td class="text-center">
                                                    <span class="h6 mb-0 text-success font-weight-bold">{{ $variant['formatted_quantity'] }}</span>
                                                    @if(isset($variant['can_sell_in_tots']) && $variant['can_sell_in_tots'] && $variant['quantity_in_tots'] > 0 && !str_contains($variant['formatted_quantity'], $variant['portion_unit_name']))
                                                        <br><small class="text-info font-weight-bold">{{ number_format($variant['quantity_in_tots']) }} {{ $variant['portion_unit_name'] }}{{ $variant['portion_unit_name'] === 'Glass' ? 'es' : 's' }}</small>
                                                    @endif
                                                </td>
                                                <td class="d-none d-print-table-cell" style="border-bottom: 1px solid #000 !important;"></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-4 pt-3 border-top no-gutters">
                                <div class="col-md-9 pr-md-3">
                                    <div class="form-group mb-0">
                                        <label class="smallest font-weight-bold text-uppercase text-muted"><i class="fa fa-sticky-note-o"></i> Handover/Discrepancy Notes</label>
                                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Note any missing items or stock variances here..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-3 text-md-right mt-3 mt-md-0">
                                    <button type="button" id="btn-confirm-open-shift" class="btn btn-primary px-4 py-2 shadow-sm w-100 w-md-auto" style="border-radius: 10px; font-size: 1rem; border: none; background: var(--brand); font-weight: 600;">
                                        <i class="fa fa-play mr-2"></i> VERIFY & OPEN SHIFT
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @else
        <!-- Welcome Banner with Shift Info -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile p-3 mb-4 bg-light border-left border-primary d-flex justify-content-between align-items-center shadow-sm">
                    <div>
                        <h5 class="mb-0 text-dark"><i class="fa fa-user-circle text-primary"></i> Working as: <span class="font-weight-bold">{{ $staff->full_name }}</span></h5>
                        <small class="text-muted">
                            <i class="fa fa-terminal"></i> Current Shift: <span class="text-info font-weight-bold">{{ $activeShift->shift_number }}</span> 
                            (Opened at {{ $activeShift->opened_at->format('H:i') }}) &nbsp;|&nbsp; 
                            <span id="shift-realtime-counter" data-opened-at="{{ $activeShift->opened_at->toISOString() }}" class="badge badge-success" style="font-size: 0.85em;">
                                <i class="fa fa-clock-o"></i> <span id="shift-timer-text">00:00:00</span>
                            </span>
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('bar.counter.reconciliation') }}" class="btn btn-outline-danger font-weight-bold shadow-sm">
                            <i class="fa fa-power-off"></i> CLOSE SHIFT
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
    <div class="row">
      <div class="col-md-3">
        <div class="widget-small primary coloured-icon">
          <i class="icon fa fa-shopping-bag fa-3x"></i>
          <div class="info">
            <h4>Shift Orders</h4>
            <p>{{ number_format($shiftOrderCount ?? 0) }}</p>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="widget-small success coloured-icon">
          <i class="icon fa fa-money fa-3x"></i>
          <div class="info">
            <h4>Shift Revenue</h4>
            <p>TSh {{ number_format($shiftRevenue ?? 0) }}</p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="widget-small info coloured-icon">
          <i class="icon fa fa-cubes fa-3x"></i>
          <div class="info">
            <h4>Inventory Items</h4>
            <p>{{ $counterStockItems }}</p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="widget-small danger coloured-icon">
          <i class="icon fa fa-bell fa-3x"></i>
          <div class="info">
            <h4>Pending Orders</h4>
            <p>{{ $pendingOrders }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <h3 class="tile-title">Quick Actions</h3>
          <div class="tile-body">
            <div class="row">
              <div class="col-md-3 mb-3">
                <button type="button" class="btn btn-primary btn-block btn-lg" id="btn-pos-mode">
                  <i class="fa fa-shopping-cart fa-2x"></i><br>
                  Place New Order (POS)
                </button>
              </div>
              <div class="col-md-3 mb-3">
                <a href="{{ route('bar.counter.waiter-orders') }}" class="btn btn-info btn-block btn-lg">
                  <i class="fa fa-list-alt fa-2x"></i><br>
                  My Orders
                  @if($pendingOrders > 0)
                    <span class="badge badge-danger">{{ $pendingOrders }}</span>
                  @endif
                </a>
              </div>
              <div class="col-md-3 mb-3">
                <a href="{{ route('bar.counter.customer-orders') }}" class="btn btn-warning btn-block btn-lg">
                  <i class="fa fa-users fa-2x"></i><br>
                  Customer Orders
                </a>
              </div>
              <div class="col-md-3 mb-3">
                <a href="{{ route('bar.counter.counter-stock') }}" class="btn btn-success btn-block btn-lg">
                  <i class="fa fa-cubes fa-2x"></i><br>
                  Counter Stock
                </a>
              </div>


              <div class="col-md-3 mb-3">
                <a href="{{ route('bar.counter-settings.index') }}" class="btn btn-secondary btn-block btn-lg">
                  <i class="fa fa-cog fa-2x"></i><br>
                  Settings
                </a>
              </div>

              <div class="col-md-3 mb-3">
                <a href="{{ route('bar.counter.shift.history') }}" class="btn btn-dark btn-block btn-lg">
                  <i class="fa fa-history fa-2x"></i><br>
                  Shift Tracking
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-md-6">
            <div class="tile">
                <h3 class="tile-title">Recent Orders</h3>
                <div class="table-responsive">
                    <table class="table table-hover table-sm" id="orders-table-recent">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Waiter/Staff</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                            <tr class="{{ $order->payment_status === 'paid' ? 'table-success' : ($order->status === 'cancelled' ? 'table-danger opacity-75' : '') }}">
                                <td>{{ $order->order_number }}</td>
                                <td>
                                    @if($order->order_source === 'counter')
                                        <span class="text-info font-weight-bold">Counter:</span> {{ $order->waiter ? $order->waiter->full_name : 'Staff' }}
                                    @else
                                        {{ $order->waiter ? $order->waiter->full_name : 'Counter' }}
                                    @endif
                                </td>
                                <td>TSh {{ number_format($order->total_amount) }}</td>
                                <td>
                                    @if($order->status == 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($order->status == 'served')
                                        <span class="badge badge-success">Served</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($order->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <!-- Primary Actions -->
                                        <div class="btn-group btn-group-sm mr-2 shadow-sm mb-1">
                                            <button class="btn btn-info view-order-details" 
                                                data-order-id="{{ $order->id }}" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </button>

                                            @if(($order->status === 'pending' || $order->status === 'served') && $order->payment_status !== 'paid')
                                                <button class="btn btn-primary btn-add-items" 
                                                    data-order-id="{{ $order->id }}" title="Add Items">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            @endif
                                        </div>

                                        @if($order->status === 'pending' && $order->payment_status !== 'paid')
                                            <!-- Print/Cancel Group -->
                                            <div class="btn-group btn-group-sm shadow-sm mb-1">
                                                <a href="{{ route('bar.counter.print-receipt', $order->id) }}" target="_blank" 
                                                   class="btn btn-dark" title="Print">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                                <button class="btn btn-danger btn-cancel-order" 
                                                    data-order-id="{{ $order->id }}" title="Cancel">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                        
                                        @if($order->status === 'served' && $order->payment_status !== 'paid')
                                            <button class="btn btn-sm btn-success btn-pay-order font-weight-bold px-3 mr-2 shadow-sm mb-1"
                                                data-order-id="{{ $order->id }}" data-total="{{ $order->total_amount }}" title="PAY NOW">
                                                <i class="fa fa-money"></i> <b>PAY</b>
                                            </button>
                                            
                                            <!-- Admin Group -->
                                            <div class="btn-group btn-group-sm shadow-sm mb-1">
                                                <a href="{{ route('bar.counter.print-receipt', $order->id) }}" target="_blank" class="btn btn-dark"><i class="fa fa-print"></i></a>
                                                <button class="btn btn-danger btn-cancel-order" data-order-id="{{ $order->id }}"><i class="fa fa-times"></i></button>
                                            </div>

                                        @elseif($order->payment_status === 'paid')
                                            <span class="badge badge-success px-2 py-1 mr-2 shadow-sm mb-1 text-uppercase" style="font-size: 0.65rem;">
                                                <i class="fa fa-check-circle"></i> Paid
                                            </span>
                                            <a href="{{ route('bar.counter.print-receipt', $order->id) }}" target="_blank" class="btn btn-sm btn-dark shadow-sm mb-1"><i class="fa fa-print"></i></a>

                                        @elseif($order->status === 'cancelled')
                                            <span class="badge badge-secondary ml-2 opacity-75 mb-1"><i class="fa fa-ban"></i> Cancelled</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Pagination for Recent Orders -->
                <div id="recent-orders-pagination" class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                    <button class="btn btn-sm btn-outline-primary" id="prev-recent-orders"><i class="fa fa-chevron-left"></i> Previous</button>
                    <span class="small text-muted" id="recent-orders-page-info">Page 1 of 1</span>
                    <button class="btn btn-sm btn-outline-primary" id="next-recent-orders">Next <i class="fa fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="col-md-6">
            <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="tile-title">Low Stock Alerts</h3>
                <p>
                    <a href="{{ route('bar.products.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus-circle"></i> Register Products (Add Item)
                    </a>
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Stock Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockItemsList as $item)
                        <tr>
                            <td class="font-weight-bold">{{ $item['product_name'] }} ({{ $item['variant'] }})</td>
                            <td class="text-danger font-weight-bold text-center">
                                <span class="badge badge-danger px-2">{{ $item['counter_qty'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
        </div>
    @endif
</div>

<!-- Legacy Close Shift Modal (Moved to Dedicated Page) -->


<!-- POS SECTION (Hidden by default) -->
<div id="pos-section" style="display: none; opacity: 0;">
  <div class="row mb-3">
    <div class="col-md-12">
      <div class="tile py-2">
        <div class="tile-title-w-btn mb-0">
          <div class="d-flex align-items-center">
              <h3 class="title mb-0"><i class="fa fa-shopping-cart"></i> Counter POC</h3>
              <div id="pos-mode-indicator" class="alert alert-warning py-1 px-2 mb-0 ml-3" style="display: none;">
                  <small><i class="fa fa-plus-circle"></i> Appending to Order #<span id="pos-appending-order-num"></span></small>
              </div>
          </div>
          <p class="mb-0">
              <button class="btn btn-secondary" id="btn-back-to-dashboard">
                  <i class="fa fa-arrow-left"></i> Dashboard
              </button>
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Product Selection Column -->
    <div class="col-md-8">
      <div class="tile">
        <!-- Search and Categories -->
        <div class="row mb-3">
          <div class="col-md-12">
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text bg-primary text-white"><i class="fa fa-search"></i></span>
              </div>
              <input type="text" class="form-control form-control-lg" id="product-search" placeholder="Search drinks or food items...">
            </div>
            
            <div class="d-flex flex-wrap mb-3" id="category-filters">
                <span class="badge badge-primary p-2 category-pill active" data-category="all">All Items</span>
                @php 
                    $uniqueCats = collect($variants)->pluck('category')->unique(); 
                @endphp
                @foreach($uniqueCats as $cat)
                    <span class="badge badge-secondary p-2 category-pill" data-category="cat-{{ \Illuminate\Support\Str::slug($cat) }}">{{ $cat }}</span>
                @endforeach
            </div>
          </div>
        </div>

        <!-- Product Grid -->
        <div id="pos-items-grid-container" style="max-height: 65vh; overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
            <div class="row" id="pos-items-grid">
            <!-- Drinks -->
            @foreach($variants as $v)
            @php 
                $vFullName = $v['variant_name'] ?: $v['product_name'];
            @endphp
            <div class="col-md-4 col-sm-6 mb-4 pos-item cat-drinks cat-{{ \Illuminate\Support\Str::slug($v['category']) }}" 
                 data-id="{{ $v['id'] }}" 
                 data-name="{{ $vFullName }}" 
                 data-variant="{{ $v['variant'] }}"
                 data-price="{{ $v['selling_price'] }}"
                 data-price-tot="{{ $v['selling_price_per_tot'] }}"
                 data-can-tot="{{ $v['can_sell_in_tots'] ? 'true' : 'false' }}"
                 data-unit="{{ $v['unit'] }}"
                 data-portion-unit="{{ $v['portion_unit_name'] }}"
                 data-pkg="{{ $v['packaging_type'] ?: 'Bottle' }}"
                 data-stock-qty="{{ $v['quantity'] }}"
                 data-stock-tots="{{ $v['quantity_in_tots'] }}"
                 data-total-tots="{{ $v['total_tots'] ?: 1 }}"
                 data-type="drink">
                <div class="card product-card h-100 {{ $v['is_low_stock'] ? 'border-warning shadow-sm' : '' }}" 
                     style="{{ $v['is_low_stock'] ? 'background-color: #fff9e6 !important; position: relative;' : '' }}">
                    @if($v['is_low_stock'])
                        <div class="badge badge-warning position-absolute" style="top: 10px; right: 10px; z-index: 10;">LOW STOCK</div>
                    @endif

                    @if($v['product_image'])
                        <img src="{{ asset('storage/' . $v['product_image']) }}" class="card-img-top" style="height: 100px; object-fit: cover;">
                    @else
                        <div class="bg-light text-center py-4 text-muted"><i class="fa fa-glass fa-2x"></i></div>
                    @endif
                    <div class="card-body p-2 d-flex flex-column" style="font-size: 0.85rem;">
                        <div class="product-title font-weight-bold text-dark mb-1" style="font-size: 0.95rem; line-height: 1.2; height: auto; min-height: 44px;">
                            {{ $vFullName }} <small class="text-muted d-block small mt-1">({{ $v['variant'] }})</small>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-1 mt-1">
                            <span class="text-secondary small">Available:</span>
                            <span class="font-weight-bold text-{{ $v['quantity'] < 1 ? 'danger' : 'dark' }} text-right">
                                {{ $v['formatted_quantity'] }}
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary small">Bottle Price:</span>
                            <span class="text-primary font-weight-bold">TSh {{ number_format($v['selling_price']) }}</span>
                        </div>
                        
                        @if($v['can_sell_in_tots'])
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary smallest font-weight-bold">{{ $v['portion_unit_name'] }} Price:</span>
                            <span class="text-info font-weight-bold">TSh {{ number_format($v['selling_price_per_tot']) }}</span>
                        </div>
                        @endif

                        <div class="mt-auto pt-2 border-top d-flex align-items-center">
                            @if($v['can_sell_in_tots'])
                                @php 
                                   $canSellBtl = $v['quantity'] >= 1;
                                   $canSellTot = $v['quantity_in_tots'] >= 1;
                                   $totLabel = ($v['portion_unit_name'] == 'Glass' ? 'Glasses' : $v['portion_unit_name'] . 's');
                                @endphp
                                <button class="btn btn-xs btn-primary font-weight-bold flex-fill mr-1 py-1 px-0 btn-pos-modal" 
                                    data-sell-type="unit" title="Add Bottle(s)" {{ !$canSellBtl ? 'disabled style=opacity:0.5;filter:grayscale(1);' : '' }}>
                                    <i class="fa fa-plus"></i> Bottles
                                </button>
                                <button class="btn btn-xs btn-info font-weight-bold flex-fill py-1 px-0 btn-pos-modal" 
                                    data-sell-type="tot" title="Add {{ $totLabel }}" {{ !$canSellTot ? 'disabled style=opacity:0.5;filter:grayscale(1);' : '' }}>
                                    <i class="fa fa-plus"></i> {{ $totLabel }}
                                </button>
                            @else
                                @php $canSell = $v['quantity'] >= 1; @endphp
                                @if($canSell)
                                    <div class="text-primary font-weight-bold flex-grow-1 text-center">
                                        <i class="fa fa-plus-circle"></i> Add to Order
                                    </div>
                                @else
                                    <div class="text-danger font-weight-bold flex-grow-1 text-center smallest">
                                        <i class="fa fa-ban"></i> OUT OF STOCK
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach


            </div>
        </div>
      </div>
    </div>

    <!-- Right Sidebar: Cart -->
    <div class="col-md-4">
      <div class="tile cart-tile shadow-sm">
        <h3 class="tile-title border-bottom pb-2"><i class="fa fa-shopping-basket text-primary"></i> Order List</h3>
        
        <div id="cart-items-container">
          <div class="text-center p-5 text-muted" id="empty-cart-msg">
            <i class="fa fa-shopping-cart fa-4x mb-3 opacity-50"></i>
            <h5>Empty Order</h5>
            <p>Select items from the left to start</p>
          </div>
          <table class="table table-sm table-striped" id="cart-table" style="display: none;">
            <thead>
              <tr class="bg-light">
                <th>Item</th>
                <th width="80">Qty</th>
                <th class="text-right">Total</th>
                <th width="30"></th>
              </tr>
            </thead>
            <tbody id="cart-tbody">
              <!-- Cart items row -->
            </tbody>
          </table>
        </div>
        
        <div class="cart-bottom-fixed border-top pt-1">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted small">Subtotal</span>
            <span id="cart-subtotal" class="font-weight-bold smallest">TSh 0</span>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <h5 class="mb-0 font-weight-bold">Payable Amount</h5>
            <h5 id="cart-total" class="text-primary mb-0 font-weight-bold">TSh 0</h5>
          </div>
          
          <div class="bg-light p-1 rounded mb-2 border shadow-xs">
              <div class="form-group mb-1">
                <label class="small font-weight-bold text-muted mb-0 text-uppercase" style="font-size: 10px;">Waiter Selection <span class="text-danger">*</span></label>
                <select class="form-control select2 form-control-sm" id="order-waiter" style="width: 100%;">
                  <option value="">-- Counter/Walk-in (Self) --</option>
                  @foreach($waiters as $waiter)
                    <option value="{{ $waiter->id }}">{{ $waiter->full_name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group mb-1">
                <label class="small font-weight-bold text-muted mb-0 text-uppercase" style="font-size: 10px;">Table Selection</label>
                <select class="form-control select2 form-control-sm" id="order-table" style="width: 100%;">
                  <option value="">-- No Table --</option>
                  @foreach($tables as $table)
                    <option value="{{ $table['id'] }}">Table {{ $table['table_number'] }} ({{ $table['location'] }}) - {{ $table['status'] }}</option>
                  @endforeach
                </select>
              </div>
              
              <div class="row no-gutters">
                  <div class="col-md-6 pr-md-1 mb-2 mb-md-0">
                      <div class="input-group input-group-sm">
                          <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-user"></i></span></div>
                          <input type="text" id="pos-customer-name" class="form-control" placeholder="Guest Name">
                      </div>
                  </div>
                  <div class="col-md-6 pl-md-1">
                      <div class="input-group input-group-sm">
                          <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-phone"></i></span></div>
                          <input type="text" id="pos-customer-phone" class="form-control" placeholder="Guest Phone">
                      </div>
                  </div>
              </div>
          </div>
          <input type="hidden" id="pos-existing-order-id" value="">
          
          <div class="row mt-2">
              <div class="col-10 pr-1">
                  <button class="btn btn-primary btn-block btn-lg font-weight-bold shadow-sm py-2" id="btn-place-only" disabled>
                    <i class="fa fa-save"></i> <span class="text-uppercase">Place Order</span>
                  </button>
              </div>
              <div class="col-2 pl-1">
                  <button class="btn btn-outline-danger btn-block btn-lg" id="btn-clear-cart" title="Clear All Items">
                    <i class="fa fa-trash"></i>
                  </button>
              </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-plus-circle"></i> Add to Order</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="modal-item-id">
        <input type="hidden" id="modal-item-type">
        <input type="hidden" id="modal-item-price">
        <input type="hidden" id="modal-item-price-tot">
        <input type="hidden" id="modal-item-name">
        <input type="hidden" id="modal-item-variant">
        <input type="hidden" id="modal-item-stock-qty">
        <input type="hidden" id="modal-item-stock-tots">
        <input type="hidden" id="modal-item-total-tots">

        <div class="text-center mb-4">
          <h3 id="modal-display-name" class="font-weight-bold text-dark"></h3>
          <h4 id="modal-display-price" class="text-primary"></h4>
        </div>

        <div id="sell-type-group" class="mb-4" style="display: none;">
          <label class="font-weight-bold">Selling Option</label>
          <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
            <label class="btn btn-outline-info flex-fill p-3 active">
              <input type="radio" name="sell_type" value="unit" checked> 
              <i class="fa fa-square-o fa-2x mb-2 d-block"></i> <span id="modal-unit-label">Unit</span>
            </label>
            <label class="btn btn-outline-info flex-fill p-3" id="label-sell-tot">
              <input type="radio" name="sell_type" value="tot"> 
              <i class="fa fa-glass fa-2x mb-2 d-block"></i> <span id="modal-portion-label">Shots/Tots</span>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="font-weight-bold">Quantity</label>
          <div class="input-group input-group-lg">
            <div class="input-group-prepend">
              <button class="btn btn-dark px-4" type="button" id="btn-qty-minus"><i class="fa fa-minus"></i></button>
            </div>
            <input type="number" class="form-control text-center font-weight-bold" id="modal-quantity" value="1" min="1">
            <div class="input-group-append">
              <button class="btn btn-dark px-4" type="button" id="btn-qty-plus"><i class="fa fa-plus"></i></button>
            </div>
          </div>
        </div>

        <div id="food-notes-group" class="mt-3" style="display: none;">
          <label class="font-weight-bold">Preparation Notes</label>
          <textarea class="form-control" id="modal-notes" rows="2" placeholder="e.g., Spicy, No onions..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-0 p-4">
        <button type="button" class="btn btn-light btn-lg flex-fill" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-lg flex-fill font-weight-bold" id="btn-add-to-cart-confirm">ADD TO ORDER</button>
      </div>
    </div>
  </div>
</div>

<!-- Checkout / Payment Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title font-weight-bold"><i class="fa fa-credit-card"></i> Process Payment</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4">
        <div class="bg-light p-3 rounded mb-4 text-center border">
          <small class="text-muted d-block text-uppercase font-weight-bold">Total Amount Due</small>
          <h2 class="mb-0 text-dark font-weight-bold" id="checkout-total-display">TSh 0</h2>
          <input type="hidden" id="checkout-order-id" value="">
        </div>

        <div class="form-group">
          <label class="font-weight-bold">Select Payment Mode</label>
          <div class="btn-group btn-group-toggle d-flex flex-wrap" data-toggle="buttons">
            <label class="btn btn-outline-success flex-fill active p-3">
              <input type="radio" name="payment_method" value="cash" checked>
              <i class="fa fa-money fa-2x mb-2 d-block"></i> CASH
            </label>
            <label class="btn btn-outline-info flex-fill p-3">
              <input type="radio" name="payment_method" value="mobile_money">
              <i class="fa fa-mobile fa-3x mb-2 d-block"></i> MOBILE MONEY
            </label>
            <label class="btn btn-outline-primary flex-fill p-3">
              <input type="radio" name="payment_method" value="bank">
              <i class="fa fa-university fa-2x mb-2 d-block"></i> BANK
            </label>
            <label class="btn btn-outline-dark flex-fill p-3">
              <input type="radio" name="payment_method" value="card">
              <i class="fa fa-credit-card fa-2x mb-2 d-block"></i> CARD
            </label>
          </div>
        </div>

        {{-- Mobile Money --}}
        <div id="mobile-money-details" style="display: none;" class="mt-3 p-3 bg-light border-info border rounded">
          <div class="form-group">
            <label class="font-weight-bold small">MM Provider</label>
            <select class="form-control" id="mobile-money-provider">
              <option value="Halopesa">Halopesa</option>
              <option value="Mixx By Yas">Mixx By Yas</option>
              <option value="M-Pesa">M-Pesa</option>
              <option value="Airtel Money">Airtel Money</option>
              <option value="T-Pesa">T-Pesa</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold small">Transaction Reference / Receipt #</label>
            <input type="text" id="mobile-money-ref" class="form-control" placeholder="Enter Reference ID">
          </div>
        </div>

        {{-- Bank Transfer --}}
        <div id="bank-details" style="display: none;" class="mt-3 p-3 bg-light border-primary border rounded">
          <div class="form-group">
            <label class="font-weight-bold small">Bank Name</label>
            <select class="form-control" id="bank-provider">
              <option value="CRDB Bank">CRDB Bank</option>
              <option value="NMB Bank">NMB Bank</option>
              <option value="NBC Bank">NBC Bank</option>
              <option value="Stanbic Bank">Stanbic Bank</option>
              <option value="Equity Bank">Equity Bank</option>
              <option value="Absa Bank">Absa Bank</option>
              <option value="DTB Bank">DTB Bank</option>
              <option value="KCB Bank">KCB Bank</option>
              <option value="Exim Bank">Exim Bank</option>
              <option value="Azania Bank">Azania Bank</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold small">Bank Slip / Reference #</label>
            <input type="text" id="bank-ref" class="form-control" placeholder="Enter bank slip or reference number">
          </div>
        </div>

        {{-- Card Payment --}}
        <div id="card-details" style="display: none;" class="mt-3 p-3 bg-light border-dark border rounded">
          <div class="form-group">
            <label class="font-weight-bold small">Card Type</label>
            <select class="form-control" id="card-provider">
              <option value="Visa">Visa</option>
              <option value="Mastercard">Mastercard</option>
              <option value="Amex">American Express</option>
              <option value="UnionPay">UnionPay</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold small">Card Approval Code</label>
            <input type="text" id="card-ref" class="form-control" placeholder="Enter approval / authorization code">
          </div>
        </div>

      </div>
      <div class="modal-footer border-0 p-4 pt-0">
          <button type="button" class="btn btn-success btn-lg btn-block font-weight-bold py-3 shadow-sm" id="btn-place-order-final">
              <i class="fa fa-check-circle"></i> COMPLETE & PROCESS PAYMENT
          </button>
      </div>
    </div>
  </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg border-0" role="document">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="orderDetailsModalLabel">Order Details View</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0" id="orderDetailsContent">
        <div class="text-center p-5">
          <i class="fa fa-spinner fa-spin fa-3x text-primary mb-3"></i>
          <p class="h5">Loading order details...</p>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let cart = [];
    
    // Initialize Select2
    $('.select2').select2({
        width: '100%',
        placeholder: "Search...",
        allowClear: true
    });

    // --- VERIFY STOCK SEARCH & FILTER ---
    $('#verifySearch').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.verify-item-wrapper').each(function() {
            let name = $(this).data('name').toLowerCase();
            $(this).toggle(name.includes(val));
        });
    });

    $('.verify-filter-pill').on('click', function() {
        $('.verify-filter-pill').removeClass('active');
        $(this).addClass('active');
        let filter = $(this).data('filter');
        
        $('.verify-item-wrapper').each(function() {
            if (filter === 'all') {
                $(this).show();
            } else {
                $(this).toggle($(this).data('category') === filter);
            }
        });
    });

    $('.view-toggle-btn').on('click', function() {
        $('.view-toggle-btn').removeClass('active btn-primary text-white').addClass('btn-light');
        $(this).addClass('active btn-primary text-white').removeClass('btn-light');
        
        let view = $(this).data('view');
        if (view === 'grid') {
            $('#verifyStockGrid').removeClass('d-none');
            $('#verifyStockList').addClass('d-none');
        } else {
            $('#verifyStockGrid').addClass('d-none');
            $('#verifyStockList').removeClass('d-none');
        }
    });
    
    $('#btn-confirm-open-shift').on('click', function() {
        Swal.fire({
            title: "Confirm Stock Verification?",
            text: "By opening the shift, you confirm that you have physically verified the counter stock and it matches the numbers shown above.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Start My Shift",
            cancelButtonText: "No, Check Again",
            confirmButtonColor: '#940000',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show sleek toast notification on the right
                showToast('success', 'Opening shift... Please wait.', 'Verified!');
                
                // Submit form after a tiny delay to let toast be seen
                setTimeout(() => {
                    $('#openShiftForm').submit();
                }, 800);
            }
        });
    });

    $('#btn-pos-mode').on('click', function() {
        $('#dashboard-content').css('opacity', '0');
        setTimeout(() => {
            $('#dashboard-content').hide();
            $('#pos-section').show().css('opacity', '1');
            renderCart();
        }, 400);
    });

    $('#btn-back-to-dashboard').on('click', function() {
        $('#pos-section').css('opacity', '0');
        setTimeout(() => {
            $('#pos-section').hide();
            $('#dashboard-content').show().css('opacity', '1');
            // Reset POS state
            $('#pos-existing-order-id').val('');
            $('#pos-mode-indicator').hide();
            $('#order-table').val('').trigger('change');
            $('#pos-customer-name').val('');
            $('#pos-customer-phone').val('');
            cart = [];
            renderCart();
        }, 400);
    });

    // --- PRODUCT SEARCH & FILTER ---
    $('#product-search').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.pos-item').each(function() {
            let name = $(this).data('name').toLowerCase();
            let variant = ($(this).data('variant') || '').toLowerCase();
            if (name.includes(val) || variant.includes(val)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('.category-pill').on('click', function() {
        $('.category-pill').removeClass('active badge-primary').addClass('badge-secondary');
        $(this).addClass('active badge-primary').removeClass('badge-secondary');
        
        let cat = $(this).data('category');
        if (cat === 'all') {
            $('.pos-item').fadeIn(200);
        } else {
            $('.pos-item').hide();
            $('.pos-item.' + cat).fadeIn(200);
        }
    });

    // --- CART ACTIONS ---
    // Handle Modal Trigger Buttons (New logic: both Bottle and Glass buttons open the quantity modal)
    $(document).on('click', '.btn-pos-modal', function(e) {
        e.stopPropagation();
        const card = $(this).closest('.pos-item');
        const sellType = $(this).data('sell-type');
        
        openAddItemModal(card, sellType);
    });

    $(document).on('click', '.pos-item', function() {
        openAddItemModal(this, 'unit'); // Default to unit (bottle) when clicking card body
    });

    function openAddItemModal(input, defaultSellType) {
        // Handle both raw data object OR jQuery/DOM element
        const isEl = input instanceof jQuery || input instanceof HTMLElement;
        const el = isEl ? $(input) : null;
        const item = isEl ? el.data() : input;

        // Force refresh core attributes directly from attributes to beat caching
        if (isEl) {
            item.pkg = el.attr('data-pkg');
            item.portionUnit = el.attr('data-portion-unit');
            item.canTot = el.attr('data-can-tot');
        }

        const isPortionItem = (item.canTot == 'true' || item.canTot === true);

        $('#modal-item-id').val(item.id);
        $('#modal-item-type').val(item.type);
        $('#modal-item-price').val(item.price);
        $('#modal-item-price-tot').val(isPortionItem ? item.priceTot : '');
        $('#modal-item-name').val(item.name);
        $('#modal-item-variant').val(item.variant || '');
        $('#modal-item-stock-qty').val(item.stockQty || 0);
        $('#modal-item-stock-tots').val(item.stockTots || 0);
        $('#modal-item-total-tots').val(item.totalTots || 1);
        
        $('#modal-display-name').text(item.name + (item.variant ? ' (' + item.variant + ')' : ''));
        $('#modal-display-price').text('TSh ' + parseInt(item.price || 0).toLocaleString());
        
        if (item.type === 'drink') {
            const stockText = (item.stockQty > 0) ? `Available: ${parseFloat(item.stockQty).toFixed(2)} Bottles` : 'OUT OF STOCK';
            $('#modal-stock-info').remove();
            $('#modal-display-name').after(`<div class="text-muted small text-center mb-2" id="modal-stock-info">${stockText}</div>`);
        } else {
            $('#modal-stock-info').remove();
        }
        
        // Sync Guest info from sidebar if not already set in checkout
        $('#checkout-customer-name').val($('#pos-customer-name').val());
        $('#checkout-customer-phone').val($('#pos-customer-phone').val());
        
        $('#modal-quantity').val(1);
        $('#modal-notes').val('');
        
        if (item.type === 'drink') {
            const packaging = item.pkg || item.packagingType || 'Bottle';
            $('#modal-unit-label').text(packaging);
            
            $('#sell-type-group').show();
            
            if (isPortionItem) {
                $('#label-sell-tot').show();
                // Pluralize portion label
                const portionUnit = item.portionUnit || 'Portion';
                const pluralUnit = portionUnit === 'Glass' ? 'Glasses' : (portionUnit + 's');
                $('#modal-portion-label').text(pluralUnit);
                
                // Select the sell type based on which button was clicked
                const activeType = defaultSellType || 'unit';
                $(`input[name="sell_type"][value="${activeType}"]`).prop('checked', true).parent().addClass('active').siblings().removeClass('active');
                
                // Update display price immediately to match selected type
                const price = activeType === 'tot' ? item.priceTot : item.price;
                $('#modal-display-price').text('TSh ' + parseInt(price || 0).toLocaleString());
            } else {
                $('#label-sell-tot').hide();
                $(`input[name="sell_type"][value="unit"]`).prop('checked', true).parent().addClass('active').siblings().removeClass('active');
            }
        } else {
            $('#sell-type-group').hide();
        }
        
        if (item.type === 'food') {
            $('#food-notes-group').show();
        } else {
            $('#food-notes-group').hide();
        }
        
        $('#addItemModal').modal('show');
    }

    // Quantity buttons in modal
    $('#btn-qty-minus').on('click', function() {
        let q = parseInt($('#modal-quantity').val());
        if (q > 1) $('#modal-quantity').val(q - 1);
    });
    $('#btn-qty-plus').on('click', function() {
        let q = parseInt($('#modal-quantity').val());
        $('#modal-quantity').val(q + 1);
    });

    // Handle Sell Type selection styling
    $('input[name="sell_type"]').on('change', function() {
        $(this).parent().addClass('active').siblings().removeClass('active');
        const price = $(this).val() === 'tot' ? $('#modal-item-price-tot').val() : $('#modal-item-price').val();
        $('#modal-display-price').text('TSh ' + parseInt(price).toLocaleString());
    });

    // Refactored Add to Cart logic to support both Quick Add and Modal
    function addToCart(data) {
        // Check if item already in cart with same sell type
        const existingIndex = cart.findIndex(i => 
            (data.type === 'food' ? i.food_item_id == data.id : i.variant_id == data.id) && i.sell_type === data.sell_type && i.notes === data.notes
        );
        
        if (existingIndex > -1) {
            cart[existingIndex].quantity += data.quantity;
        } else {
            const cartItem = {
                product_name: data.name,
                variant_name: data.variant,
                quantity: data.quantity,
                price: data.price,
                sell_type: data.sell_type,
                portion_unit: data.portion_unit,
                notes: data.notes
            };
            
            if (data.type === 'food') {
                cartItem.food_item_id = data.id;
            } else {
                cartItem.variant_id = data.id;
            }
            
            cart.push(cartItem);
        }
        
        renderCart();
        showToast('success', data.name + ' added to order', 'Order Updated');
    }

    // Add to Cart Confirm (from Modal)
    $('#btn-add-to-cart-confirm').on('click', function() {
        const id = $('#modal-item-id').val();
        const type = $('#modal-item-type').val();
        const sellType = type === 'drink' ? $('input[name="sell_type"]:checked').val() : 'unit';
        const price = sellType === 'tot' ? $('#modal-item-price-tot').val() : $('#modal-item-price').val();
        const name = $('#modal-item-name').val();
        const variant = $('#modal-item-variant').val();
        const qty = parseInt($('#modal-quantity').val());
        const notes = $('#modal-notes').val();
        
        const stockQty = parseFloat($('#modal-item-stock-qty').val());
        const stockTots = parseInt($('#modal-item-stock-tots').val());
        const totPerBottle = parseInt($('#modal-item-total-tots').val());
        
        // Stock Validation
        if (type === 'drink') {
            // Find ALL items in the current cart with the SAME variant ID to calculate total equivalent bottles
            const inCartItems = cart.filter(i => i.variant_id == id);
            let totalTotsNeeded = 0;
            
            inCartItems.forEach(i => {
                if (i.sell_type === 'unit') {
                    totalTotsNeeded += i.quantity * totPerBottle;
                } else if (i.sell_type === 'tot') {
                    totalTotsNeeded += i.quantity;
                }
            });
            
            // Add what we're trying to add now
            if (sellType === 'unit') {
                totalTotsNeeded += qty * totPerBottle;
            } else if (sellType === 'tot') {
                totalTotsNeeded += qty;
            }
            
            if (totalTotsNeeded > stockTots) {
                const bottlesLeft = (stockTots / totPerBottle).toFixed(2);
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Stock',
                    text: `You only have ${bottlesLeft} bottles (${stockTots} portions) left. Please reduce the quantity.`,
                    confirmButtonColor: '#007bff'
                });
                return;
            }
        }
        
        addToCart({
            id: id,
            type: type,
            name: name,
            variant: variant,
            price: parseFloat(price),
            quantity: qty,
            sell_type: sellType,
            portion_unit: $('#modal-portion-label').text(), // Get the dynamic name from modal label
            notes: notes
        });
        
        $('#addItemModal').modal('hide');
    });

    function renderCart() {
        const tbody = $('#cart-tbody');
        tbody.empty();
        
        if (cart.length === 0) {
            $('#empty-cart-msg').show();
            $('#cart-table').hide();
            $('#btn-checkout').prop('disabled', true);
            $('#cart-subtotal, #cart-total').text('TSh 0');
            return;
        }
        
        $('#empty-cart-msg').hide();
        $('#cart-table').show();
        $('#btn-checkout').prop('disabled', false);
        $('#btn-place-only').prop('disabled', false);
        
        let total = 0;
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            let row = `<tr class="border-bottom">
                <td class="py-2">
                    <div class="font-weight-bold text-dark">${item.product_name}</div>
                    <div class="small text-muted line-height-1">
                        ${item.variant_name} 
                        ${item.sell_type === 'tot' ? `<span class="badge badge-info ml-1 px-1">${item.portion_unit || 'Portion'}</span>` : ''}
                    </div>
                    ${item.notes ? `<div class="smallest text-info mt-1"><i><i class="fa fa-sticky-note-o"></i> ${item.notes}</i></div>` : ''}
                </td>
                <td class="align-middle text-center py-2" style="width: 70px;">
                    <span class="smallest text-muted d-block">Qty</span>
                    <span class="font-weight-bold h6 mb-0">${item.quantity}</span>
                </td>
                <td class="text-right align-middle py-2">
                    <span class="smallest text-muted d-block">${item.quantity} @ TSh ${item.price.toLocaleString()}</span>
                    <span class="font-weight-bold text-primary">TSh ${itemTotal.toLocaleString()}</span>
                </td>
                <td class="text-right align-middle">
                    <button class="btn btn-sm btn-link text-danger btn-remove-cart" data-index="${index}"><i class="fa fa-times"></i></button>
                </td>
            </tr>`;
            tbody.append(row);
        });
        
        $('#cart-subtotal, #cart-total, #checkout-total-display').text('TSh ' + total.toLocaleString());
    }

    $(document).on('click', '.btn-remove-cart', function() {
        const idx = $(this).data('index');
        cart.splice(idx, 1);
        renderCart();
    });

    $('#btn-clear-cart').on('click', function() {
        if (confirm('Clear all items from order?')) {
            cart = [];
            renderCart();
        }
    });

    // --- CHECKOUT & PAYMENT ---
    $('#btn-checkout').on('click', function() {
        $('#checkout-order-id').val($('#pos-existing-order-id').val());
        $('#checkoutModal').modal('show');
    });

    $('#order-table').on('change', function() {
        if ($(this).val()) {
            $('#place-only-wrapper').slideDown();
        } else {
            $('#place-only-wrapper').slideUp();
        }
    });

    $('#btn-place-only').on('click', function() {
        if (cart.length === 0) return;
        
        const btn = $(this);
        const existingOrderId = $('#pos-existing-order-id').val();
        
        const originalBtnText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> SAVING...');
        
        const orderData = {
            items: cart,
            table_id: $('#order-table').val(),
            waiter_id: $('#order-waiter').val(),
            customer_name: $('#pos-customer-name').val(),
            customer_phone: $('#pos-customer-phone').val(),
            order_notes: $('#checkout-notes').val(), // Added missing notes field
            existing_order_id: existingOrderId,
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("bar.counter.create-order") }}',
            method: 'POST',
            data: orderData,
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Order stored successfully.', 'Saved');
                    setTimeout(() => { window.location.href = "{{ route('bar.counter.waiter-orders') }}"; }, 800);
                }
            },
            error: function(err) {
                btn.prop('disabled', false).html(originalBtnText);
                let errMsg = "Unknown error";
                if (err.responseJSON) {
                    errMsg = err.responseJSON.error || err.responseJSON.message || "Request failed";
                    if (err.responseJSON.errors) {
                        const firstErr = Object.values(err.responseJSON.errors)[0][0];
                        errMsg = firstErr || errMsg;
                    }
                }
                showAlert('error', errMsg, 'Failed');
            }
        });
    });

    $('input[name="payment_method"]').on('change', function() {
        const val = $(this).val();
        $('#mobile-money-details, #bank-details, #card-details').slideUp();
        if (val === 'mobile_money') {
            $('#mobile-money-details').slideDown();
        } else if (val === 'bank') {
            $('#bank-details').slideDown();
        } else if (val === 'card') {
            $('#card-details').slideDown();
        }
    });

    $('#btn-place-order-final, #btn-pay-later-final').on('click', function() {
        const isPayLater = $(this).attr('id') === 'btn-pay-later-final';
        const btn = $(this);
        const method = $('input[name="payment_method"]:checked').val() || 'cash';
        
        if (cart.length === 0 && !$('#checkout-order-id').val()) {
            showAlert('warning', 'Your order list is empty!');
            return;
        }

        const existingOrderId = $('#checkout-order-id').val();
        
        if (method === 'mobile_money' && !$('#mobile-money-ref').val() && !isPayLater) {
            showAlert('warning', 'Please enter reference number for mobile money');
            return;
        }
        if (method === 'bank' && !$('#bank-ref').val() && !isPayLater) {
            showAlert('warning', 'Please enter Bank Slip / Reference number');
            return;
        }
        if (method === 'card' && !$('#card-ref').val() && !isPayLater) {
            showAlert('warning', 'Please enter the Card Approval Code');
            return;
        }

        const originalBtnText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> PROCESSING...');
        
        if (existingOrderId) {
            if (isPayLater) {
                $('#checkoutModal').modal('hide');
                btn.prop('disabled', false).html(originalBtnText);
                return;
            }
            // RECORD PAYMENT FOR EXISTING ORDER
            $.ajax({
                url: '{{ url("bar/counter/record-payment") }}/' + existingOrderId,
                method: 'POST',
                data: {
                    payment_method: method,
                    mobile_money_number: method === 'mobile_money' ? $('#mobile-money-provider').val()
                                      : method === 'bank' ? $('#bank-provider').val()
                                      : method === 'card' ? $('#card-provider').val()
                                      : null,
                    transaction_reference: method === 'mobile_money' ? $('#mobile-money-ref').val()
                                        : method === 'bank' ? $('#bank-ref').val()
                                        : method === 'card' ? $('#card-ref').val()
                                        : null,
                    _token: '{{ csrf_token() }}'
                },
                success: function(payResponse) {
                    $('#checkoutModal').modal('hide');
                    showToast('success', 'Order payment recorded.', 'Success!');
                    window.open('{{ url("bar/counter/print-receipt") }}/' + existingOrderId, '_blank');
                    setTimeout(() => { window.location.href = "{{ route('bar.counter.waiter-orders') }}"; }, 1000);
                },
                error: function(err) {
                    btn.prop('disabled', false).html(originalBtnText);
                    let errMsg = "Payment failed";
                    if (err.responseJSON) {
                        errMsg = err.responseJSON.error || err.responseJSON.message || errMsg;
                    }
                    showAlert('error', errMsg, 'Error');
                }
            });
        } else {
            // NEW ORDER FLOW
            const orderData = {
                items: cart,
                table_id: $('#order-table').val(),
                waiter_id: $('#order-waiter').val(),
                customer_name: $('#checkout-customer-name').val() || $('#pos-customer-name').val(),
                customer_phone: $('#checkout-customer-phone').val() || $('#pos-customer-phone').val(),
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                url: '{{ route("bar.counter.create-order") }}',
                method: 'POST',
                data: orderData,
                success: function(response) {
                    if (response.success) {
                        const orderId = response.order.id;
                        
                        if (isPayLater) {
                            $('#checkoutModal').modal('hide');
                            cart = [];
                            renderCart();
                            showToast('success', 'Order placed successfully.', 'Success!');
                            window.open('{{ url("bar/counter/print-receipt") }}/' + orderId, '_blank');
                            setTimeout(() => { window.location.href = "{{ route('bar.counter.waiter-orders') }}"; }, 1000);
                            return;
                        }

                        // Record Payment immediately
                        $.ajax({
                            url: '{{ url("bar/counter/record-payment") }}/' + orderId,
                            method: 'POST',
                            data: {
                                payment_method: method,
                                mobile_money_number: method === 'mobile_money' ? $('#mobile-money-provider').val()
                                                  : method === 'bank' ? $('#bank-provider').val()
                                                  : method === 'card' ? $('#card-provider').val()
                                                  : null,
                                transaction_reference: method === 'mobile_money' ? $('#mobile-money-ref').val()
                                                    : method === 'bank' ? $('#bank-ref').val()
                                                    : method === 'card' ? $('#card-ref').val()
                                                    : null,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(payResponse) {
                                $('#checkoutModal').modal('hide');
                                cart = [];
                                renderCart();
                                showToast('success', 'Order completed successfully.', 'Success!');
                                window.open('{{ url("bar/counter/print-receipt") }}/' + orderId, '_blank');
                                setTimeout(() => { window.location.href = "{{ route('bar.counter.waiter-orders') }}"; }, 1000);
                            },
                            error: function(err) {
                                btn.prop('disabled', false).html(originalBtnText);
                                showAlert('error', 'Order created but payment failed: ' + (err.responseJSON ? err.responseJSON.error : 'Unknown error'), 'Alert');
                            }
                        });
                    } else {
                        btn.prop('disabled', false).html(originalBtnText);
                        showAlert('error', response.error || 'Failed to create order', 'Error');
                    }
                },
                error: function(err) {
                    btn.prop('disabled', false).html(originalBtnText);
                    showAlert('error', err.responseJSON ? err.responseJSON.error : 'Order creation failed', 'Error');
                }
            });
        }
    });

    // --- ORDER ACTIONS (MARK SERVED / PAY / CANCEL / VIEW / ADD) ---
    $(document).on('click', '.btn-mark-served', function() {
        const orderId = $(this).data('order-id');
        Swal.fire({
            title: 'Mark as Served?',
            text: "This will deduct items from counter stock.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, mark served'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("bar.counter.update-order-status", ":id") }}'.replace(':id', orderId),
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', status: 'served' },
                    success: function(response) {
                        showToast('success', response.message, 'Updated');
                        location.reload();
                    },
                    error: function(xhr) {
                        showAlert('error', xhr.responseJSON.error || "Failed to update status", 'Error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-pay-order', function() {
        const orderId = $(this).data('order-id');
        const total = $(this).data('total');
        
        $('#checkout-order-id').val(orderId);
        $('#checkout-total-display').text('TSh ' + parseInt(total).toLocaleString());
        $('#checkoutModal').modal('show');
    });

    $(document).on('click', '.btn-cancel-order', function() {
        const orderId = $(this).data('order-id');
        const orderNum = $(this).data('order-number');
        
        Swal.fire({
            title: 'Cancel Order?',
            text: `Are you sure you want to cancel order ${orderNum}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Cancel it!',
            input: 'text',
            inputPlaceholder: 'Reason for cancellation (REQUIRED)',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to write a reason!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("bar.counter.cancel-order", ":id") }}'.replace(':id', orderId),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        reason: result.value
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message, 'Order Cancelled');
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        showAlert('error', xhr.responseJSON.error || "Failed to cancel order", 'Error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-add-items', function() {
        const tableId = $(this).data('table-id');
        const orderId = $(this).data('order-id');
        const orderNum = $(this).data('order-num');
        
        // High-end Professional Transition
        $('#pos-loader-overlay').css('display', 'flex').hide().fadeIn(300);
        $('#dashboard-content').css('opacity', '0');

        setTimeout(() => {
            $('#dashboard-content').hide();
            if (tableId) {
                $('#order-table').val(tableId).trigger('change');
            }
            
            $('#pos-existing-order-id').val(orderId || '');
            if (orderId) {
                $('#pos-appending-order-num').text(orderNum);
                $('#pos-mode-indicator').show();
            } else {
                $('#pos-mode-indicator').hide();
            }
            
            $('#pos-section').show().css('opacity', '1');
            renderCart();

            // Fade out the professional loader
            $('#pos-loader-overlay').fadeOut(400, function() {
                showToast('success', 'POS ready for Order #' + orderNum, 'Direct Mode');
            });
        }, 600);
    });

    $(document).on('click', '.view-order-details', function() {
        const orderId = $(this).data('order-id');
        $('#orderDetailsContent').html('<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-3x text-primary mb-3"></i><p class="h5">Loading order details...</p></div>');
        $('#orderDetailsModal').modal('show');

        $.ajax({
            url: '{{ route("bar.orders.details", ":id") }}'.replace(':id', orderId),
            method: 'GET',
            success: function(response) {
                const order = response.order;
                let itemsHtml = '';
                
                // Regular Items (Drinks)
                if (order.items && order.items.length > 0) {
                    itemsHtml += `<h6 class="text-primary font-weight-bold"><i class="fa fa-glass"></i> Drinks/Beverages</h6>
                                  <table class="table table-sm table-striped">
                                    <thead class="bg-light"><tr><th>Item</th><th class="text-center">Qty</th><th class="text-right">Total</th></tr></thead><tbody>`;
                    order.items.forEach(item => {
                        itemsHtml += `<tr><td>${item.product_name} <small class="text-muted">(${item.variant})</small></td><td class="text-center">${item.quantity}</td><td class="text-right">TSh ${parseInt(item.total_price).toLocaleString()}</td></tr>`;
                    });
                    itemsHtml += '</tbody></table>';
                }

                // Food Items (Kitchen Items)
                const foodItems = order.kitchen_order_items || order.food_items || [];
                if (foodItems.length > 0) {
                    itemsHtml += `<h6 class="mt-3 text-info font-weight-bold"><i class="fa fa-cutlery"></i> Food Items</h6>
                                  <table class="table table-sm table-striped">
                                    <thead class="bg-light"><tr><th>Item</th><th class="text-center">Qty</th><th class="text-right">Total</th></tr></thead><tbody>`;
                    foodItems.forEach(item => {
                        const name = item.food_item_name || item.name || 'Food Item';
                        const variant = item.variant_name || item.variant || '';
                        const quantity = item.quantity;
                        const price = item.total_price || (item.price * quantity);
                        itemsHtml += `<tr><td>${name} <small class="text-muted">${variant ? '('+variant+')' : ''}</small></td><td class="text-center">${quantity}</td><td class="text-right">TSh ${parseInt(price).toLocaleString()}</td></tr>`;
                    });
                    itemsHtml += '</tbody></table>';
                }

                const content = `
                    <div class="p-2">
                        <div class="row mb-3 pb-3 border-bottom no-gutters">
                            <div class="col-6">
                                <span class="d-block small text-muted text-uppercase">Order Details</span>
                                <h4 class="mb-1 font-weight-bold text-primary">#${order.order_number}</h4>
                                <div class="d-flex flex-wrap">
                                    <span class="badge badge-primary mr-1 mb-1 shadow-sm"><i class="fa fa-table"></i> ${order.table ? 'Table ' + order.table.table_number : 'Walk-in'}</span>
                                    <span class="badge badge-info mr-1 mb-1 shadow-sm"><i class="fa fa-user-circle"></i> ${order.waiter ? order.waiter.full_name : 'Counter Staff'}</span>
                                    ${order.customer_name ? `<span class="badge badge-dark mb-1 shadow-sm"><i class="fa fa-id-card"></i> Guest: ${order.customer_name}</span>` : ''}
                                </div>
                            </div>
                            <div class="col-6 text-right">
                                <span class="d-block small text-muted text-uppercase">Status & Contact</span>
                                <p class="mb-1 font-weight-bold small">${order.created_at}</p>
                                <div class="mb-1">
                                    <span class="badge badge-${order.status === 'served' ? 'success' : 'warning'} px-2">${order.status.toUpperCase()}</span>
                                    <span class="badge badge-${order.payment_status === 'paid' ? 'success' : 'danger'} px-2">${order.payment_status.toUpperCase()}</span>
                                </div>
                                ${order.customer_phone ? `<span class="text-info small font-weight-bold d-block"><i class="fa fa-phone"></i> ${order.customer_phone}</span>` : ''}
                            </div>
                        </div>
                        
                        ${itemsHtml}
                        
                        <div class="mt-4 p-3 bg-light rounded text-right border">
                            <span class="d-block small text-muted text-uppercase">Total Amount Due</span>
                            <h3 class="text-primary font-weight-bold mb-0">TSh ${parseInt(order.total_amount).toLocaleString()}</h3>
                        </div>
                        
                        ${order.notes ? `<div class="mt-3 p-3 bg-light border border-info rounded-sm small shadow-sm">
                            <strong class="text-info"><i class="fa fa-sticky-note"></i> Order Notes:</strong><br>${order.notes.replace(/\|/g, '<br>')}
                        </div>` : ''}
                    </div>
                `;
                $('#orderDetailsContent').html(content);
            },
            error: function() {
                $('#orderDetailsContent').html('<div class="alert alert-danger m-3">Failed to load order details.</div>');
            }
        });
    });
    // Check for "Add Items" redirect from Orders page
    const urlParams = new URLSearchParams(window.location.search);
    const orderToAdd = urlParams.get('add_item_to_order');
    if (orderToAdd) {
        // High-end Professional Transition
        $('#pos-loader-overlay').css('display', 'flex'); 
        $('#dashboard-content').css('opacity', '0');
        
        setTimeout(() => {
            $('#dashboard-content').hide();
            $('#pos-existing-order-id').val(orderToAdd);
            $('#pos-appending-order-num').text('ORDER #' + orderToAdd); 
            $('#pos-mode-indicator').show();
            
            $('#pos-section').show().css('opacity', '1');
            renderCart();
            
            // Fade out the professional loader
            $('#pos-loader-overlay').css('opacity', '0');
            setTimeout(() => {
                $('#pos-loader-overlay').hide();
                showToast('success', 'POS ready for Order #' + orderToAdd, 'Redirected');
            }, 400);
        }, 800);
        
        // Clear param from URL without reload
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.pushState({path:newUrl},'',newUrl);
    }
    // Pagination Logic for Recent Orders
    let currentRecentPage = 1;
    const itemsPerPage = 5;
    const $recentRows = $('#orders-table-recent tbody tr');
    const totalRecentPages = Math.ceil($recentRows.length / itemsPerPage);

    function showRecentPage(page) {
        $recentRows.hide();
        $recentRows.slice((page - 1) * itemsPerPage, page * itemsPerPage).show();
        $('#recent-orders-page-info').text(`Page ${page} of ${totalRecentPages}`);
        $('#prev-recent-orders').prop('disabled', page === 1);
        $('#next-recent-orders').prop('disabled', page === totalRecentPages);
    }

    $('#prev-recent-orders').on('click', function() {
        if (currentRecentPage > 1) {
            currentRecentPage--;
            showRecentPage(currentRecentPage);
        }
    });

    $('#next-recent-orders').on('click', function() {
        if (currentRecentPage < totalRecentPages) {
            currentRecentPage++;
            showRecentPage(currentRecentPage);
        }
    });

    // Initialize pagination
    if (totalRecentPages > 0) {
        showRecentPage(1);
    } else {
        $('#recent-orders-pagination').hide();
    }

    // Shift Timer Logic
    const timerEl = document.getElementById('shift-timer-text');
    const shiftCounterEl = document.getElementById('shift-realtime-counter');
    if(timerEl && shiftCounterEl) {
        const openedAtStr = shiftCounterEl.getAttribute('data-opened-at');
        if (openedAtStr) {
            const openedAt = new Date(openedAtStr);
            function updateTimer() {
                const now = new Date();
                const diffMs = now - openedAt;
                if (diffMs > 0) {
                    const hours = Math.floor(diffMs / 3600000);
                    const minutes = Math.floor((diffMs % 3600000) / 60000);
                    const seconds = Math.floor((diffMs % 60000) / 1000);
                    
                    timerEl.textContent = 
                        String(hours).padStart(2, '0') + ':' + 
                        String(minutes).padStart(2, '0') + ':' + 
                        String(seconds).padStart(2, '0');
                }
            }
            updateTimer();
            setInterval(updateTimer, 1000);
        }
    }
});
</script>
@endpush
