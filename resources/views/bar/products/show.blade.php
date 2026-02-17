@extends('layouts.dashboard')

@section('title', 'Product Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cube"></i> Product Details</h1>
    <p>View product information and variants</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.products.index') }}">Products</a></li>
    <li class="breadcrumb-item">Product Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">{{ $product->name }}</h3>
        <div>
          <a href="{{ route('bar.products.edit', $product) }}" class="btn btn-warning">
            <i class="fa fa-pencil"></i> Edit
          </a>
          <a href="{{ route('bar.products.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
          </a>
        </div>
      </div>

      <div class="tile-body">
        <div class="row">
          <div class="col-md-6">
            <h4>Product Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Product Name:</th>
                <td><strong>{{ $product->name }}</strong></td>
              </tr>
              <tr>
                <th>Brand:</th>
                <td>{{ $product->brand ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Category:</th>
                <td>{{ $product->category ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Supplier:</th>
                <td>{{ $product->supplier->company_name ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Barcode:</th>
                <td>{{ $product->barcode ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Status:</th>
                <td>
                  <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">
                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            @if($product->image)
              <div class="text-right mb-3">
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded shadow-sm" style="max-height: 300px; max-width: 100%; object-fit: contain; border: 1px solid #dee2e6;">
              </div>
            @endif
            @if($product->description)
              <h4>Description</h4>
              <p>{{ $product->description }}</p>
            @endif
          </div>
        </div>

        <div class="row mt-4">
          <div class="col-md-12">
            <h4>Product Variants</h4>
            @if($product->variants->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover table-bordered">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Measurement</th>
                      <th>Packaging</th>
                      <th>Items per Package</th>
                      <th>Warehouse Stock</th>
                      <th>Counter Stock</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($product->variants as $variant)
                      @php
                        $warehouseQty = $variant->warehouseStock->quantity ?? 0;
                        $counterQty = $variant->counterStock->quantity ?? 0;
                        $warehousePackages = $warehouseQty > 0 ? floor($warehouseQty / $variant->items_per_package) : 0;
                        $counterPackages = $counterQty > 0 ? floor($counterQty / $variant->items_per_package) : 0;
                        $packagingType = strtolower($variant->packaging ?? 'packages');
                        $packagingTypeSingular = rtrim($packagingType, 's');
                        if ($packagingTypeSingular == 'boxe') {
                          $packagingTypeSingular = 'box';
                        }
                      @endphp
                      <tr>
                        <td><strong>{{ $variant->name }}</strong></td>
                        <td>{{ $variant->measurement }}</td>
                        <td>{{ $variant->packaging }}</td>
                        <td>{{ $variant->items_per_package }}</td>
                        <td>
                          @if($warehouseQty > 0)
                            <div>{{ number_format($warehouseQty) }} bottle(s)</div>
                            @if($warehousePackages > 0 && $variant->items_per_package > 1)
                              <small class="text-muted">
                                {{ number_format($warehousePackages) }} {{ $warehousePackages == 1 ? $packagingTypeSingular : $packagingType }}
                              </small>
                            @endif
                          @else
                            0 bottle(s)
                          @endif
                        </td>
                        <td>
                          @if($counterQty > 0)
                            <div>{{ number_format($counterQty) }} bottle(s)</div>
                            @if($counterPackages > 0 && $variant->items_per_package > 1)
                              <small class="text-muted">
                                {{ number_format($counterPackages) }} {{ $counterPackages == 1 ? $packagingTypeSingular : $packagingType }}
                              </small>
                            @endif
                          @else
                            0 bottle(s)
                          @endif
                        </td>
                        <td>
                          <span class="badge {{ $variant->is_active ? 'badge-success' : 'badge-danger' }}">
                            {{ $variant->is_active ? 'Active' : 'Inactive' }}
                          </span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted">No variants found for this product.</p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
