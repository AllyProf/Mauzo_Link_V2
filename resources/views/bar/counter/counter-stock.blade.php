@extends('layouts.dashboard')

@section('title', 'Counter Stock')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cubes"></i> Counter Stock</h1>
    <p>View current counter inventory in detail</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Counter Dashboard</a></li>
    <li class="breadcrumb-item">Counter Stock</li>
  </ul>
</div>

<!-- Statistics -->
<div class="row">
  <div class="col-md-4">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-cubes fa-3x"></i>
      <div class="info">
        <h4>Total Items</h4>
        <p><b>{{ count($variants) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-exclamation-triangle fa-3x"></i>
      <div class="info">
        <h4>Low Stock Items</h4>
        <p><b>{{ $variants->where('is_low_stock', true)->count() }}</b></p>
      </div>
    </div>
  </div>
  @if(!session('is_staff'))
  <div class="col-md-4">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Stock Value</h4>
        <p><b>TSh {{ number_format($totalValue, 2) }}</b></p>
      </div>
    </div>
  </div>
  @endif
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile shadow-sm border-0" style="border-radius: 15px;">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="tile-title mb-0">Counter Inventory</h3>
        <div class="d-flex align-items-center">
            <!-- VIEW TOGGLE -->
            <div class="btn-group mr-3" role="group">
                <button type="button" class="btn btn-sm btn-outline-secondary active view-btn" data-view="grid">
                    <i class="fa fa-th"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary view-btn" data-view="list">
                    <i class="fa fa-list"></i>
                </button>
            </div>
            <a href="{{ route('bar.counter.daily-stock-sheet') }}" class="btn btn-info btn-sm shadow-sm mr-2"><i class="fa fa-print"></i> Print Daily Stock Sheet</a>
            <a href="{{ route('bar.counter.waiter-orders') }}" class="btn btn-primary btn-sm shadow-sm mr-2"><i class="fa fa-shopping-cart"></i> Create Order</a>
          <a href="{{ route('bar.counter.dashboard') }}" class="btn btn-secondary shadow-sm">
            <i class="fa fa-arrow-left"></i> Back
          </a>
        </div>
      </div>

      <!-- SEARCH & QUICK FILTERS -->
      <div class="row mb-4">
          <div class="col-md-3">
              <div class="form-group">
                  <label class="control-label font-weight-bold">Search Products</label>
                  <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fa fa-search"></i></span>
                      </div>
                      <input type="text" id="inventorySearch" class="form-control" placeholder="Search by name...">
                  </div>
              </div>
          </div>
          <div class="col-md-9">
              <label class="control-label font-weight-bold scale-in-center">Quick Filters (Categories)</label>
              <div class="category-tabs-wrapper">
                  <div class="d-flex align-items-center overflow-auto no-scrollbar py-1" id="categoryContainer">
                      <button class="btn btn-sm btn-outline-primary active filter-pill mr-1 mb-1" data-filter="all" data-filter-type="category">
                          ALL ITEMS
                      </button>
                      @foreach($categories as $label)
                          <button class="btn btn-sm btn-outline-primary filter-pill mr-1 mb-1" data-filter="{{ Str::slug($label) }}" data-filter-type="category">
                              {{ strtoupper($label) }}
                          </button>
                      @endforeach
                  </div>
              </div>
          </div>
      </div>

      <hr class="mb-4">

      <div class="tile-body">
        @if(count($variants) > 0)
          
          <!-- INVENTORY GRID -->
          <div class="row mt-2" id="inventoryGrid">
            @foreach($variants as $variant)
            @php
              $catSlug     = \Illuminate\Support\Str::slug($variant['category']);
              $brandSlug   = \Illuminate\Support\Str::slug($variant['brand']);
              $qty         = $variant['quantity'];
              $ipp         = $variant['items_per_package'] ?? 1;
              $pkgLabel    = $variant['packaging'] ?? 'Crate';
              $crates      = $ipp > 1 ? floor($qty / $ipp) : 0;
              $extraBottles= $ipp > 1 ? ($qty % $ipp) : $qty;
              $statusColor = $qty <= 0 ? 'danger' : ($variant['is_low_stock'] ? 'warning' : 'success');
              
              // Use variant name as the primary display title (cleaner, e.g. "Fanta Orange")
              $displayTitle = !empty($variant['variant_name']) ? $variant['variant_name'] : $variant['product_name'];
              // Searchable name includes both names for accuracy
              $searchName = strtolower($variant['product_name'] . ' ' . $variant['variant_name'] . ' ' . $variant['brand'] . ' ' . $variant['category']);
            @endphp
            <div class="col-md-4 mb-4 product-card-wrapper" 
                 data-category="{{ $catSlug }}"
                 data-brand="{{ $brandSlug }}"
                 data-name="{{ $searchName }}"
                 data-variant-id="{{ $variant['id'] }}"
                 data-threshold="{{ $variant['low_stock_threshold'] }}">
              
              <div class="tile p-3 h-100 mb-0 shadow-sm border-0 inventory-item-card transition-all" 
                   style="border-radius: 15px; {{ $statusColor == 'danger' ? 'background-color: #ffebee !important;' : ($statusColor == 'warning' ? 'background-color: #fffde7 !important;' : '') }}">
                  @if($statusColor == 'warning')
                      <div class="badge badge-warning position-absolute" style="top: 10px; right: 10px; z-index: 5;">LOW STOCK</div>
                  @elseif($statusColor == 'danger')
                      <div class="badge badge-danger position-absolute" style="top: 10px; right: 10px; z-index: 5;">OUT OF STOCK</div>
                  @endif

                  <div class="d-flex justify-content-between align-items-start mb-2">
                      <div class="flex-grow-1 pr-2">
                          <h6 class="font-weight-bold text-primary mb-1 line-clamp-1" title="{{ $displayTitle }}">{{ $displayTitle }}</h6>
                          <p class="text-muted smallest mb-0">{{ $variant['brand'] }} • {{ $variant['category'] }}</p>
                      </div>
                  </div>
                  <div class="mb-3">
                      <div class="text-muted smallest font-weight-bold mb-1">
                          ({{ $variant['variant'] }}{{ $variant['unit'] }} - {{ $variant['packaging_type'] }})
                      </div>
                      <div class="d-flex justify-content-between align-items-center bg-white border rounded p-2 shadow-xs">
                          <div>
                              <div class="smallest text-muted text-uppercase font-weight-bold">Available</div>
                              <div class="h6 mb-0 font-weight-bold text-{{ $statusColor }}">{{ $variant['formatted_quantity'] }}</div>
                          </div>
                          @if($variant['can_sell_in_tots'])
                           <div class="text-right border-left pl-2">
                               <div class="smallest text-muted text-uppercase font-weight-bold">Total Portions</div>
                               <div class="h6 mb-0 font-weight-bold text-info">
                                   {{ number_format($variant['quantity_in_tots']) }} 
                                   {{ $variant['quantity_in_tots'] == 1 ? $variant['portion_unit_name'] : ($variant['portion_unit_name'] == 'Glass' ? 'Glasses' : $variant['portion_unit_name'].'s') }}
                               </div>
                           </div>
                           @endif
                       </div>
                       
                       @if($ipp > 1 && $crates > 0)
                       <div class="smallest text-center mt-1 text-muted">
                           <i class="fa fa-archive"></i> Equivalent to <strong>{{ $crates }} {{ $pkgLabel }}{{ $crates != 1 ? 's' : '' }}@if($extraBottles > 0), {{ $extraBottles }} btl{{ $extraBottles != 1 ? 's' : '' }} @endif</strong>
                       </div>
                       @endif
                  </div>

                  <div class="row no-gutters mb-3 text-center bg-white rounded border py-2 shadow-xs">
                      <div class="col-6 border-right">
                          <div class="smallest text-muted">Bottle Price</div>
                          <div class="font-weight-bold text-dark">TSh {{ number_format($variant['selling_price']) }}</div>
                      </div>
                      <div class="col-6">
                          @if($variant['can_sell_in_tots'])
                            @php $totsPer = ($variant['quantity_in_tots'] > 0 && $qty > 0) ? round($variant['quantity_in_tots'] / $qty) : 0; @endphp
                            <div class="smallest text-muted">{{ $variant['portion_unit_name'] }} Price ({{ $totsPer }}/btl)</div>
                            <div class="font-weight-bold text-info">TSh {{ number_format($variant['selling_price_per_tot']) }}</div>
                          @else
                            <div class="smallest text-muted">{{ $variant['portion_unit_name'] }} Price</div>
                            <div class="smallest italic text-muted">N/A</div>
                          @endif
                      </div>
                  </div>

                  @if(!session('is_staff'))
                  <div class="d-flex justify-content-between align-items-center mb-0 mt-auto">
                    <div class="smallest">
                        <span class="text-muted">Holding Value:</span><br>
                        <strong class="text-success">TSh {{ number_format($qty * $variant['selling_price']) }}</strong>
                    </div>
                    @if($variant['product_image'])
                        <img src="{{ asset('storage/' . $variant['product_image']) }}" class="rounded shadow-xs" style="width: 32px; height: 32px; object-fit: contain; background: #fff;" onerror="this.style.display='none'">
                    @endif
                    <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary btn-set-threshold" 
                            title="Set Low Stock Alert"
                            onclick="openThresholdModal({{ $variant['id'] }}, '{{ addslashes($displayTitle) }}', '{{ addslashes($pkgLabel) }}', {{ $variant['can_sell_in_tots'] ? 'true' : 'false' }}, '{{ $variant['portion_unit_name'] }}')">
                        <i class="fa fa-bell-o"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" 
                            title="Remove from Counter Stock"
                            onclick="confirmDeleteStock({{ $variant['id'] }}, '{{ addslashes($displayTitle) }}')">
                        <i class="fa fa-trash"></i>
                    </button>
                    </div>
                  </div>
                  @else
                  <div class="d-flex justify-content-end align-items-center mb-0 mt-auto">
                    @if($variant['product_image'])
                        <img src="{{ asset('storage/' . $variant['product_image']) }}" class="rounded shadow-xs mr-auto" style="width: 32px; height: 32px; object-fit: contain; background: #fff;" onerror="this.style.display='none'">
                    @endif
                    <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary btn-set-threshold" 
                            title="Set Low Stock Alert"
                            onclick="openThresholdModal({{ $variant['id'] }}, '{{ addslashes($displayTitle) }}', '{{ addslashes($pkgLabel) }}', {{ $variant['can_sell_in_tots'] ? 'true' : 'false' }}, '{{ $variant['portion_unit_name'] }}')">
                        <i class="fa fa-bell-o"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" 
                            title="Remove from Counter Stock"
                            onclick="confirmDeleteStock({{ $variant['id'] }}, '{{ addslashes($displayTitle) }}')">
                        <i class="fa fa-trash"></i>
                    </button>
                    </div>
                  </div>
                  @endif
                  <!-- Threshold indicator -->
                  <div class="threshold-info mt-1 text-right" id="threshold-info-{{ $variant['id'] }}" style="font-size:9px; color:#e65100; font-weight:bold;"></div>
              </div>
            </div>
            @endforeach
          </div>

          <!-- INVENTORY LIST (Hidden by default) -->
          <div id="inventoryList" class="table-responsive d-none mt-2">
              <table class="table table-hover table-bordered shadow-sm" style="border-radius: 10px; overflow: hidden;">
                  <thead class="bg-light">
                      <tr>
                          <th>Product Name</th>
                          <th>Brand / Category</th>
                          <th>Measure</th>
                          <th>Counter Stock</th>
                          <th>Selling Price</th>
                          @if(!session('is_staff'))
                          <th>Holding Value</th>
                          @endif
                          <th>Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach($variants as $variant)
                      @php
                        $catSlug = \Illuminate\Support\Str::slug($variant['category']);
                        $brandSlug = \Illuminate\Support\Str::slug($variant['brand']);
                        $displayTitle = !empty($variant['variant_name']) ? $variant['variant_name'] : $variant['product_name'];
                        $searchName = strtolower($variant['product_name'] . ' ' . $variant['variant_name'] . ' ' . $variant['brand'] . ' ' . $variant['category']);
                      @endphp
                      <tr class="product-card-wrapper" 
                          data-category="{{ $catSlug }}" 
                          data-brand="{{ $brandSlug }}"
                          data-name="{{ $searchName }}"
                          data-variant-id="{{ $variant['id'] }}">
                          <td><strong class="text-primary">{{ $displayTitle }}</strong><br><small class="text-muted">{{ $variant['brand'] }}</small></td>
                          <td>
                            <strong>{{ $variant['brand'] }}</strong><br>
                            <span class="badge badge-light border smallest text-muted">{{ $variant['category'] }}</span>
                          </td>
                          <td><span class="badge badge-secondary">{{ $variant['variant'] }}{{ $variant['unit'] }}</span></td>
                          <td>
                              <strong class="text-{{ $variant['quantity'] < 10 ? 'warning' : 'dark' }}">{{ number_format($variant['quantity']) }} btl</strong>
                              @if($variant['items_per_package'] > 1)
                                <br><small class="text-muted">{{ floor($variant['quantity'] / $variant['items_per_package']) }} {{ $variant['packaging'] }}s</small>
                              @endif
                          </td>
                          <td>
                            <div class="smallest font-weight-bold">Btl: TSh {{ number_format($variant['selling_price']) }}</div>
                            @if($variant['can_sell_in_tots'])
                              <div class="smallest text-info">{{ $variant['portion_unit_name'] }}: TSh {{ number_format($variant['selling_price_per_tot']) }}</div>
                            @endif
                          </td>
                          @if(!session('is_staff'))
                          <td><strong class="text-success">TSh {{ number_format($variant['quantity'] * $variant['selling_price']) }}</strong></td>
                          @endif
                          <td class="text-center">
                              <button class="btn btn-sm btn-outline-info" onclick="openThresholdModal({{ $variant['id'] }}, '{{ addslashes($displayTitle) }}', '{{ addslashes($variant['packaging'] ?? 'Crate') }}', {{ $variant['can_sell_in_tots'] ? 'true' : 'false' }}, '{{ $variant['portion_unit_name'] }}')">
                                  <i class="fa fa-bell-o"></i> Alert
                              </button>
                              <button class="btn btn-sm btn-outline-danger ml-1" onclick="confirmDeleteStock({{ $variant['id'] }}, '{{ addslashes($displayTitle) }}')">
                                  <i class="fa fa-trash"></i> Remove
                              </button>
                          </td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>

          @if(!session('is_staff'))
          <!-- Total Bar -->
          <div class="mt-4 p-4 rounded d-flex justify-content-between align-items-center shadow"
               style="background: linear-gradient(135deg, #1a237e, #283593); color:white; border-radius: 15px;">
            <div>
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-calculator mr-2"></i> Total Counter Assets</h5>
                <small class="opacity-75">Estimated value based on current selling prices</small>
            </div>
            <h3 class="mb-0 text-success font-weight-bold">TSh {{ number_format($totalValue, 2) }}</h3>
          </div>
          @endif

        @else
          <div class="alert alert-info py-4 text-center shadow-xs" style="border-radius: 15px;">
            <i class="fa fa-info-circle fa-2x mb-3"></i>
            <h4>No products currently in counter stock.</h4>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Low Stock Threshold Modal -->
<div class="modal fade" id="thresholdModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-bell-o mr-2"></i> Stock Alert</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="threshold-product-name" class="font-weight-bold mb-3"></p>
        <div class="form-group">
            <label class="control-label">Alert when stock is below:</label>
            <div class="input-group">
              <input type="number" id="threshold-value" class="form-control" min="1" value="10">
              <div class="input-group-append">
                <select id="threshold-unit" class="custom-select" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                  <option value="btls">bottles</option>
                  <option id="threshold-pkg-option" value="crates">crates</option>
                  <option id="threshold-glass-option" value="glasses" style="display:none;">glasses/tots</option>
                </select>
              </div>
            </div>
        </div>
        <div class="text-muted small"><i class="fa fa-info-circle"></i> Saved locally in browser.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
        <button type="button" class="btn btn-primary" id="saveThresholdBtn"><i class="fa fa-save"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Counter Stock Confirmation Modal -->
<div class="modal fade" id="deleteStockModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa fa-trash mr-2"></i> Remove from Stock</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-1">Remove <strong id="deleteStockName"></strong> from counter stock?</p>
        <p class="text-muted small">This will clear the stock record. Sales history is unaffected.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteStockBtn"><i class="fa fa-trash"></i> Yes, Remove</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
    .font-weight-extra-bold { font-weight: 800; }
    .smallest { font-size: 11px; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .italic { font-style: italic; }
    .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .shadow-xs { box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    
    .inventory-item-card {
        border-radius: 15px;
        border: 1px solid #f0f0f0;
        background: #fff;
        display: flex;
        flex-direction: column;
    }

    .inventory-item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
        border-color: #007bff22;
    }

    .transition-all { transition: all 0.3s ease; }
    
    .filter-pill {
        border-radius: 20px;
        padding: 6px 16px;
        font-weight: 600;
        font-size: 11px;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .filter-pill.active {
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transform: scale(1.05);
    }
    
    .filter-pill[data-filter-type="category"].active { background-color: #007bff; color: white !important; }
    .filter-pill[data-filter-type="brand"].active { background-color: #17a2b8; color: white !important; }

    .product-card-wrapper { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('scripts')
<script>
var _currentThresholdId = null;
var _deleteVariantId = null;

function openThresholdModal(id, name, pkgLabel, canSellTots, totUnit) {
  _currentThresholdId = id;
  if (!pkgLabel) pkgLabel = 'Crate';
  
  var threshold = parseInt($('[data-variant-id="' + id + '"]').attr('data-threshold')) || 10;

  $('#threshold-product-name').text(name);
  $('#threshold-value').val(threshold);
  
  // Update the dropdown option dynamically to match the product's packaging type
  $('#threshold-pkg-option').text(pkgLabel.toLowerCase() + 's').val('btls'); 
  
  // Show/Hide Glass/Tot option based on product capabilities
  if (canSellTots) {
      $('#threshold-glass-option').show().text((totUnit || 'glass') + 's').val('btls');
  } else {
      $('#threshold-glass-option').hide();
  }

  $('#threshold-unit').val('btls');
  
  $('#thresholdModal').modal('show');
}

function confirmDeleteStock(id, name) {
  _deleteVariantId = id;
  $('#deleteStockName').text(name);
  $('#deleteStockModal').modal('show');
}

$(document).ready(function () {

    // DELETE CONFIRM BUTTON
    $('#confirmDeleteStockBtn').on('click', function () {
        if (!_deleteVariantId) return;
        
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Removing...');
        
        $.ajax({
            url: '/bar/counter/counter-stock/' + _deleteVariantId,
            type: 'POST',
            data: {
                _method: 'DELETE',
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Remove the card from the DOM
                    $('[data-variant-id="' + _deleteVariantId + '"]').closest('.col-md-4, tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                    $('#deleteStockModal').modal('hide');
                    // Show brief success toast
                    $('body').append('<div id="del-toast" class="alert alert-success shadow" style="position:fixed;bottom:20px;right:20px;z-index:9999;min-width:220px;"><i class="fa fa-check-circle"></i> Removed from Counter Stock</div>');
                    setTimeout(function() { $('#del-toast').fadeOut(400, function() { $(this).remove(); }); }, 3000);
                }
            },
            error: function() {
                alert('Failed to remove. Please try again.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-trash"></i> Yes, Remove');
            }
        });
    });
    // 1. VIEW TOGGLE
    $('.view-btn').on('click', function() {
        const view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');

        if (view === 'grid') {
            $('#inventoryGrid').removeClass('d-none');
            $('#inventoryList').addClass('d-none');
        } else {
            $('#inventoryGrid').addClass('d-none');
            $('#inventoryList').removeClass('d-none');
        }
    });

    // 2. SEARCH & FILTER
    let activeCategory = 'all';
    let activeBrand = 'all';

    function applyFilters() {
        const searchTerm = $('#inventorySearch').val().toLowerCase();
        
        $('.product-card-wrapper').each(function() {
            const itemName = $(this).data('name');
            const itemCat = $(this).data('category');
            const itemBrand = $(this).data('brand');
            
            const matchesSearch = itemName.indexOf(searchTerm) > -1;
            const matchesCat = (activeCategory === 'all' || itemCat === activeCategory);
            const matchesBrand = (activeBrand === 'all' || itemBrand === activeBrand);
            
            if (matchesSearch && matchesCat && matchesBrand) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    $('#inventorySearch').on('input', applyFilters);
    
    $('.filter-pill').on('click', function() {
        const filter = $(this).data('filter');
        const type = $(this).data('filter-type');
        
        if (type === 'category') {
            activeCategory = filter;
            $('#categoryContainer .filter-pill[data-filter-type="category"]').removeClass('active');
        } else {
            activeBrand = filter;
            $('#categoryContainer .filter-pill[data-filter-type="brand"]').removeClass('active');
        }
        
        $(this).addClass('active');
        applyFilters();
    });

    // 3. THRESHOLD SAVE
    $('#saveThresholdBtn').on('click', function () {
        var val = parseInt($('#threshold-value').val(), 10);
        if (isNaN(val) || val < 1) return;
        
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '/bar/counter/counter-stock/threshold/' + _currentThresholdId,
            type: 'POST',
            data: {
                threshold: val,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('[data-variant-id="' + _currentThresholdId + '"]').attr('data-threshold', val);
                    updateThresholdVisual(_currentThresholdId, val, 'btls');
                    $('#thresholdModal').modal('hide');
                    location.reload();
                }
            },
            error: function() {
                alert('Failed to save. Please try again.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save');
            }
        });
    });

    function updateThresholdVisual(id, val, unit) {
        $(`#threshold-info-${id}`).html(`<i class="fa fa-bell"></i> Alert < ${val} ${unit}`).show();
    }

    // 4. LOAD LABELS
    $('.product-card-wrapper').each(function() {
        const id = $(this).data('variant-id');
        const threshold = $(this).data('threshold');
        if (id && threshold) {
            updateThresholdVisual(id, threshold, 'btls');
        }
    });
});
</script>
@endsection
