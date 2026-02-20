@extends('layouts.dashboard')

@section('title', 'Counter Stock')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cubes"></i> Counter Stock</h1>
    <p>View current counter inventory by category</p>
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
  <div class="col-md-4">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Stock Value</h4>
        <p><b>TSh {{ number_format($totalValue, 2) }}</b></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Counter Inventory</h3>
        <div>
          <a href="{{ route('bar.stock-transfers.history') }}" class="btn btn-info mr-2">
            <i class="fa fa-history"></i> Transfer History
          </a>
          <a href="{{ route('bar.stock-transfers.available') }}" class="btn btn-primary mr-2">
            <i class="fa fa-archive"></i> Request from Warehouse
          </a>
          <a href="{{ route('bar.counter.dashboard') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
          </a>
        </div>
      </div>

      <div class="tile-body">
        @if(count($variants) > 0)

          <!-- Category Tabs -->
          <ul class="nav counter-category-tabs mb-4" id="categoryTabs">
            <li class="nav-item">
              <a class="nav-link active" href="#" data-category="all">
                <i class="fa fa-th"></i> All
                <span class="badge badge-secondary ml-1">{{ count($variants) }}</span>
              </a>
            </li>
            @foreach($categories as $category)
              @php
                $catSlug  = \Illuminate\Support\Str::slug($category);
                $catCount = $variants->where('category', $category)->count();
              @endphp
              <li class="nav-item">
                <a class="nav-link" href="#" data-category="{{ $catSlug }}">
                  {{ ucfirst($category) }}
                  <span class="badge badge-secondary ml-1">{{ $catCount }}</span>
                </a>
              </li>
            @endforeach
          </ul>

          <!-- Product Grid -->
          <div class="row" id="stockContainer">
            @foreach($variants as $variant)
            @php
              $catSlug     = \Illuminate\Support\Str::slug($variant['category']);
              $qty         = $variant['quantity'];
              $ipp         = $variant['items_per_package'] ?? 1;  // items per crate/package
              $pkgLabel    = ucfirst($variant['packaging'] ?? 'Crate');
              $crates      = $ipp > 1 ? floor($qty / $ipp) : 0;
              $extraBottles= $ipp > 1 ? ($qty % $ipp) : $qty;
              $statusColor = $qty <= 0 ? 'danger' : ($variant['is_low_stock'] ? 'warning' : 'success');
              $borderHex   = $qty <= 0 ? '#dc3545' : ($variant['is_low_stock'] ? '#ffc107' : '#28a745');
              $headerClass = $qty <= 0 ? 'bg-danger text-white' : ($variant['is_low_stock'] ? 'bg-warning text-dark' : 'bg-success text-white');
            @endphp
            <div class="col-6 col-md-4 col-lg-3 mb-3 stock-card-wrap" data-category="{{ $catSlug }}">
              <div class="card h-100 shadow-sm border-{{ $statusColor }}" style="border-width: 2px;" id="stock-card-{{ $variant['id'] }}">

                <!-- Product Image -->
                <div class="product-img-wrap" style="height: 120px; overflow:hidden; background:#f8f9fa; display:flex; align-items:center; justify-content:center; border-bottom: 2px solid {{ $borderHex }}; position:relative;">
                  @if($variant['product_image'])
                    <img src="{{ asset('storage/' . $variant['product_image']) }}"
                         alt="{{ $variant['product_name'] }}"
                         style="max-height:120px; width:100%; object-fit:contain; padding:6px;"
                         onerror="this.src='{{ asset('default_images/default_drink.jpg') }}'">
                  @else
                    <img src="{{ asset('default_images/default_drink.jpg') }}"
                         alt="Default Drink"
                         style="max-height:120px; width:100%; object-fit:contain; padding:6px;">
                  @endif
                  <!-- Category overlay -->
                  <span class="cat-badge">{{ $variant['category'] }}</span>
                  <!-- Low-stock settings gear -->
                  <button class="btn-set-threshold" title="Set Low Stock Threshold"
                          data-id="{{ $variant['id'] }}"
                          data-name="{{ $variant['product_name'] }}"
                          onclick="openThresholdModal({{ $variant['id'] }}, '{{ addslashes($variant['product_name']) }}')">
                    <i class="fa fa-cog"></i>
                  </button>
                </div>

                <!-- Card Header -->
                @php
                  // Build full display title: "Bonite (Coca-Cola) (Fanta Pineapple)" or just "Wine Collection"
                  $displayTitle = $variant['product_name'];
                  if (!empty($variant['variant_name']) && $variant['variant_name'] !== $variant['product_name']) {
                      $displayTitle = $variant['product_name'] . ' (' . $variant['variant_name'] . ')';
                  }
                  $fullTooltip = $displayTitle . ' — ' . $variant['variant'];
                @endphp
                <div class="card-header {{ $headerClass }} p-2" style="border-bottom:none;">
                  <h6 class="card-title mb-0 font-weight-bold" title="{{ $fullTooltip }}"
                      style="font-size:0.8rem; line-height:1.3; word-break:break-word;">
                    {{ $displayTitle }}
                    @if($qty <= 0)
                      <i class="fa fa-times-circle float-right mt-1"></i>
                    @elseif($variant['is_low_stock'])
                      <i class="fa fa-exclamation-triangle float-right mt-1"></i>
                    @else
                      <i class="fa fa-check-circle float-right mt-1"></i>
                    @endif
                  </h6>
                  <small style="font-size:9px; opacity:0.8;">
                    <i class="fa fa-flask"></i> {{ $variant['variant'] }}
                  </small>
                </div>

                <!-- Card Body -->
                <div class="card-body p-2">

                  <!-- Quantity breakdown -->
                  <div class="qty-row mb-1" id="qty-display-{{ $variant['id'] }}">
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted"><i class="fa fa-cubes"></i> Qty:</small>
                      <span class="badge badge-{{ $statusColor }}" style="font-size:0.78rem;">
                        {{ number_format($qty) }} btl
                      </span>
                    </div>
                    @if($ipp > 1 && $qty > 0)
                    <div class="text-right mt-1">
                      <small class="text-muted" style="font-size:10px;">
                        @if($crates > 0)
                          <span class="text-dark font-weight-bold">{{ $crates }}</span> {{ $pkgLabel }}{{ $crates != 1 ? 's' : '' }}
                          @if($extraBottles > 0) + <span class="text-dark font-weight-bold">{{ $extraBottles }}</span> btl @endif
                        @else
                          <span class="text-dark font-weight-bold">{{ $extraBottles }}</span> btl
                        @endif
                      </small>
                    </div>
                    @endif
                  </div>

                  <!-- Prices -->
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted"><i class="fa fa-tag"></i> Bottle:</small>
                    <small class="font-weight-bold text-primary">TSh {{ number_format($variant['selling_price']) }}</small>
                  </div>

                  @if(!empty($variant['can_sell_in_tots']) && $variant['can_sell_in_tots'] && ($variant['selling_price_per_tot'] ?? 0) > 0)
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted"><i class="fa fa-glass"></i> Glass/Tot:</small>
                    <small class="font-weight-bold text-info">TSh {{ number_format($variant['selling_price_per_tot']) }}</small>
                  </div>
                  @endif

                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted"><i class="fa fa-money"></i> Value:</small>
                    <small class="font-weight-bold text-success">TSh {{ number_format($qty * $variant['selling_price']) }}</small>
                  </div>

                  <!-- Low-stock threshold indicator -->
                  <div class="threshold-info mt-1" id="threshold-info-{{ $variant['id'] }}" style="font-size:10px; color:#888;"></div>

                </div>

                <!-- Card Footer -->
                <div class="card-footer p-1 text-center" style="border-top:2px solid {{ $borderHex }}; background:#fafafa;">
                  @if($qty <= 0)
                    <small class="text-danger font-weight-bold"><i class="fa fa-times-circle"></i> Out of Stock</small>
                  @elseif($variant['is_low_stock'])
                    <small class="text-warning font-weight-bold"><i class="fa fa-exclamation-triangle"></i> Low Stock</small>
                  @else
                    <small class="text-success font-weight-bold"><i class="fa fa-check-circle"></i> In Stock</small>
                  @endif
                </div>

              </div>
            </div>
            @endforeach
          </div>

          <!-- Total Bar -->
          <div class="mt-3 p-3 rounded d-flex justify-content-between align-items-center"
               style="background: linear-gradient(135deg, #1a237e, #283593); color:white;">
            <h5 class="mb-0"><i class="fa fa-calculator"></i> Total Counter Stock Value</h5>
            <h4 class="mb-0 text-success font-weight-bold">TSh {{ number_format($totalValue, 2) }}</h4>
          </div>

        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No products currently in counter stock.
            <a href="{{ route('bar.counter.warehouse-stock') }}" class="alert-link">Request stock from warehouse</a>
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
      <div class="modal-header" style="background:#1565c0; color:white;">
        <h5 class="modal-title"><i class="fa fa-cog"></i> Low Stock Alert Threshold</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p id="threshold-product-name" class="font-weight-bold text-primary mb-2"></p>
        <label class="small">Alert me when stock falls below:</label>
        <div class="input-group input-group-sm mt-1">
          <input type="number" id="threshold-value" class="form-control" min="1" value="10" placeholder="e.g. 10">
          <div class="input-group-append">
            <span class="input-group-text">bottles</span>
          </div>
        </div>
        <small class="text-muted mt-1 d-block">Saved locally per product.</small>
      </div>
      <div class="modal-footer p-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="saveThresholdBtn">
          <i class="fa fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  /* Category tabs */
  .counter-category-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 12px;
  }
  .counter-category-tabs .nav-link {
    border-radius: 20px;
    color: #555;
    background: #f0f2f5;
    border: 1px solid #ddd;
    padding: 5px 14px;
    font-size: 0.82rem;
    font-weight: 600;
    transition: all 0.2s;
  }
  .counter-category-tabs .nav-link:hover {
    background: #e3f2fd;
    border-color: #90caf9;
    color: #1565c0;
  }
  .counter-category-tabs .nav-link.active {
    background: #1565c0;
    color: white !important;
    border-color: #1565c0;
  }

  /* Product image area */
  .product-img-wrap { position: relative; }
  .cat-badge {
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

  /* Gear icon button */
  .btn-set-threshold {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0,0,0,0.4);
    border: none;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
    padding: 0;
  }
  .btn-set-threshold:hover {
    background: rgba(21,101,192,0.85);
  }

  /* Card hover effect */
  .stock-card-wrap .card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  }
  .stock-card-wrap .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12) !important;
  }

  /* Quantity row subtle bg */
  .qty-row {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 4px 6px;
  }

  /* Fade animation */
  .stock-card-wrap { animation: fadeIn 0.25s ease; }
  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.97); }
    to   { opacity: 1; transform: scale(1); }
  }
</style>
@endpush

@section('scripts')
<script>
var _currentThresholdId = null;

// Open threshold modal
function openThresholdModal(id, name) {
  _currentThresholdId = id;
  var saved = localStorage.getItem('lowstock_threshold_' + id) || 10;
  $('#threshold-product-name').text(name);
  $('#threshold-value').val(saved);
  $('#thresholdModal').modal('show');
}

$(document).ready(function () {

  // Category tab filtering
  $('#categoryTabs .nav-link').on('click', function (e) {
    e.preventDefault();
    var cat = $(this).data('category');
    $('#categoryTabs .nav-link').removeClass('active');
    $(this).addClass('active');
    if (cat === 'all') {
      $('.stock-card-wrap').fadeIn(200);
    } else {
      $('.stock-card-wrap').hide();
      $('.stock-card-wrap[data-category="' + cat + '"]').fadeIn(200);
    }
  });

  // Save threshold
  $('#saveThresholdBtn').on('click', function () {
    var val = parseInt($('#threshold-value').val(), 10);
    if (isNaN(val) || val < 1) {
      alert('Please enter a valid number (minimum 1).');
      return;
    }
    localStorage.setItem('lowstock_threshold_' + _currentThresholdId, val);
    // Update visual feedback
    var info = $('#threshold-info-' + _currentThresholdId);
    info.html('<i class="fa fa-bell"></i> Alert below <b>' + val + '</b> btl').css('color', '#e65100');
    $('#thresholdModal').modal('hide');
  });

  // On load: apply saved thresholds to UI
  $('.stock-card-wrap').each(function () {
    var card = $(this).find('.card');
    var id   = card.attr('id') ? card.attr('id').replace('stock-card-', '') : null;
    if (!id) return;
    var saved = localStorage.getItem('lowstock_threshold_' + id);
    if (saved) {
      $('#threshold-info-' + id).html('<i class="fa fa-bell"></i> Alert below <b>' + saved + '</b> btl').css('color', '#e65100');
    }
  });
});
</script>
@endsection
