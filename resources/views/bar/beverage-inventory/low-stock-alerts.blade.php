@extends('layouts.dashboard')

@section('title', 'Low Stock Alerts')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exclamation-triangle"></i> Low Stock Alerts</h1>
    <p>Beverages with low stock levels</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.beverage-inventory.index') }}">Beverage Inventory</a></li>
    <li class="breadcrumb-item">Low Stock Alerts</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Low Stock Items (Less than 10 bottle(s))</h3>
        <a href="{{ route('bar.beverage-inventory.index') }}" class="btn btn-secondary">
          <i class="fa fa-arrow-left"></i> Back
        </a>
      </div>

      <div class="tile-body">
        @if($variants->count() > 0)
          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> 
            <strong>{{ $variants->count() }}</strong> beverage variant(s) have low stock levels and need to be restocked.
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-bordered table-warning" id="lowStockTable">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Variant</th>
                  <th>Warehouse Stock</th>
                  <th>Counter Stock</th>
                  <th>Total Stock</th>
                  <th>Action</th>
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
                  <tr>
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
                      <div><strong class="text-danger">{{ number_format($totalStock) }} bottle(s)</strong></div>
                      @if($totalPackages > 0 && $itemsPerPackage > 1)
                        <small class="text-muted"><strong>{{ number_format($totalPackages) }} {{ $totalPackages == 1 ? $packagingTypeSingular : $packagingType }}</strong></small>
                      @endif
                    </td>
                    <td>
                      <a href="{{ route('bar.stock-receipts.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> Restock
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> 
            <strong>Great!</strong> All beverages have adequate stock levels. No low stock alerts at this time.
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
        $('#lowStockTable').DataTable({
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

