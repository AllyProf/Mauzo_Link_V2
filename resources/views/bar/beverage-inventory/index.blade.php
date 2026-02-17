@extends('layouts.dashboard')

@section('title', 'Beverage Inventory')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-beer"></i> Beverage Inventory</h1>
    <p>Manage beverage inventory and stock levels</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Beverage Inventory</li>
  </ul>
</div>

<div class="row" id="statistics-cards">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-archive fa-3x"></i>
      <div class="info">
        <h4>Warehouse Stock</h4>
        <p><b id="stat-warehouse-qty">{{ number_format($totalWarehouseStock) }} bottle(s)</b></p>
        <small id="stat-warehouse-value">TSh {{ number_format($totalWarehouseValue, 2) }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-shopping-cart fa-3x"></i>
      <div class="info">
        <h4>Counter Stock</h4>
        <p><b id="stat-counter-qty">{{ number_format($totalCounterStock) }} bottle(s)</b></p>
        <small id="stat-counter-value">Value: TSh {{ number_format($totalCounterValue, 2) }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Stock Value</h4>
        <p><b id="stat-total-value">TSh {{ number_format($totalValue, 2) }}</b></p>
        <small id="stat-total-units">{{ number_format($totalWarehouseStock + $totalCounterStock) }} total bottle(s)</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-exclamation-triangle fa-3x"></i>
      <div class="info">
        <h4>Low Stock Items</h4>
        <p><b id="stat-low-stock-count">{{ $stockOverview->where('is_low_stock', true)->count() }}</b></p>
        <small>Need restocking</small>
      </div>
    </div>
  </div>
</div>

{{-- Recommendations Section --}}
@if(count($recommendations['low_stock']) > 0 || count($recommendations['stock_movements']) > 0)
<div class="row mt-4">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">
          <i class="fa fa-lightbulb-o"></i> Recommendations & Quick Actions
        </h3>
      </div>
      
      <div class="tile-body">
        {{-- Low Stock Alerts with Reorder Suggestions --}}
        @if(count($recommendations['low_stock']) > 0)
        <div class="mb-4">
          <h5 class="text-warning">
            <i class="fa fa-exclamation-triangle"></i> Low Stock Alerts ({{ count($recommendations['low_stock']) }})
          </h5>
          <div class="table-responsive">
            <table class="table table-sm table-bordered table-warning">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Variant</th>
                  <th>Current Stock</th>
                  <th>Warehouse</th>
                  <th>Counter</th>
                  <th>Suggested Reorder</th>
                  <th>Priority</th>
                  <th>Quick Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recommendations['low_stock'] as $item)
                <tr>
                  <td><strong>{{ $item['product_name'] }}</strong></td>
                  <td>{{ $item['variant'] }}</td>
                  <td><span class="badge badge-{{ $item['current_stock'] == 0 ? 'danger' : 'warning' }}">{{ $item['current_stock'] }} bottle(s)</span></td>
                  <td>{{ $item['warehouse_stock'] }} bottle(s)</td>
                  <td>{{ $item['counter_stock'] }} bottle(s)</td>
                  <td><strong>{{ $item['suggested_reorder'] }} bottle(s)</strong></td>
                  <td>
                    <span class="badge badge-{{ $item['priority'] == 'critical' ? 'danger' : ($item['priority'] == 'high' ? 'warning' : 'info') }}">
                      {{ ucfirst($item['priority']) }}
                    </span>
                  </td>
                  <td>
                    <a href="{{ route('bar.stock-receipts.create') }}" class="btn btn-sm btn-primary" title="Restock Now">
                      <i class="fa fa-plus"></i> Restock
                    </a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        @endif
        
        {{-- Stock Movement Recommendations --}}
        @if(count($recommendations['stock_movements']) > 0)
        <div class="mb-4">
          <h5 class="text-info">
            <i class="fa fa-exchange"></i> Stock Movement Recommendations ({{ count($recommendations['stock_movements']) }})
          </h5>
          <p class="text-muted"><small>Move stock from warehouse to counter to maintain adequate counter levels</small></p>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Variant</th>
                  <th>Warehouse Stock</th>
                  <th>Counter Stock</th>
                  <th>Suggested Transfer</th>
                  <th>Priority</th>
                  <th>Quick Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recommendations['stock_movements'] as $item)
                <tr>
                  <td><strong>{{ $item['product_name'] }}</strong></td>
                  <td>{{ $item['variant'] }}</td>
                  <td><span class="badge badge-primary">{{ $item['warehouse_stock'] }} bottle(s)</span></td>
                  <td><span class="badge badge-info">{{ $item['counter_stock'] }} bottle(s)</span></td>
                  <td><strong class="text-success">{{ $item['suggested_transfer'] }} bottle(s)</strong></td>
                  <td>
                    <span class="badge badge-{{ $item['priority'] == 'high' ? 'warning' : 'info' }}">
                      {{ ucfirst($item['priority']) }}
                    </span>
                  </td>
                  <td>
                    @if($item['variant_id'])
                    @php
                      // Get variant to calculate packages
                      $variant = \App\Models\ProductVariant::find($item['variant_id']);
                      $itemsPerPackage = $variant ? $variant->items_per_package : 10;
                      $packagesNeeded = ceil($item['suggested_transfer'] / $itemsPerPackage);
                    @endphp
                    <form action="{{ route('bar.stock-transfers.store') }}" method="POST" class="d-inline">
                      @csrf
                      <input type="hidden" name="product_variant_id" value="{{ $item['variant_id'] }}">
                      <input type="hidden" name="quantity_requested" value="{{ $packagesNeeded }}">
                      <input type="hidden" name="notes" value="Auto-recommended transfer: {{ $item['suggested_transfer'] }} bottle(s) from Beverage Inventory">
                      <button type="submit" class="btn btn-sm btn-success" title="Transfer {{ $item['suggested_transfer'] }} bottle(s) to Counter">
                        <i class="fa fa-arrow-right"></i> Transfer
                      </button>
                    </form>
                    @else
                    <a href="{{ route('bar.stock-transfers.create') }}" class="btn btn-sm btn-info">
                      <i class="fa fa-exchange"></i> Create Transfer
                    </a>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        @endif
        
        {{-- Quick Actions Panel --}}
        <div class="row mt-4">
          <div class="col-md-12">
            <h5><i class="fa fa-bolt"></i> Quick Actions</h5>
            <div class="btn-group-vertical" role="group" style="width: 100%;">
              <a href="{{ route('bar.stock-receipts.create') }}" class="btn btn-primary mb-2">
                <i class="fa fa-plus-circle"></i> Add New Stock Receipt
              </a>
              <a href="{{ route('bar.stock-transfers.create') }}" class="btn btn-success mb-2">
                <i class="fa fa-exchange"></i> Create Stock Transfer
              </a>
              <a href="{{ route('bar.beverage-inventory.low-stock-alerts') }}" class="btn btn-warning mb-2">
                <i class="fa fa-exclamation-triangle"></i> View All Low Stock Alerts
              </a>
              <a href="{{ route('bar.products.create') }}" class="btn btn-info mb-2">
                <i class="fa fa-cube"></i> Add New Product
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

{{-- Stock Overview Section --}}
<div class="row mt-4">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">
          <i class="fa fa-bar-chart"></i> Stock Overview - Warehouse & Counter
        </h3>
        <div class="d-flex align-items-center">
          <div class="btn-group mr-2" role="group">
            <button type="button" class="btn btn-sm btn-primary active" id="filter-all" onclick="filterStock('all')">
              <i class="fa fa-list"></i> All Stock
            </button>
            <button type="button" class="btn btn-sm btn-outline-info" id="filter-warehouse" onclick="filterStock('warehouse')">
              <i class="fa fa-archive"></i> Warehouse Only
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" id="filter-counter" onclick="filterStock('counter')">
              <i class="fa fa-shopping-cart"></i> Counter Only
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning" id="filter-low" onclick="filterStock('low')">
              <i class="fa fa-exclamation-triangle"></i> Low Stock
            </button>
          </div>
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-secondary active" id="view-table" onclick="switchView('table')" title="Table View">
              <i class="fa fa-table"></i> Table
            </button>
            <button type="button" class="btn btn-sm btn-secondary" id="view-card" onclick="switchView('card')" title="Card View">
              <i class="fa fa-th-large"></i> Cards
            </button>
          </div>
        </div>
      </div>

      <div class="tile-body">
        @if($stockOverview->count() > 0)
          {{-- Table View --}}
          <div id="table-view" class="view-container">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="stockOverviewTable">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>Warehouse Stock</th>
                    <th>Warehouse Value</th>
                    <th>Counter Stock</th>
                    <th>Counter Value</th>
                    <th>Total Stock</th>
                    <th>Total Value</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($stockOverview as $stock)
                    <tr class="stock-row {{ $stock['is_low_stock'] ? 'table-warning' : '' }}" 
                        data-location="all"
                        data-warehouse-qty="{{ $stock['warehouse_quantity'] }}"
                        data-counter-qty="{{ $stock['counter_quantity'] }}"
                        data-warehouse-value="{{ $stock['warehouse_value'] }}"
                        data-counter-value="{{ $stock['counter_value'] }}"
                        data-is-low="{{ $stock['is_low_stock'] ? 'true' : 'false' }}">
                      <td><strong>{{ $stock['product_name'] }}</strong></td>
                      <td>{{ $stock['variant'] }}</td>
                      <td>
                        @php
                          $packagingType = strtolower($stock['packaging_type'] ?? 'packages');
                          $packagingTypeSingular = rtrim($packagingType, 's');
                          if ($packagingTypeSingular == 'boxe') {
                            $packagingTypeSingular = 'box';
                          }
                        @endphp
                        <div>{{ number_format($stock['warehouse_quantity']) }} bottle(s)</div>
                        @if($stock['warehouse_packages'] > 0 && $stock['items_per_package'] > 1)
                          <small class="text-muted">{{ number_format($stock['warehouse_packages']) }} {{ $stock['warehouse_packages'] == 1 ? $packagingTypeSingular : $packagingType }}</small>
                        @endif
                      </td>
                      <td>TSh {{ number_format($stock['warehouse_value'], 2) }}</td>
                      <td>
                        <div>{{ number_format($stock['counter_quantity']) }} bottle(s)</div>
                        @if($stock['counter_packages'] > 0 && $stock['items_per_package'] > 1)
                          <small class="text-muted">{{ number_format($stock['counter_packages']) }} {{ $stock['counter_packages'] == 1 ? $packagingTypeSingular : $packagingType }}</small>
                        @endif
                      </td>
                      <td>TSh {{ number_format($stock['counter_value'], 2) }}</td>
                      <td>
                        <div><strong>{{ number_format($stock['total_quantity']) }} bottle(s)</strong></div>
                        @if($stock['total_packages'] > 0 && $stock['items_per_package'] > 1)
                          <small class="text-muted"><strong>{{ number_format($stock['total_packages']) }} {{ $stock['total_packages'] == 1 ? $packagingTypeSingular : $packagingType }}</strong></small>
                        @endif
                      </td>
                      <td><strong>TSh {{ number_format($stock['warehouse_value'] + $stock['counter_value'], 2) }}</strong></td>
                      <td>
                        @if($stock['is_low_stock'])
                          <span class="badge badge-warning">Low Stock</span>
                        @elseif($stock['total_quantity'] == 0)
                          <span class="badge badge-danger">Out of Stock</span>
                        @else
                          <span class="badge badge-success">In Stock</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          {{-- Card View --}}
          <div id="card-view" class="view-container" style="display: none;">
            <div class="row" id="stock-cards-container">
              @foreach($stockOverview as $stock)
                <div class="col-md-4 mb-3 stock-card" 
                     data-warehouse-qty="{{ $stock['warehouse_quantity'] }}"
                     data-counter-qty="{{ $stock['counter_quantity'] }}"
                     data-warehouse-value="{{ $stock['warehouse_value'] }}"
                     data-counter-value="{{ $stock['counter_value'] }}"
                     data-is-low="{{ $stock['is_low_stock'] ? 'true' : 'false' }}">
                  <div class="card h-100 {{ $stock['is_low_stock'] ? 'border-warning' : ($stock['total_quantity'] == 0 ? 'border-danger' : 'border-success') }}">
                    <div class="card-header {{ $stock['is_low_stock'] ? 'bg-warning' : ($stock['total_quantity'] == 0 ? 'bg-danger text-white' : 'bg-success text-white') }}">
                      <h5 class="card-title mb-0">
                        <strong>{{ $stock['product_name'] }}</strong>
                        @if($stock['is_low_stock'])
                          <span class="badge badge-warning float-right">Low Stock</span>
                        @elseif($stock['total_quantity'] == 0)
                          <span class="badge badge-danger float-right">Out of Stock</span>
                        @else
                          <span class="badge badge-light float-right">In Stock</span>
                        @endif
                      </h5>
                    </div>
                    <div class="card-body">
                      <p class="text-muted mb-2"><small>{{ $stock['variant'] }}</small></p>
                      <div class="row">
                        <div class="col-6">
                          <div class="text-center p-2 bg-primary text-white rounded mb-2">
                            <small><strong>Warehouse</strong></small>
                            @php
                              $packagingType = strtolower($stock['packaging_type'] ?? 'packages');
                              $packagingTypeSingular = rtrim($packagingType, 's');
                              if ($packagingTypeSingular == 'boxe') {
                                $packagingTypeSingular = 'box';
                              }
                            @endphp
                            <div class="row align-items-center mt-1">
                              <div class="col-6 text-right border-right border-white">
                                @if($stock['warehouse_packages'] > 0 && $stock['items_per_package'] > 1)
                                  <div class="h6 mb-0">{{ number_format($stock['warehouse_packages']) }}</div>
                                  <small>{{ $stock['warehouse_packages'] == 1 ? $packagingTypeSingular : $packagingType }}</small>
                                @else
                                  <div class="h6 mb-0">-</div>
                                  <small>-</small>
                                @endif
                              </div>
                              <div class="col-6 text-left">
                                <div class="h5 mb-0">{{ number_format($stock['warehouse_quantity']) }}</div>
                                <small>bottle(s)</small>
                              </div>
                            </div>
                            <div class="mt-1"><small>TSh {{ number_format($stock['warehouse_value'], 2) }}</small></div>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="text-center p-2 bg-info text-white rounded mb-2">
                            <small><strong>Counter</strong></small>
                            <div class="row align-items-center mt-1">
                              <div class="col-6 text-right border-right border-white">
                                @if($stock['counter_packages'] > 0 && $stock['items_per_package'] > 1)
                                  <div class="h6 mb-0">{{ number_format($stock['counter_packages']) }}</div>
                                  <small>{{ $stock['counter_packages'] == 1 ? $packagingTypeSingular : $packagingType }}</small>
                                @else
                                  <div class="h6 mb-0">-</div>
                                  <small>-</small>
                                @endif
                              </div>
                              <div class="col-6 text-left">
                                <div class="h5 mb-0">{{ number_format($stock['counter_quantity']) }}</div>
                                <small>bottle(s)</small>
                              </div>
                            </div>
                            <div class="mt-1"><small>TSh {{ number_format($stock['counter_value'], 2) }}</small></div>
                          </div>
                        </div>
                      </div>
                      <hr>
                      <div class="row text-center">
                        <div class="col-6">
                          <strong>Total Stock</strong>
                          <div class="h5 text-primary">{{ number_format($stock['total_quantity']) }} bottle(s)</div>
                          @if($stock['total_packages'] > 0 && $stock['items_per_package'] > 1)
                            <small class="text-muted">{{ number_format($stock['total_packages']) }} {{ $stock['total_packages'] == 1 ? $packagingTypeSingular : $packagingType }}</small>
                          @endif
                        </div>
                        <div class="col-6">
                          <strong>Total Value</strong>
                          <div class="h5 text-success">TSh {{ number_format($stock['warehouse_value'] + $stock['counter_value'], 2) }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No stock data available.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Beverages</h3>
        <div>
          <a href="{{ route('bar.beverage-inventory.low-stock-alerts') }}" class="btn btn-warning mr-2">
            <i class="fa fa-exclamation-triangle"></i> Low Stock Alerts
          </a>
          <a href="{{ route('bar.beverage-inventory.stock-levels') }}" class="btn btn-info mr-2">
            <i class="fa fa-bar-chart"></i> Stock Levels
          </a>
          @php
            $canCreate = false;
            if (session('is_staff')) {
              $staff = \App\Models\Staff::find(session('staff_id'));
              if ($staff && $staff->role) {
                $canCreate = $staff->role->hasPermission('inventory', 'create');
              }
            } else {
              $user = Auth::user();
              $canCreate = $user && ($user->hasPermission('inventory', 'create') || $user->hasRole('owner'));
            }
          @endphp
          @if($canCreate)
            <a href="{{ route('bar.beverage-inventory.add') }}" class="btn btn-primary">
              <i class="fa fa-plus"></i> Add Beverage
            </a>
          @endif
        </div>
      </div>

      <div class="tile-body">
        @if($productsWithStock->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="beveragesTable">
              <thead>
                <tr>
                  <th>Product Name</th>
                  <th>Category</th>
                  <th>Variants</th>
                  <th>Warehouse Stock</th>
                  <th>Counter Stock</th>
                  <th>Total Stock</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($productsWithStock as $product)
                  @php
                    $warehouseTotal = 0;
                    $counterTotal = 0;
                    $warehousePackagesTotal = 0;
                    $counterPackagesTotal = 0;
                    $packagingTypes = [];
                    $itemsPerPackage = 1;
                    
                    foreach ($product->variants as $variant) {
                      $warehouse = $variant->stockLocations->where('location', 'warehouse')->first();
                      $counter = $variant->stockLocations->where('location', 'counter')->first();
                      $warehouseQty = $warehouse ? $warehouse->quantity : 0;
                      $counterQty = $counter ? $counter->quantity : 0;
                      $warehouseTotal += $warehouseQty;
                      $counterTotal += $counterQty;
                      
                      // Calculate packages
                      $itemsPerPackage = $variant->items_per_package ?? 1;
                      if ($itemsPerPackage > 1) {
                        $warehousePackagesTotal += floor($warehouseQty / $itemsPerPackage);
                        $counterPackagesTotal += floor($counterQty / $itemsPerPackage);
                        $packagingType = strtolower($variant->packaging ?? 'packages');
                        if (!in_array($packagingType, $packagingTypes)) {
                          $packagingTypes[] = $packagingType;
                        }
                      }
                    }
                    $totalStock = $warehouseTotal + $counterTotal;
                    $totalPackages = $warehousePackagesTotal + $counterPackagesTotal;
                    $isLowStock = $totalStock < 10;
                    
                    // Get packaging type (use first one if multiple)
                    $packagingType = !empty($packagingTypes) ? $packagingTypes[0] : 'packages';
                    $packagingTypeSingular = rtrim($packagingType, 's');
                    if ($packagingTypeSingular == 'boxe') {
                      $packagingTypeSingular = 'box';
                    }
                  @endphp
                  <tr class="{{ $isLowStock ? 'table-warning' : '' }}">
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>{{ $product->category ?? 'N/A' }}</td>
                    <td>
                      <span class="badge badge-info">{{ $product->variants->count() }} variant(s)</span>
                    </td>
                    <td>
                      <div>{{ number_format($warehouseTotal) }} bottle(s)</div>
                      @if($warehousePackagesTotal > 0 && $itemsPerPackage > 1)
                        <small class="text-muted">{{ number_format($warehousePackagesTotal) }} {{ $warehousePackagesTotal == 1 ? $packagingTypeSingular : $packagingType }}</small>
                      @endif
                    </td>
                    <td>
                      <div>{{ number_format($counterTotal) }} bottle(s)</div>
                      @if($counterPackagesTotal > 0 && $itemsPerPackage > 1)
                        <small class="text-muted">{{ number_format($counterPackagesTotal) }} {{ $counterPackagesTotal == 1 ? $packagingTypeSingular : $packagingType }}</small>
                      @endif
                    </td>
                    <td>
                      <div><strong>{{ number_format($totalStock) }} bottle(s)</strong></div>
                      @if($totalPackages > 0 && $itemsPerPackage > 1)
                        <small class="text-muted"><strong>{{ number_format($totalPackages) }} {{ $totalPackages == 1 ? $packagingTypeSingular : $packagingType }}</strong></small>
                      @endif
                    </td>
                    <td>
                      @if($isLowStock)
                        <span class="badge badge-warning">Low Stock</span>
                      @elseif($totalStock == 0)
                        <span class="badge badge-danger">Out of Stock</span>
                      @else
                        <span class="badge badge-success">In Stock</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No beverage products found. 
            <a href="{{ route('bar.products.create') }}">Create a beverage product</a> to get started.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- Data table plugin (optional) -->
<script type="text/javascript" src="{{ asset('js/admin/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/admin/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript">
  var stockOverviewTable;
  var currentFilter = 'all';
  var currentView = 'table';
  var customFilter = null;

  $(document).ready(function() {
    // Initialize DataTable for beverages table if available
    if (typeof $.fn.DataTable !== 'undefined') {
      try {
        $('#beveragesTable').DataTable({
          "paging": true,
          "info": true,
          "searching": true,
        });
      } catch(e) {
        console.warn('DataTable initialization failed for beveragesTable:', e);
      }
      
      // Initialize DataTable for stock overview only if table exists
      if ($('#stockOverviewTable').length > 0) {
        try {
          stockOverviewTable = $('#stockOverviewTable').DataTable({
            "paging": true,
            "info": true,
            "searching": true,
            "order": [[6, "desc"]], // Sort by total stock descending
            "columnDefs": [
              { "orderable": false, "targets": [8] } // Disable sorting on Status column
            ]
          });
          
          // Add custom filter function
          $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'stockOverviewTable' || !customFilter) {
              return true;
            }
            
            var $row = $('#stockOverviewTable tbody tr').eq(dataIndex);
            if ($row.length === 0) return true;
            
            var warehouseQty = parseInt($row.data('warehouse-qty')) || 0;
            var counterQty = parseInt($row.data('counter-qty')) || 0;
            var isLow = $row.data('is-low') === 'true';
            
            switch(customFilter) {
              case 'warehouse':
                return warehouseQty > 0;
              case 'counter':
                return counterQty > 0;
              case 'low':
                return isLow;
              default:
                return true;
            }
          });
        } catch(e) {
          console.warn('DataTable initialization failed for stockOverviewTable:', e);
        }
      }
    } else {
      console.warn('DataTable plugin not loaded, tables will work without it');
    }
  });
  
  function filterStock(filter) {
    currentFilter = filter;
    customFilter = filter === 'all' ? null : filter;
    
    // Update active filter button with better visual feedback
    updateFilterButtons(filter);
    
    // Filter table rows (with or without DataTable)
    if (stockOverviewTable && typeof stockOverviewTable !== 'undefined' && typeof stockOverviewTable.draw === 'function') {
      // Use DataTable's draw method
      try {
        stockOverviewTable.draw();
        // Update statistics after DataTable redraws (wait a bit for DOM to update)
        setTimeout(function() {
          updateStatistics(filter);
        }, 200);
      } catch(e) {
        console.warn('DataTable draw failed, using fallback:', e);
        filterTableRowsManually(filter);
      }
    } else {
      // Fallback: manually filter table rows (updateStatistics is called inside)
      filterTableRowsManually(filter);
    }
    
    // Filter card view
    filterCardView(filter);
  }
  
  function updateFilterButtons(filter) {
    // Remove all active classes and reset button styles
    $('#filter-all').removeClass('active btn-primary').addClass('btn-outline-primary');
    $('#filter-warehouse').removeClass('active btn-info').addClass('btn-outline-info');
    $('#filter-counter').removeClass('active btn-success').addClass('btn-outline-success');
    $('#filter-low').removeClass('active btn-warning').addClass('btn-outline-warning');
    
    // Add active class to selected button
    var $activeBtn = $('#filter-' + filter);
    $activeBtn.removeClass('btn-outline-primary btn-outline-info btn-outline-success btn-outline-warning');
    
    switch(filter) {
      case 'all':
        $activeBtn.addClass('active btn-primary');
        break;
      case 'warehouse':
        $activeBtn.addClass('active btn-info');
        break;
      case 'counter':
        $activeBtn.addClass('active btn-success');
        break;
      case 'low':
        $activeBtn.addClass('active btn-warning');
        break;
    }
  }
  
  function updateStatistics(filter) {
    var totalWarehouseQty = 0;
    var totalCounterQty = 0;
    var totalWarehouseValue = 0;
    var totalCounterValue = 0;
    var lowStockCount = 0;
    
    // Get all stock items (from both table and cards)
    // We calculate based on filter criteria, not visibility
    var $allItems = $('#stockOverviewTable tbody tr.stock-row, .stock-card');
    
    $allItems.each(function() {
      var $item = $(this);
      
      var warehouseQty = parseInt($item.data('warehouse-qty')) || 0;
      var counterQty = parseInt($item.data('counter-qty')) || 0;
      var warehouseValue = parseFloat($item.data('warehouse-value')) || 0;
      var counterValue = parseFloat($item.data('counter-value')) || 0;
      var isLow = $item.data('is-low') === 'true';
      
      // Check if this item should be included in statistics based on filter
      var include = false;
      switch(filter) {
        case 'warehouse':
          include = warehouseQty > 0;
          break;
        case 'counter':
          include = counterQty > 0;
          break;
        case 'low':
          include = isLow;
          break;
        default:
          include = true;
      }
      
      if (include) {
        totalWarehouseQty += warehouseQty;
        totalCounterQty += counterQty;
        totalWarehouseValue += warehouseValue;
        totalCounterValue += counterValue;
        if (isLow) {
          lowStockCount++;
        }
      }
    });
    
    // Update statistics cards with animation
    $('#stat-warehouse-qty').fadeOut(100, function() {
      $(this).text(formatNumber(totalWarehouseQty) + ' bottle(s)').fadeIn(100);
    });
    $('#stat-warehouse-value').fadeOut(100, function() {
      $(this).text('TSh ' + formatNumber(totalWarehouseValue, 2)).fadeIn(100);
    });
    $('#stat-counter-qty').fadeOut(100, function() {
      $(this).text(formatNumber(totalCounterQty) + ' bottle(s)').fadeIn(100);
    });
    $('#stat-counter-value').fadeOut(100, function() {
      $(this).text('Value: TSh ' + formatNumber(totalCounterValue, 2)).fadeIn(100);
    });
    $('#stat-total-value').fadeOut(100, function() {
      $(this).text('TSh ' + formatNumber(totalWarehouseValue + totalCounterValue, 2)).fadeIn(100);
    });
    $('#stat-total-units').fadeOut(100, function() {
      $(this).text(formatNumber(totalWarehouseQty + totalCounterQty) + ' total bottle(s)').fadeIn(100);
    });
    $('#stat-low-stock-count').fadeOut(100, function() {
      $(this).text(lowStockCount).fadeIn(100);
    });
  }
  
  function formatNumber(num, decimals) {
    if (decimals === undefined) {
      decimals = 0;
    }
    return num.toLocaleString('en-US', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    });
  }
  
  function filterTableRowsManually(filter) {
    $('#stockOverviewTable tbody tr.stock-row').each(function() {
      var $row = $(this);
      var warehouseQty = parseInt($row.data('warehouse-qty')) || 0;
      var counterQty = parseInt($row.data('counter-qty')) || 0;
      var isLow = $row.data('is-low') === 'true';
      
      var show = false;
      switch(filter) {
        case 'warehouse':
          show = warehouseQty > 0;
          break;
        case 'counter':
          show = counterQty > 0;
          break;
        case 'low':
          show = isLow;
          break;
        default:
          show = true;
      }
      
      if (show) {
        $row.show();
      } else {
        $row.hide();
      }
    });
    
    // Update statistics after filtering
    updateStatistics(filter);
  }
  
  function filterCardView(filter) {
    $('.stock-card').each(function() {
      var $card = $(this);
      var warehouseQty = parseInt($card.data('warehouse-qty')) || 0;
      var counterQty = parseInt($card.data('counter-qty')) || 0;
      var isLow = $card.data('is-low') === 'true';
      
      var show = false;
      switch(filter) {
        case 'warehouse':
          show = warehouseQty > 0;
          break;
        case 'counter':
          show = counterQty > 0;
          break;
        case 'low':
          show = isLow;
          break;
        default:
          show = true;
      }
      
      if (show) {
        $card.show();
      } else {
        $card.hide();
      }
    });
    
    // Update statistics after filtering cards (only if card view is active)
    if (currentView === 'card') {
      updateStatistics(filter);
    }
  }
  
  function switchView(view) {
    currentView = view;
    
    if (view === 'table') {
      $('#table-view').show();
      $('#card-view').hide();
      $('#view-table').addClass('active');
      $('#view-card').removeClass('active');
    } else {
      $('#table-view').hide();
      $('#card-view').show();
      $('#view-card').addClass('active');
      $('#view-table').removeClass('active');
      
      // Reapply current filter to cards
      filterStock(currentFilter);
    }
  }
</script>
<style>
  .view-container {
    min-height: 200px;
  }
  .stock-card {
    transition: all 0.3s ease;
  }
  .stock-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  .btn-group .btn.active {
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transform: scale(1.05);
  }
  .btn-group .btn {
    transition: all 0.2s ease;
    border-width: 2px;
  }
  .btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
  }
</style>
@endpush

