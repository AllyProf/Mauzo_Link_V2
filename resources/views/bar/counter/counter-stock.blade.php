@extends('layouts.dashboard')

@section('title', 'Counter Stock')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cubes"></i> Counter Stock</h1>
    <p>View current counter inventory</p>
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
          <div class="row">
            @foreach($variants as $variant)
            <div class="col-md-6 col-lg-4 mb-4">
              <div class="card h-100 shadow-sm {{ $variant['is_low_stock'] ? 'border-warning' : 'border-success' }}" style="transition: transform 0.2s; border-width: 2px;">
                @if($variant['product_image'])
                  <img src="{{ asset('storage/' . $variant['product_image']) }}" class="card-img-top" alt="{{ $variant['product_name'] }}" style="height: 180px; object-fit: cover; border-bottom: 2px solid {{ $variant['is_low_stock'] ? '#ffc107' : '#28a745' }};">
                @else
                  <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px; border-bottom: 2px solid {{ $variant['is_low_stock'] ? '#ffc107' : '#28a745' }};">
                    <i class="fa fa-cube fa-4x text-muted"></i>
                  </div>
                @endif
                <div class="card-header {{ $variant['is_low_stock'] ? 'bg-warning text-dark' : 'bg-success text-white' }}" style="border-bottom: none;">
                  <h5 class="card-title mb-1">
                    <strong>{{ $variant['product_name'] }}</strong>
                    @if($variant['is_low_stock'])
                      <span class="badge badge-danger float-right"><i class="fa fa-exclamation-triangle"></i></span>
                    @else
                      <span class="badge badge-light float-right"><i class="fa fa-check-circle"></i></span>
                    @endif
                  </h5>
                  <small class="d-block">Measurement: {{ $variant['variant'] }}</small>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-muted"><i class="fa fa-cubes"></i> Quantity:</span>
                      <span class="badge badge-{{ $variant['is_low_stock'] ? 'warning' : 'success' }} badge-lg" style="font-size: 1.1rem; padding: 0.5rem 0.75rem;">
                        {{ number_format($variant['quantity']) }} bottle(s)
                      </span>
                    </div>
                  </div>
                  <hr>
                  <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted"><i class="fa fa-tag"></i> Selling Price:</span>
                      <span class="text-primary font-weight-bold">TSh {{ number_format($variant['selling_price'], 2) }}</span>
                    </div>
                  </div>
                  <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted"><i class="fa fa-money"></i> Stock Value:</span>
                      <span class="text-success font-weight-bold" style="font-size: 1.1rem;">TSh {{ number_format($variant['quantity'] * $variant['selling_price'], 2) }}</span>
                    </div>
                  </div>
                </div>
                <div class="card-footer bg-light text-center" style="border-top: 2px solid {{ $variant['is_low_stock'] ? '#ffc107' : '#28a745' }};">
                  @if($variant['is_low_stock'])
                    <span class="badge badge-warning badge-lg"><i class="fa fa-exclamation-triangle"></i> Low Stock - Request More</span>
                  @else
                    <span class="badge badge-success badge-lg"><i class="fa fa-check-circle"></i> In Stock</span>
                  @endif
                </div>
              </div>
            </div>
            @endforeach
          </div>
          <div class="mt-3 p-3 bg-info text-white rounded">
            <h5 class="mb-0">Total Stock Value: <strong>TSh {{ number_format($totalValue, 2) }}</strong></h5>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No products in counter stock. 
            <a href="{{ route('bar.counter.warehouse-stock') }}" class="alert-link">Request stock from warehouse</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  }
  
  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
  }
  
  .card-img-top {
    transition: transform 0.3s ease-in-out;
  }
  
  .card:hover .card-img-top {
    transform: scale(1.05);
  }
  
  .badge-lg {
    font-size: 0.9rem;
    padding: 0.4rem 0.8rem;
  }
</style>
@endpush
@endsection

