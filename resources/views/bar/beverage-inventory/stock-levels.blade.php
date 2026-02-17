@extends('layouts.dashboard')

@section('title', 'Stock Levels')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-bar-chart"></i> Stock Levels</h1>
    <p>View detailed stock levels for all beverages</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.beverage-inventory.index') }}">Beverage Inventory</a></li>
    <li class="breadcrumb-item">Stock Levels</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Stock Levels</h3>
        <a href="{{ route('bar.beverage-inventory.index') }}" class="btn btn-secondary">
          <i class="fa fa-arrow-left"></i> Back
        </a>
      </div>

      <div class="tile-body">
        @if($variants->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="stockLevelsTable">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Variant</th>
                  <th>Warehouse Stock</th>
                  <th>Counter Stock</th>
                  <th>Total Stock</th>
                  <th>Buying Price</th>
                  <th>Selling Price</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($variants as $variant)
                  @php
                    $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
                    $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                    $warehouseQty = $warehouseStock ? $warehouseStock->quantity : 0;
                    $counterQty = $counterStock ? $counterStock->quantity : 0;
                    $totalStock = $warehouseQty + $counterQty;
                    $isLowStock = $totalStock < 10;
                    
                    // Calculate packaging information
                    $packagingType = strtolower($variant->packaging ?? 'packages');
                    $packagingTypeSingular = rtrim($packagingType, 's');
                    if ($packagingTypeSingular == 'boxe') {
                      $packagingTypeSingular = 'box';
                    }
                    $itemsPerPackage = $variant->items_per_package ?? 1;
                    $warehousePackages = $warehouseQty > 0 ? floor($warehouseQty / $itemsPerPackage) : 0;
                    $counterPackages = $counterQty > 0 ? floor($counterQty / $itemsPerPackage) : 0;
                    $totalPackages = $warehousePackages + $counterPackages;
                  @endphp
                  <tr class="{{ $isLowStock ? 'table-warning' : '' }}">
                    <td><strong>{{ $variant->product->name ?? 'N/A' }}</strong></td>
                    <td>{{ $variant->measurement }} - {{ $variant->packaging }}</td>
                    <td>
                      <div>{{ number_format($warehouseQty) }} bottle(s)</div>
                      @if($warehousePackages > 0 && $itemsPerPackage > 1)
                        <small class="text-muted">{{ number_format($warehousePackages) }} {{ $warehousePackages == 1 ? $packagingTypeSingular : $packagingType }}</small>
                      @endif
                    </td>
                    <td>
                      <div>{{ number_format($counterQty) }} bottle(s)</div>
                      @if($counterPackages > 0 && $itemsPerPackage > 1)
                        <small class="text-muted">{{ number_format($counterPackages) }} {{ $counterPackages == 1 ? $packagingTypeSingular : $packagingType }}</small>
                      @endif
                    </td>
                    <td>
                      <div><strong>{{ number_format($totalStock) }} bottle(s)</strong></div>
                      @if($totalPackages > 0 && $itemsPerPackage > 1)
                        <small class="text-muted"><strong>{{ number_format($totalPackages) }} {{ $totalPackages == 1 ? $packagingTypeSingular : $packagingType }}</strong></small>
                      @endif
                    </td>
                    <td>TSh {{ number_format($warehouseStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0, 2) }}</td>
                    <td>TSh {{ number_format($warehouseStock->selling_price ?? $variant->selling_price_per_unit ?? 0, 2) }}</td>
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
          <div class="mt-3">
            {{ $variants->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No beverage variants found.
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
  $(document).ready(function() {
    // Initialize DataTable only if available
    if (typeof $.fn.DataTable !== 'undefined') {
      try {
        $('#stockLevelsTable').DataTable({
          "paging": false,
          "info": false,
          "searching": true,
        });
      } catch(e) {
        console.warn('DataTable initialization failed:', e);
      }
    } else {
      console.warn('DataTable plugin not loaded, table will work without it');
    }
  });
</script>
@endpush

