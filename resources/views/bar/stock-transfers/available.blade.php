@extends('layouts.dashboard')

@section('title', 'Available Products from Warehouse')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cubes"></i> Available Products from Warehouse</h1>
    <p>Browse and request stock transfers from warehouse to counter</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-transfers.index') }}">Stock Transfers</a></li>
    <li class="breadcrumb-item">Available Products</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Products Available in Warehouse</h3>
        <div>
          <a href="{{ route('bar.stock-transfers.create') }}" class="btn btn-secondary">
            <i class="fa fa-plus"></i> Manual Request
          </a>
          <a href="{{ route('bar.stock-transfers.index') }}" class="btn btn-info">
            <i class="fa fa-list"></i> View All Transfers
          </a>
        </div>
      </div>

      <div class="tile-body">
        @if($productsWithStock->count() > 0)
          <div class="row">
            @foreach($productsWithStock as $product)
              <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm" style="border-left: 4px solid #28a745;">
                  @if($product['image'])
                    <img src="{{ asset('storage/' . $product['image']) }}" class="card-img-top" alt="{{ $product['name'] }}" style="height: 150px; object-fit: cover; border-bottom: 1px solid #dee2e6;">
                  @else
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px; border-bottom: 1px solid #dee2e6;">
                      <i class="fa fa-cube fa-3x text-muted"></i>
                    </div>
                  @endif
                  <div class="card-body">
                    <h5 class="card-title mb-2">
                      <strong>{{ $product['name'] }}</strong>
                      @if($product['brand'] && strtolower(trim($product['brand'])) !== strtolower(trim($product['name'])))
                        <br><small class="text-muted">{{ $product['brand'] }}</small>
                      @endif
                    </h5>
                    
                    @if($product['description'])
                      <p class="card-text text-muted small mb-3">{{ Str::limit($product['description'], 80) }}</p>
                    @endif

                    <div class="mb-3">
                      <span class="badge badge-primary">
                        <i class="fa fa-tags"></i> {{ $product['total_variants'] }} Variant(s) Available
                      </span>
                    </div>

                    <hr>

                    <div class="variants-section">
                      <h6 class="mb-2"><i class="fa fa-list"></i> Available Variants:</h6>
                      @foreach($product['variants'] as $variant)
                        <div class="variant-card mb-3 p-3" style="background-color: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">
                          <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                              <strong>{{ $variant['measurement'] }}</strong>
                            </div>
                            @php
                              $packagingSingular = Str::singular(strtolower($variant['packaging']));
                              $packagingLabel = $variant['warehouse_packages'] != 1 ? $variant['packaging'] : $packagingSingular;
                            @endphp
                            <span class="badge badge-success">
                              {{ $variant['warehouse_packages'] }} {{ $packagingLabel }}
                            </span>
                          </div>
                          
                          <div class="row mb-2">
                            <div class="col-6">
                              <small class="text-muted">Stock:</small><br>
                              <strong>{{ number_format($variant['warehouse_quantity']) }} {{ $variant['unit_label'] ?? 'bottles' }}</strong>
                            </div>
                            <div class="col-6">
                              <small class="text-muted">Per {{ Str::singular($variant['packaging']) }}:</small><br>
                              <strong>{{ $variant['items_per_package'] }} {{ $variant['unit_label'] ?? 'bottles' }}</strong>
                            </div>
                          </div>

                          <div class="row mb-2">
                            <div class="col-6">
                              <small class="text-muted">Buying Price:</small><br>
                              <strong>TSh {{ number_format($variant['average_buying_price'], 2) }}</strong>
                            </div>
                            <div class="col-6">
                              <small class="text-muted">Selling Price:</small><br>
                              <strong class="text-success">TSh {{ number_format($variant['selling_price'], 2) }}</strong>
                            </div>
                          </div>
                          @php
                            $profitPerUnit = $variant['selling_price'] - $variant['average_buying_price'];
                            $expectedProfitPerPackage = $profitPerUnit * $variant['items_per_package'];
                          @endphp
                          <div class="row mb-2">
                            <div class="col-12">
                              <small class="text-muted">Expected Profit per {{ Str::singular($variant['packaging']) }}:</small><br>
                              <strong class="text-primary">TSh {{ number_format($expectedProfitPerPackage, 2) }}</strong>
                              <small class="text-muted">({{ number_format($profitPerUnit, 2) }} per bottle)</small>
                            </div>
                          </div>

                          <form action="{{ route('bar.stock-transfers.store') }}" method="POST" class="mt-2 transfer-form" 
                                data-variant="{{ $variant['measurement'] }} - {{ $variant['packaging'] }}" 
                                data-max="{{ $variant['warehouse_packages'] }}"
                                data-items-per-package="{{ $variant['items_per_package'] }}"
                                data-buying-price="{{ $variant['average_buying_price'] }}"
                                data-selling-price="{{ $variant['selling_price'] }}"
                                data-profit-per-unit="{{ $profitPerUnit }}">
                            @csrf
                            <input type="hidden" name="product_variant_id" value="{{ $variant['id'] }}">
                            <div class="input-group mb-2">
                              <input type="number" 
                                     name="quantity_requested" 
                                     class="form-control form-control-sm quantity-input" 
                                     value="1" 
                                     min="1" 
                                     max="{{ $variant['warehouse_packages'] }}"
                                     required
                                     style="max-width: 80px;">
                              @php
                                $packagingSingular = Str::singular(strtolower($variant['packaging']));
                              @endphp
                              <div class="input-group-append">
                                <span class="input-group-text" style="font-size: 0.875rem;">{{ $packagingSingular }}{{ $variant['warehouse_packages'] != 1 ? 's' : '' }}</span>
                              </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm btn-block request-btn">
                              <i class="fa fa-exchange"></i> Request Transfer
                            </button>
                          </form>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <div class="alert alert-info text-center">
            <i class="fa fa-info-circle fa-3x mb-3"></i>
            <h4>No Products Available</h4>
            <p>There are no products with stock available in the warehouse.</p>
            <p>Please create a <a href="{{ route('bar.stock-receipts.create') }}">stock receipt</a> to add products to the warehouse first.</p>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .variant-card {
    transition: all 0.3s ease;
  }
  .variant-card:hover {
    background-color: #e9ecef !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission with confirmation
    document.querySelectorAll('.transfer-form').forEach(function(form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const variant = form.dataset.variant;
        const maxPackages = parseInt(form.dataset.max);
        const itemsPerPackage = parseInt(form.dataset.itemsPerPackage);
        const buyingPrice = parseFloat(form.dataset.buyingPrice);
        const sellingPrice = parseFloat(form.dataset.sellingPrice);
        const profitPerUnit = parseFloat(form.dataset.profitPerUnit);
        const quantityInput = form.querySelector('.quantity-input');
        const quantity = parseInt(quantityInput.value) || 1;
        
        if (quantity > maxPackages) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid Quantity',
            text: `You can only request up to ${maxPackages} package(s). Available stock: ${maxPackages} packages.`,
          });
          return;
        }
        
        const totalUnits = quantity * itemsPerPackage;
        const expectedProfit = totalUnits * profitPerUnit;
        const expectedRevenue = totalUnits * sellingPrice;
        const totalCost = totalUnits * buyingPrice;
        
        Swal.fire({
          title: 'Confirm Transfer Request',
          html: `
            <div class="text-left">
              <p><strong>Variant:</strong> ${variant}</p>
              <p><strong>Quantity:</strong> ${quantity} package(s)</p>
              <p><strong>Total Bottles:</strong> ${totalUnits.toLocaleString()} bottle(s)</p>
              <hr>
              <p><strong>Expected Revenue:</strong> <span class="text-success">TSh ${expectedRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></p>
              <p><strong>Expected Profit:</strong> <span class="text-primary"><strong>TSh ${expectedProfit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></span></p>
              <hr>
              <p class="text-muted"><small>This will create a pending transfer request that requires approval.</small></p>
            </div>
          `,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#28a745',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, Request Transfer',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });
  });
</script>
@endpush

