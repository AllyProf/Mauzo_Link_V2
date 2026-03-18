@extends('layouts.dashboard')

@section('title', 'Warehouse Inventory')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-archive"></i> Warehouse Inventory</h1>
    <p>View available products from stock keeper</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Counter Dashboard</a></li>
    <li class="breadcrumb-item">Warehouse Stock</li>
  </ul>
</div>

<!-- Statistics Widgets -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-cubes fa-3x"></i>
            <div class="info">
                <h4>Warehouse Items</h4>
                <p><b>{{ count($variants) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-archive fa-3x"></i>
            <div class="info">
                <h4>Total Units</h4>
                <p><b>{{ number_format($variants->sum('warehouse_quantity')) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small success coloured-icon"><i class="icon fa fa-money fa-3x"></i>
            <div class="info">
                <h4>Est. Value</h4>
                <p><b>TSh {{ number_format($variants->sum(fn($v) => $v['warehouse_quantity'] * $v['selling_price'])) }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile shadow-sm border-0" style="border-radius: 15px;">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="tile-title mb-0">Available Stocks</h3>
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
          <a href="{{ route('bar.stock-transfers.available') }}" class="btn btn-primary mr-2 shadow-sm font-weight-bold">
            <i class="fa fa-plus-circle"></i> Request Transfer
          </a>
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
                      <input type="text" id="inventorySearch" class="form-control" placeholder="Search...">
                  </div>
              </div>
          </div>
          <div class="col-md-9">
              <label class="control-label font-weight-bold">Quick Filters (Categories & Brands)</label>
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
                      <div class="mx-2 border-right h-100" style="height: 24px !important;"></div>
                      @foreach($brands as $label)
                          <button class="btn btn-sm btn-outline-info filter-pill mr-1 mb-1" data-filter="{{ Str::slug($label) }}" data-filter-type="brand">
                              {{ $label }}
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
              $catSlug   = \Illuminate\Support\Str::slug($variant['category']);
              $brandSlug = \Illuminate\Support\Str::slug($variant['brand']);
              $qty       = $variant['warehouse_quantity'];
              $counter   = $variant['counter_quantity'];
              
              // Build full title
              $displayTitle = $variant['product_name'];
              if (!empty($variant['variant_name']) && $variant['variant_name'] !== $variant['product_name']) {
                  $displayTitle = $variant['product_name'] . ' (' . $variant['variant_name'] . ')';
              }
            @endphp
            <div class="col-md-4 mb-4 product-card-wrapper" 
                 data-category="{{ $catSlug }}" 
                 data-brand="{{ $brandSlug }}"
                 data-name="{{ strtolower($displayTitle) }} {{ strtolower($variant['brand'] ?? '') }} {{ strtolower($variant['category']) }}">
              
              <div class="tile p-3 h-100 mb-0 shadow-sm border-0 inventory-item-card transition-all" style="border-radius: 15px;">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                      <div class="flex-grow-1 pr-2">
                          <h6 class="font-weight-bold text-primary mb-1 line-clamp-1" title="{{ $displayTitle }}">{{ $displayTitle }}</h6>
                          <p class="text-muted smallest mb-0">{{ $variant['brand'] }} • {{ $variant['category'] }}</p>
                      </div>
                      <span class="badge badge-secondary px-2 py-1 smallest">{{ $variant['variant'] }}</span>
                  </div>

                  <div class="bg-light p-2 rounded mb-3">
                      <div class="d-flex justify-content-between small mb-1">
                          <span class="text-muted">Warehouse stock:</span>
                          <strong class="text-info">{{ number_format($qty) }} btl</strong>
                      </div>
                      <div class="d-flex justify-content-between smallest border-top pt-1 mt-1">
                          <span class="text-muted">Currently at counter:</span>
                          <strong class="text-{{ $counter > 0 ? 'success' : 'muted' }}">{{ number_format($counter) }} btl</strong>
                      </div>
                  </div>

                  <div class="row no-gutters mb-3 text-center bg-white rounded border py-2 shadow-xs">
                      <div class="col-6 border-right">
                          <div class="smallest text-muted">Buying Price</div>
                          <div class="font-weight-bold text-dark">Tsh {{ number_format($variant['buying_price']) }}</div>
                      </div>
                      <div class="col-6">
                          <div class="smallest text-muted">Selling Price</div>
                          <div class="font-weight-bold text-success">Tsh {{ number_format($variant['selling_price']) }}</div>
                      </div>
                  </div>

                  <div class="mt-auto d-flex justify-content-between align-items-center">
                    @if($variant['product_image'])
                        <img src="{{ asset('storage/' . $variant['product_image']) }}" class="rounded border shadow-xs" style="width: 40px; height: 40px; object-fit: contain; background: #fff;" onerror="this.style.display='none'">
                    @else
                        <div class="p-2 rounded border bg-white shadow-xs"><i class="fa fa-archive text-muted"></i></div>
                    @endif
                    <a href="{{ route('bar.stock-transfers.available') }}?variant_id={{ $variant['id'] }}" class="btn btn-primary btn-sm px-3 shadow-sm font-weight-bold">
                      <i class="fa fa-exchange mr-1"></i> REQUEST
                    </a>
                  </div>
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
                          <th>Warehouse Stock</th>
                          <th>Counter Stock</th>
                          <th>Buying Price</th>
                          <th>Selling Price</th>
                          <th>Action</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach($variants as $variant)
                        @php
                          $catSlug = Str::slug($variant['category']);
                          $brandSlug = Str::slug($variant['brand']);
                          $displayTitle = $variant['product_name'];
                          if (!empty($variant['variant_name']) && $variant['variant_name'] !== $variant['product_name']) {
                              $displayTitle = $variant['product_name'] . ' (' . $variant['variant_name'] . ')';
                          }
                        @endphp
                        <tr class="product-card-wrapper" 
                            data-category="{{ $catSlug }}" 
                            data-brand="{{ $brandSlug }}"
                            data-name="{{ strtolower($displayTitle) }} {{ strtolower($variant['brand'] ?? '') }} {{ strtolower($variant['category']) }}">
                            <td><strong class="text-primary">{{ $displayTitle }}</strong><br><small class="text-muted">{{ $variant['variant'] }}</small></td>
                            <td>
                                <strong>{{ $variant['brand'] }}</strong><br>
                                <span class="badge badge-light border smallest text-muted">{{ $variant['category'] }}</span>
                            </td>
                            <td><strong class="text-info">{{ number_format($variant['warehouse_quantity']) }} units</strong></td>
                            <td>
                                @if($variant['counter_quantity'] > 0)
                                    <span class="badge badge-success">{{ number_format($variant['counter_quantity']) }} units</span>
                                @else
                                    <span class="text-muted">0 units</span>
                                @endif
                            </td>
                            <td>TSh {{ number_format($variant['buying_price']) }}</td>
                            <td>TSh {{ number_format($variant['selling_price']) }}</td>
                            <td>
                                <a href="{{ route('bar.stock-transfers.available') }}?variant_id={{ $variant['id'] }}" class="btn btn-sm btn-primary shadow-sm font-weight-bold">
                                    <i class="fa fa-exchange"></i> Request
                                </a>
                            </td>
                        </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>

        @else
          <div class="alert alert-info py-5 text-center shadow-xs" style="border-radius: 15px;">
            <i class="fa fa-info-circle fa-3x mb-3"></i>
            <h4>No products available in warehouse at the moment.</h4>
          </div>
        @endif
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
$(document).ready(function () {
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
});
</script>
@endsection
