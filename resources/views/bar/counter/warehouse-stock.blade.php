@extends('layouts.dashboard')

@section('title', 'Warehouse Stock')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-archive"></i> Warehouse Stock</h1>
    <p>View available products from stock keeper</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Counter Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-transfers.available') }}">Warehouse Stock</a></li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Available Products in Warehouse</h3>
        <div>
          <a href="{{ route('bar.stock-transfers.available') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Request Stock Transfer
          </a>
          <a href="{{ route('bar.counter.dashboard') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
          </a>
        </div>
      </div>

      <div class="tile-body">
        @if(count($variants) > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Variant</th>
                  <th>Warehouse Stock</th>
                  <th>Counter Stock</th>
                  <th>Buying Price</th>
                  <th>Selling Price</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($variants as $variant)
                <tr>
                  <td><strong>{{ $variant['product_name'] }}</strong></td>
                  <td>{{ $variant['variant'] }}</td>
                  <td>
                    <span class="badge badge-info">{{ number_format($variant['warehouse_quantity']) }} units</span>
                  </td>
                  <td>
                    @if($variant['counter_quantity'] > 0)
                      <span class="badge badge-success">{{ number_format($variant['counter_quantity']) }} units</span>
                    @else
                      <span class="badge badge-secondary">0 units</span>
                    @endif
                  </td>
                  <td>TSh {{ number_format($variant['buying_price'], 2) }}</td>
                  <td>TSh {{ number_format($variant['selling_price'], 2) }}</td>
                  <td>
                    <a href="{{ route('bar.stock-transfers.available') }}?variant_id={{ $variant['id'] }}" class="btn btn-sm btn-primary">
                      <i class="fa fa-exchange"></i> Request Transfer
                    </a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No products available in warehouse at the moment.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

