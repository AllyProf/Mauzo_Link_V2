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

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-4">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-archive fa-3x"></i>
        <div class="info">
          <h4>Total Items</h4>
          <p><b>{{ number_format($totalWarehouseStock) }} btl</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="widget-small info coloured-icon">
        <i class="icon fa fa-cubes fa-3x"></i>
        <div class="info">
          <h4>Variants</h4>
          <p><b>{{ $warehouseStock->count() }} Variants</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="widget-small danger coloured-icon">
        <i class="icon fa fa-exclamation-triangle fa-3x"></i>
        <div class="info">
          <h4>Low Stock</h4>
          <p><b>{{ $warehouseStock->where('is_low_stock', true)->count() }}</b></p>
        </div>
      </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="tile-title">Warehouse Inventory</h3>
                <div class="d-flex align-items-center">
                    <div class="btn-group mr-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary active" id="view-table" onclick="toggleView('table')" title="Table View">
                            <i class="fa fa-table"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="view-card" onclick="toggleView('card')" title="Card View">
                            <i class="fa fa-th-large"></i>
                        </button>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary active" id="tab-all" onclick="switchTab('all')">All Items</button>
                        <button type="button" class="btn btn-outline-danger" id="tab-low" onclick="switchTab('low')">Low Stock</button>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="text" id="warehouseSearch" class="form-control" placeholder="Search product or brand...">
                    </div>
                </div>
                <div class="col-md-8">
                    <ul class="nav nav-pills wh-category-tabs" id="categoryTabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-category="all">All Categories</a>
                        </li>
                        @foreach($categories as $cat)
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-category="{{ \Str::slug($cat) }}">{{ ucfirst($cat) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="tile-body">
                <!-- Table View -->
                <div class="table-responsive" id="tableView">
                    <table class="table table-hover table-bordered" id="warehouseTable">
                        <thead>
                            <tr style="background: #f4f4f4;">
                                <th>Product Details</th>
                                <th>Category</th>
                                <th class="text-center">Stock Level</th>
                                <th class="text-center">Packages</th>
                                @if($showRevenue)
                                <th class="text-right">Valuation</th>
                                @endif
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouseStock as $item)
                            <tr class="warehouse-row" 
                                data-category="{{ \Str::slug($item['category']) }}" 
                                data-is-low="{{ $item['is_low_stock'] ? 'true' : 'false' }}"
                                data-search="{{ strtolower($item['display_title'] . ' ' . $item['product_name']) }}">
                                <td>
                                    <strong>{{ $item['display_title'] }}</strong><br>
                                    <small class="text-muted">{{ $item['variant'] }}</small>
                                </td>
                                <td>{{ $item['category'] }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $item['is_low_stock'] ? 'danger' : 'success' }}" style="font-size: 14px; padding: 5px 10px;">
                                        {{ number_format($item['quantity']) }} btl
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong>{{ $item['packages'] }}</strong> {{ $item['packaging_type'] }}
                                </td>
                                @if($showRevenue)
                                <td class="text-right">
                                    <small class="text-muted">TSh {{ number_format($item['buying_price']) }} / btl</small><br>
                                    <strong>TSh {{ number_format($item['value']) }}</strong>
                                </td>
                                @endif
                                <td class="text-right">
                                    <button class="btn btn-sm btn-info" onclick="openQuickAction({{ $item['variant_id'] }}, '{{ addslashes($item['display_title']) }}')">
                                        <i class="fa fa-bolt"></i> Logistics
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Card View -->
                <div id="cardView" style="display: none;">
                    <div class="row">
                        @foreach($warehouseStock as $item)
                        <div class="col-md-4 mb-3 warehouse-card" 
                             data-category="{{ \Str::slug($item['category']) }}" 
                             data-is-low="{{ $item['is_low_stock'] ? 'true' : 'false' }}"
                             data-search="{{ strtolower($item['display_title'] . ' ' . $item['product_name']) }}">
                            <div class="card h-100 {{ $item['is_low_stock'] ? 'border-danger' : 'border-light' }}">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="font-weight-bold mb-1">{{ $item['display_title'] }}</h6>
                                        <span class="badge badge-{{ $item['is_low_stock'] ? 'danger' : 'success' }}">
                                            {{ number_format($item['quantity']) }} btl
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-2">{{ $item['variant'] }} • {{ $item['category'] }}</p>
                                    <div class="bg-light p-2 rounded mb-2">
                                        <div class="d-flex justify-content-between small">
                                            <span>Pkg Type:</span>
                                            <strong>{{ $item['packaging_type'] }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between small">
                                            <span>Total Pkgs:</span>
                                            <strong>{{ $item['packages'] }}</strong>
                                        </div>
                                    </div>
                                    @if($showRevenue)
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="small text-muted">Valuation:</span>
                                        <strong class="text-dark">TSh {{ number_format($item['value']) }}</strong>
                                    </div>
                                    @endif
                                    <button class="btn btn-sm btn-block btn-outline-info" onclick="openQuickAction({{ $item['variant_id'] }}, '{{ addslashes($item['display_title']) }}')">
                                        <i class="fa fa-bolt"></i> Manage Logistics
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Side Action Drawer (Overlay Style) --}}
<div id="quickActionDrawer" class="pos-side-drawer shadow">
    <div class="drawer-header p-3 d-flex justify-content-between align-items-center" style="background: #222; color: #fff;">
        <h5 class="mb-0"><i class="fa fa-bolt"></i> Quick Logistics</h5>
        <button type="button" class="close text-white" onclick="closeQuickAction()">&times;</button>
    </div>
    <div class="drawer-body p-4">
        <div class="mb-4">
            <h5 id="drawer-title" class="font-weight-bold mb-1">Product Name</h5>
            <p class="text-muted small">Select an action for this warehouse item</p>
        </div>
        
        <div class="list-group">
            <a href="#" id="transfer-link" class="list-group-item list-group-item-action p-3 mb-2" style="border-left: 4px solid #007bff;">
                <div class="d-flex align-items-center">
                    <i class="fa fa-exchange fa-2x mr-3 text-primary"></i>
                    <div>
                        <h6 class="mb-0 font-weight-bold">Transfer to Counter</h6>
                        <small class="text-muted">Move stock from warehouse to bar</small>
                    </div>
                </div>
            </a>
            <a href="#" id="restock-link" class="list-group-item list-group-item-action p-3" style="border-left: 4px solid #28a745;">
                <div class="d-flex align-items-center">
                    <i class="fa fa-plus-circle fa-2x mr-3 text-success"></i>
                    <div>
                        <h6 class="mb-0 font-weight-bold">Restock Batch</h6>
                        <small class="text-muted">Receive new stock for this item</small>
                    </div>
                </div>
            </a>
        </div>

        <div class="alert alert-info mt-4 small">
            <i class="fa fa-info-circle"></i> Use these shortcuts to quickly initiate stock movements. The details will be auto-loaded for you.
        </div>
    </div>
</div>
<div id="drawerOverlay" class="drawer-overlay" onclick="closeQuickAction()"></div>

<style>
    /* Side Drawer Styling - Aligned with Vali Template */
    .pos-side-drawer {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100%;
        background: #fff;
        z-index: 1050;
        transition: right 0.3s ease-in-out;
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
    }
    .pos-side-drawer.open {
        right: 0;
    }
    .drawer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
        visibility: hidden;
        opacity: 0;
        transition: 0.3s;
    }
    .drawer-overlay.active {
        visibility: visible;
        opacity: 1;
    }
    .wh-category-tabs .nav-link {
        font-weight: 600;
        color: #666;
        margin-right: 5px;
    }
    .wh-category-tabs .nav-link.active {
        background-color: #009688;
        color: #fff;
    }
</style>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Live Search
    $('#warehouseSearch').on('keyup', function() {
        filterTable();
    });

    // Category Tabs
    $('#categoryTabs .nav-link').on('click', function(e) {
        e.preventDefault();
        $('#categoryTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        filterTable();
    });
});

function switchTab(tab) {
    $('#tab-all, #tab-low').removeClass('active btn-primary btn-danger').addClass('btn-outline-primary btn-outline-danger');
    if (tab === 'all') {
        $('#tab-all').removeClass('btn-outline-primary').addClass('active btn-primary');
    } else {
        $('#tab-low').removeClass('btn-outline-danger').addClass('active btn-danger');
    }
    filterTable();
}

function toggleView(view) {
    $('#view-table, #view-card').removeClass('active');
    if (view === 'table') {
        $('#view-table').addClass('active');
        $('#tableView').fadeIn();
        $('#cardView').hide();
    } else {
        $('#view-card').addClass('active');
        $('#cardView').fadeIn();
        $('#tableView').hide();
    }
}

function filterTable() {
    const searchTerm = $('#warehouseSearch').val().toLowerCase();
    const activeCategory = $('#categoryTabs .nav-link.active').data('category');
    const showLowStock = $('#tab-low').hasClass('active');

    $('.warehouse-row, .warehouse-card').each(function() {
        const item = $(this);
        const text = item.data('search');
        const category = item.data('category');
        const isLow = item.data('is-low') === 'true';

        const matchesSearch = text.includes(searchTerm);
        const matchesCategory = activeCategory === 'all' || category === activeCategory;
        const matchesStock = !showLowStock || isLow;

        if (matchesSearch && matchesCategory && matchesStock) {
            item.show();
        } else {
            item.hide();
        }
    });
}

function openQuickAction(id, title) {
    $('#drawer-title').text(title);
    $('#transfer-link').attr('href', "{{ route('bar.stock-transfers.create') }}?auto_load_variant=" + id);
    $('#restock-link').attr('href', "{{ route('bar.stock-receipts.create') }}?auto_load_variant=" + id);
    
    $('#quickActionDrawer').addClass('open');
    $('#drawerOverlay').addClass('active');
}

function closeQuickAction() {
    $('#quickActionDrawer').removeClass('open');
    $('#drawerOverlay').removeClass('active');
}
</script>
@endsection
