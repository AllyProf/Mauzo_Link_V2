@extends('layouts.dashboard')

@section('title', 'Warehouse Stock')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-archive"></i> Warehouse Stock</h1>
    <p>View and manage all stock available in warehouse</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.beverage-inventory.index') }}">Beverage Inventory</a></li>
    <li class="breadcrumb-item">Warehouse Stock</li>
  </ul>
</div>

{{-- Statistics Cards --}}
<div class="row">
  <div class="col-md-4">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-archive fa-3x"></i>
      <div class="info">
        <h4>Total Warehouse Stock</h4>
        <p><b>{{ number_format($totalWarehouseStock) }} bottle(s)</b></p>
        <small>TSh {{ number_format($totalWarehouseValue, 2) }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-cubes fa-3x"></i>
      <div class="info">
        <h4>Total Items</h4>
        <p><b>{{ $warehouseStock->count() }} variants</b></p>
        <small>{{ $productsWithWarehouseStock->count() }} products</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-exclamation-triangle fa-3x"></i>
      <div class="info">
        <h4>Low Stock Items</h4>
        <p><b>{{ $warehouseStock->where('is_low_stock', true)->count() }}</b></p>
        <small>Need attention</small>
      </div>
    </div>
  </div>
</div>

{{-- Quick Actions --}}
<div class="row mt-3">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="tile-title"><i class="fa fa-bolt"></i> Quick Actions</h3>
      </div>
      <div class="tile-body">
        <div class="btn-group flex-wrap" role="group">
          <a href="{{ route('bar.stock-receipts.create') }}" class="btn btn-primary">
            <i class="fa fa-plus-circle"></i> Receive New Stock
          </a>
          <a href="{{ route('bar.stock-transfers.create') }}" class="btn btn-success">
            <i class="fa fa-exchange"></i> Transfer to Counter
          </a>
          <a href="{{ route('bar.beverage-inventory.index') }}" class="btn btn-info">
            <i class="fa fa-eye"></i> View Full Inventory
          </a>
          <a href="{{ route('bar.beverage-inventory.low-stock-alerts') }}" class="btn btn-warning">
            <i class="fa fa-exclamation-triangle"></i> Low Stock Alerts
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Warehouse Stock Cards --}}
<div class="row mt-3">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title"><i class="fa fa-archive"></i> Warehouse Inventory</h3>
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-sm btn-primary active" id="tab-all" onclick="switchTab('all')">
            <i class="fa fa-list"></i> All Stock
          </button>
          <button type="button" class="btn btn-sm btn-outline-warning" id="tab-low" onclick="switchTab('low')">
            <i class="fa fa-exclamation-triangle"></i> Low Stock
          </button>
        </div>
      </div>

      <div class="tile-body">
        @if($warehouseStock->count() > 0)

          {{-- Category Tabs --}}
          <ul class="nav wh-category-tabs mb-4" id="categoryTabs">
            <li class="nav-item">
              <a class="nav-link active" href="#" data-category="all">
                <i class="fa fa-th"></i> All
                <span class="badge badge-secondary ml-1">{{ $warehouseStock->count() }}</span>
              </a>
            </li>
            @foreach($categories as $cat)
              @php
                $catSlug  = \Illuminate\Support\Str::slug($cat);
                $catCount = $warehouseStock->where('category', $cat)->count();
              @endphp
              <li class="nav-item">
                <a class="nav-link" href="#" data-category="{{ $catSlug }}">
                  {{ ucfirst($cat) }}
                  <span class="badge badge-secondary ml-1">{{ $catCount }}</span>
                </a>
              </li>
            @endforeach
          </ul>

          {{-- Product Cards Grid --}}
          <div class="row" id="warehouseContainer">
            @foreach($warehouseStock as $stock)
            @php
              $catSlug     = \Illuminate\Support\Str::slug($stock['category']);
              $isLow       = $stock['is_low_stock'];
              $qty         = $stock['quantity'];
              $ipp         = $stock['items_per_package'];
              $pkgLabel    = ucfirst($stock['packaging_type']);
              $crates      = $stock['packages'];
              $extraBottles= $stock['extra_bottles'];
              $borderColor = $isLow ? 'border-warning' : 'border-success';
              $headerClass = $isLow ? 'bg-warning text-dark' : 'bg-success text-white';
              $borderHex   = $isLow ? '#ffc107' : '#28a745';
              $badgeColor  = $isLow ? 'warning' : 'success';
            @endphp
            <div class="col-6 col-md-4 col-lg-3 mb-3 warehouse-card"
                 data-is-low="{{ $isLow ? 'true' : 'false' }}"
                 data-category="{{ $catSlug }}">
              <div class="card h-100 shadow-sm {{ $borderColor }}" style="border-width:2px;">

                {{-- Product Image --}}
                <div style="height:120px; overflow:hidden; background:#f8f9fa; display:flex; align-items:center; justify-content:center; border-bottom:2px solid {{ $borderHex }}; position:relative;">
                  @if($stock['product_image'])
                    <img src="{{ asset('storage/' . $stock['product_image']) }}"
                         alt="{{ $stock['product_name'] }}"
                         style="max-height:120px; width:100%; object-fit:contain; padding:6px;"
                         onerror="this.src='{{ asset('default_images/default_drink.jpg') }}'">
                  @else
                    <img src="{{ asset('default_images/default_drink.jpg') }}"
                         alt="Default"
                         style="max-height:120px; width:100%; object-fit:contain; padding:6px;">
                  @endif
                  <span class="wh-cat-badge">{{ $stock['category'] }}</span>
                  @if($isLow)
                    <span class="wh-low-badge"><i class="fa fa-exclamation-triangle"></i> Low</span>
                  @endif
                </div>

                {{-- Card Header --}}
                <div class="card-header {{ $headerClass }} p-2" style="border-bottom:none;">
                  <h6 class="card-title mb-0 font-weight-bold"
                      title="{{ $stock['display_title'] }} — {{ $stock['measurement'] }}"
                      style="font-size:0.8rem; line-height:1.3; word-break:break-word;">
                    {{ $stock['display_title'] }}
                    @if($isLow)
                      <i class="fa fa-exclamation-triangle float-right mt-1"></i>
                    @else
                      <i class="fa fa-check-circle float-right mt-1"></i>
                    @endif
                  </h6>
                  <small style="font-size:9px; opacity:0.8;">
                    <i class="fa fa-flask"></i> {{ $stock['measurement'] }}
                  </small>
                </div>

                {{-- Card Body --}}
                <div class="card-body p-2">

                  {{-- Quantity --}}
                  <div class="qty-box mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted"><i class="fa fa-cubes"></i> Qty:</small>
                      <span class="badge badge-{{ $badgeColor }}" style="font-size:0.78rem;">
                        {{ number_format($qty) }} btl
                      </span>
                    </div>
                    @if($ipp > 1 && $qty > 0)
                    <div class="text-right mt-1">
                      <small class="text-muted" style="font-size:10px;">
                        @if($crates > 0)
                          <b class="text-dark">{{ $crates }}</b> {{ $pkgLabel }}{{ $crates != 1 ? 's' : '' }}
                          @if($extraBottles > 0) + <b class="text-dark">{{ $extraBottles }}</b> btl @endif
                        @else
                          <b class="text-dark">{{ $extraBottles }}</b> btl
                        @endif
                      </small>
                    </div>
                    @endif
                  </div>

                  {{-- Pricing --}}
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted"><i class="fa fa-download"></i> Buy:</small>
                    <small class="font-weight-bold text-dark">TSh {{ number_format($stock['buying_price']) }}</small>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted"><i class="fa fa-tag"></i> Sell:</small>
                    <small class="font-weight-bold text-primary">TSh {{ number_format($stock['selling_price']) }}</small>
                  </div>
                  @if($stock['can_sell_in_tots'] && $stock['selling_price_per_tot'] > 0)
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted"><i class="fa fa-glass"></i> Tot:</small>
                    <small class="font-weight-bold text-info">TSh {{ number_format($stock['selling_price_per_tot']) }}</small>
                  </div>
                  @endif

                  <hr class="my-1">

                  {{-- Total Cost --}}
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted"><i class="fa fa-shopping-cart"></i> Total Cost:</small>
                    <small class="font-weight-bold text-dark">TSh {{ number_format($stock['value']) }}</small>
                  </div>

                  {{-- Bottle channel revenue/profit --}}
                  <div class="channel-row {{ $stock['best_channel'] === 'bottle' ? 'best-channel' : '' }} mb-1">
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted">
                        <i class="fa fa-bottle-o fa-fw"></i>
                        Bottle Rev:
                        @if($stock['best_channel'] === 'bottle')
                          <span class="badge badge-success" style="font-size:8px;">Best</span>
                        @endif
                      </small>
                      <small class="font-weight-bold text-info">TSh {{ number_format($stock['bottle_revenue']) }}</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted" style="padding-left:16px;">Profit:</small>
                      <small class="font-weight-bold {{ $stock['bottle_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        TSh {{ number_format($stock['bottle_profit']) }}
                      </small>
                    </div>
                  </div>

                  {{-- Tot/Glass channel revenue/profit --}}
                  @if($stock['can_sell_in_tots'])
                  <div class="channel-row {{ $stock['best_channel'] === 'tot' ? 'best-channel' : '' }} mb-1">
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted">
                        <i class="fa fa-glass fa-fw"></i>
                        Glass Rev:
                        @if($stock['best_channel'] === 'tot')
                          <span class="badge badge-success" style="font-size:8px;">Best</span>
                        @endif
                      </small>
                      <small class="font-weight-bold text-info">TSh {{ number_format($stock['tot_revenue']) }}</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted" style="padding-left:16px;">Profit:</small>
                      <small class="font-weight-bold {{ $stock['tot_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        TSh {{ number_format($stock['tot_profit']) }}
                      </small>
                    </div>
                    <div class="text-right">
                      <small class="text-muted" style="font-size:9px;">
                        ({{ $qty * $stock['total_tots_per_bottle'] }} glasses × TSh {{ number_format($stock['selling_price_per_tot']) }})
                      </small>
                    </div>
                  </div>
                  @endif

                </div>

                {{-- Card Footer --}}
                <div class="card-footer p-1 text-center" style="border-top:2px solid {{ $borderHex }}; background:#fafafa;">
                  <a href="{{ route('bar.products.show', $stock['product_id']) }}"
                     class="btn btn-xs btn-outline-info px-2 py-1" style="font-size:11px;">
                    <i class="fa fa-eye"></i> View
                  </a>
                  <a href="{{ route('bar.stock-transfers.create') }}"
                     class="btn btn-xs btn-outline-success px-2 py-1" style="font-size:11px;">
                    <i class="fa fa-exchange"></i> Transfer
                  </a>
                </div>

              </div>
            </div>
            @endforeach
          </div>

          {{-- Total Bar --}}
          <div class="mt-3 p-3 rounded d-flex justify-content-between align-items-center"
               style="background: linear-gradient(135deg, #1a237e, #283593); color:white;">
            <h5 class="mb-0"><i class="fa fa-calculator"></i> Total Warehouse Value</h5>
            <h4 class="mb-0 text-success font-weight-bold">TSh {{ number_format($totalWarehouseValue, 2) }}</h4>
          </div>

        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No stock available in warehouse.
            <a href="{{ route('bar.stock-receipts.create') }}">Receive new stock</a> to get started.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Products Summary --}}
@if($productsWithWarehouseStock->count() > 0)
<div class="row mt-3">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title"><i class="fa fa-cube"></i> Products Summary</h3>
      </div>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-sm table-bordered align-middle mb-0">
            <thead class="thead-dark">
              <tr>
                <th>Product</th>
                <th class="text-center">Variants</th>
                <th class="text-center">Total Bottles</th>
                <th class="text-right">Total Cost Value</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($productsWithWarehouseStock as $item)
              @php
                $variantCount = $warehouseStock->where('product_id', $item['product']->id)->count();
              @endphp
              <tr>
                <td>
                  <div class="font-weight-bold">{{ $item['product']->name }}</div>
                  <small class="text-muted">{{ $item['product']->category ?? 'General' }}</small>
                </td>
                <td class="text-center">
                  <span class="badge badge-info">{{ $variantCount }}</span>
                </td>
                <td class="text-center">
                  <span class="font-weight-bold">{{ number_format($item['total_quantity']) }}</span>
                </td>
                <td class="text-right">
                  <span class="text-success font-weight-bold">TSh {{ number_format($item['total_value'], 2) }}</span>
                </td>
                <td class="text-center">
                  <a href="{{ route('bar.products.show', $item['product']->id) }}"
                     class="btn btn-sm btn-info">
                    <i class="fa fa-eye"></i>
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
            <tfoot class="table-dark">
              <tr>
                <th colspan="2">Totals</th>
                <th class="text-center">{{ number_format($totalWarehouseStock) }} btl</th>
                <th class="text-right">TSh {{ number_format($totalWarehouseValue, 2) }}</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

@endsection

@push('styles')
<style>
  /* Category tabs */
  .wh-category-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 12px;
  }
  .wh-category-tabs .nav-link {
    border-radius: 20px;
    color: #555;
    background: #f0f2f5;
    border: 1px solid #ddd;
    padding: 5px 14px;
    font-size: 0.82rem;
    font-weight: 600;
    transition: all 0.2s;
  }
  .wh-category-tabs .nav-link:hover {
    background: #e3f2fd;
    border-color: #90caf9;
    color: #1565c0;
  }
  .wh-category-tabs .nav-link.active {
    background: #1565c0;
    color: white !important;
    border-color: #1565c0;
  }

  /* Image overlay badges */
  .wh-cat-badge {
    position: absolute;
    top: 5px;
    left: 5px;
    background: rgba(0,0,0,0.45);
    color: white;
    font-size: 8px;
    padding: 2px 6px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    pointer-events: none;
  }
  .wh-low-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #e65100;
    color: white;
    font-size: 8px;
    padding: 2px 6px;
    border-radius: 10px;
    pointer-events: none;
  }

  /* Quantity box */
  .qty-box {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 4px 6px;
  }

  /* Card hover */
  .warehouse-card .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .warehouse-card .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12) !important;
  }

  /* Active tab button */
  .btn-group .btn.active {
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }

  /* Table summary */
  .table td, .table th { vertical-align: middle !important; }

  /* Channel revenue rows */
  .channel-row {
    background: #f8f9fa;
    border-radius: 5px;
    padding: 3px 5px;
    border-left: 2px solid #dee2e6;
  }
  .channel-row.best-channel {
    background: #e8f5e9;
    border-left: 2px solid #28a745;
  }

  /* Animation */
  .warehouse-card { animation: fadeIn 0.25s ease; }
  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.97); }
    to   { opacity: 1; transform: scale(1); }
  }
</style>
@endpush

@section('scripts')
<script>
$(document).ready(function () {

  // Category tabs filter
  $('#categoryTabs .nav-link').on('click', function (e) {
    e.preventDefault();
    var cat = $(this).data('category');
    $('#categoryTabs .nav-link').removeClass('active');
    $(this).addClass('active');
    if (cat === 'all') {
      $('.warehouse-card').fadeIn(200);
    } else {
      $('.warehouse-card').hide();
      $('.warehouse-card[data-category="' + cat + '"]').fadeIn(200);
    }
  });

});

// All Stock / Low Stock tab toggle
function switchTab(tab) {
  $('#tab-all, #tab-low').removeClass('active btn-primary btn-warning');
  $('#tab-all').addClass('btn-outline-primary');
  $('#tab-low').addClass('btn-outline-warning');

  if (tab === 'all') {
    $('#tab-all').removeClass('btn-outline-primary').addClass('active btn-primary');
    $('#categoryTabs .nav-link[data-category="all"]').trigger('click');
    $('.warehouse-card').show();
  } else {
    $('#tab-low').removeClass('btn-outline-warning').addClass('active btn-warning');
    // Reset category tab to all
    $('#categoryTabs .nav-link').removeClass('active');
    $('#categoryTabs .nav-link[data-category="all"]').addClass('active');
    $('.warehouse-card').each(function () {
      $(this).toggle($(this).data('is-low') === 'true');
    });
  }
}
</script>
@endsection
