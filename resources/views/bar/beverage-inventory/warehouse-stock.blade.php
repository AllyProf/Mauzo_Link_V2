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
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title"><i class="fa fa-bolt"></i> Quick Actions</h3>
      </div>
      <div class="tile-body">
        <div class="btn-group" role="group">
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

{{-- Warehouse Stock Cards with Tabs --}}
<div class="row mt-4">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">
          <i class="fa fa-archive"></i> Warehouse Stock
        </h3>
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
          {{-- All Stock Cards --}}
          <div id="cards-all" class="cards-container">
            <div class="row">
              @foreach($warehouseStock as $stock)
                <div class="col-md-4 mb-3 warehouse-card" 
                     data-is-low="{{ $stock['is_low_stock'] ? 'true' : 'false' }}">
                  <div class="card h-100 {{ $stock['is_low_stock'] ? 'border-warning' : 'border-success' }}">
                    @if(isset($stock['product_image']) && $stock['product_image'])
                      <img src="{{ asset('storage/' . $stock['product_image']) }}" class="card-img-top" alt="{{ $stock['product_name'] }}" style="height: 150px; object-fit: cover;">
                    @else
                      <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                        <i class="fa fa-cube fa-3x text-muted"></i>
                      </div>
                    @endif
                    <div class="card-header {{ $stock['is_low_stock'] ? 'bg-warning' : 'bg-success text-white' }}">
                      <h5 class="card-title mb-0">
                        <strong>{{ $stock['product_name'] }}</strong>
                        @if($stock['is_low_stock'])
                          <span class="badge badge-warning float-right">Low Stock</span>
                        @else
                          <span class="badge badge-light float-right">In Stock</span>
                        @endif
                      </h5>
                    </div>
                    <div class="card-body">
                      <div class="p-3 bg-primary text-white rounded mb-3">
                        <small class="d-block text-center mb-2"><strong>{{ $stock['variant'] }}</strong></small>
                        <div class="row text-center">
                          <div class="col-6 border-right border-white">
                            @php
                              $packagingCount = $stock['packages'];
                              $packagingType = strtolower($stock['packaging_type']);
                              $itemsPerPackage = $stock['items_per_package'];
                              // Handle singular/plural for display
                              $packagingTypeSingular = $packagingType;
                              if ($packagingCount == 1) {
                                // Remove 's' from end if plural (e.g., crates -> crate, boxes -> box)
                                $packagingTypeSingular = rtrim($packagingType, 's');
                                // Fix special cases
                                if ($packagingTypeSingular == 'boxe') {
                                  $packagingTypeSingular = 'box';
                                }
                              }
                            @endphp
                            @if($packagingCount > 0 && $itemsPerPackage > 1)
                              <div class="h3 mb-0">{{ number_format($packagingCount) }}</div>
                              <small>{{ $packagingCount == 1 ? $packagingTypeSingular : $packagingType }}</small>
                              <div class="mt-1">
                                <small>({{ number_format($itemsPerPackage) }} bottles per {{ $packagingTypeSingular }})</small>
                              </div>
                            @elseif($packagingCount > 0)
                              <div class="h3 mb-0">{{ number_format($packagingCount) }}</div>
                              <small>{{ $packagingType }}</small>
                            @endif
                          </div>
                          <div class="col-6">
                            <div class="h3 mb-0">{{ number_format($stock['quantity']) }}</div>
                            <small>bottle(s)</small>
                          </div>
                        </div>
                      </div>
                      
                      <div class="row text-center mb-2">
                        <div class="col-6">
                          <small class="text-muted">Buying Price</small>
                          <div class="h6">TSh {{ number_format($stock['buying_price'], 2) }}</div>
                        </div>
                        <div class="col-6">
                          <small class="text-muted">Selling Price</small>
                          <div class="h6">TSh {{ number_format($stock['selling_price'], 2) }}</div>
                        </div>
                      </div>
                      
                      <hr>
                      
                      <div class="row text-center mb-2">
                        <div class="col-6">
                          <small class="text-muted"><strong>Total Cost Bought</strong></small>
                          <div class="h5 text-primary mb-0">TSh {{ number_format($stock['value'], 2) }}</div>
                        </div>
                        <div class="col-6">
                          <small class="text-muted"><strong>Total Revenue</strong></small>
                          <div class="h5 text-info mb-0">TSh {{ number_format($stock['total_cost_sold'], 2) }}</div>
                        </div>
                      </div>
                      
                      <hr>
                      
                      <div class="text-center">
                        <small class="text-muted"><strong>Expected Profit</strong></small>
                        <div class="h4 text-success mb-1">TSh {{ number_format($stock['expected_profit'], 2) }}</div>
                        @if($stock['total_cost_sold'] > 0)
                          @php
                            $profitMargin = ($stock['expected_profit'] / $stock['total_cost_sold']) * 100;
                          @endphp
                          <small class="text-muted">
                            {{ number_format($profitMargin, 1) }}% profit margin
                          </small>
                        @endif
                      </div>
                      
                      <div class="mt-3">
                        <a href="{{ route('bar.products.show', $stock['product_id']) }}" class="btn btn-sm btn-info btn-block" title="View Product Details">
                          <i class="fa fa-eye"></i> View Details
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
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
<div class="row mt-4">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">
          <i class="fa fa-cube"></i> Products Summary
        </h3>
      </div>
      <div class="tile-body">
        <div class="row">
          @foreach($productsWithWarehouseStock as $item)
            <div class="col-md-4 mb-3">
              <div class="card">
                <div class="card-header bg-info text-white">
                  <h5 class="card-title mb-0">{{ $item['product']->name }}</h5>
                </div>
                <div class="card-body">
                  <div class="text-center mb-3">
                    <div class="h4 text-primary">{{ number_format($item['total_quantity']) }}</div>
                    <small class="text-muted">Total Bottle(s)</small>
                  </div>
                  <div class="text-center mb-3">
                    <div class="h5 text-success">TSh {{ number_format($item['total_value'], 2) }}</div>
                    <small class="text-muted">Total Value</small>
                  </div>
                  <div class="text-center">
                    @php
                      $variantCount = $warehouseStock->where('product_id', $item['product']->id)->count();
                    @endphp
                    <span class="badge badge-info">{{ $variantCount }} variant(s)</span>
                  </div>
                  <div class="mt-3">
                    <a href="{{ route('bar.products.show', $item['product']->id) }}" class="btn btn-sm btn-info btn-block">
                      <i class="fa fa-eye"></i> View Details
                    </a>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script type="text/javascript">
  function switchTab(tab) {
    // Update button states
    $('#tab-all, #tab-low').removeClass('active btn-primary btn-warning');
    $('#tab-all').addClass('btn-outline-primary');
    $('#tab-low').addClass('btn-outline-warning');
    
    if (tab === 'all') {
      $('#tab-all').removeClass('btn-outline-primary').addClass('active btn-primary');
      $('.warehouse-card').show();
    } else if (tab === 'low') {
      $('#tab-low').removeClass('btn-outline-warning').addClass('active btn-warning');
      $('.warehouse-card').each(function() {
        var isLow = $(this).data('is-low') === 'true';
        if (isLow) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    }
  }
</script>
<style>
  .warehouse-card {
    transition: all 0.3s ease;
  }
  .warehouse-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  .btn-group .btn.active {
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }
  .btn-group .btn {
    transition: all 0.2s ease;
  }
</style>
@endpush
