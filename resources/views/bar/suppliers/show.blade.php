@extends('layouts.dashboard')

@section('title', 'Supplier Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-truck"></i> Supplier Details</h1>
    <p>View supplier information and related data</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.suppliers.index') }}">Suppliers</a></li>
    <li class="breadcrumb-item">Supplier Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">{{ $supplier->company_name }}</h3>
        <div>
          <a href="{{ route('bar.suppliers.edit', $supplier) }}" class="btn btn-warning">
            <i class="fa fa-pencil"></i> Edit
          </a>
          <a href="{{ route('bar.suppliers.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
          </a>
        </div>
      </div>

      <div class="tile-body">
        <div class="row">
          <div class="col-md-6">
            <h4>Contact Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Company Name:</th>
                <td><strong>{{ $supplier->company_name }}</strong></td>
              </tr>
              <tr>
                <th>Contact Person:</th>
                <td>{{ $supplier->contact_person ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Phone:</th>
                <td>{{ $supplier->phone }}</td>
              </tr>
              <tr>
                <th>Email:</th>
                <td>{{ $supplier->email ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Status:</th>
                <td>
                  <span class="badge {{ $supplier->is_active ? 'badge-success' : 'badge-danger' }}">
                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <h4>Address Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Address:</th>
                <td>{{ $supplier->address ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>City:</th>
                <td>{{ $supplier->city ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Country:</th>
                <td>{{ $supplier->country ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Registered:</th>
                <td>{{ $supplier->created_at->format('M d, Y') }}</td>
              </tr>
            </table>
          </div>
        </div>

        @if($supplier->notes)
          <div class="row mt-3">
            <div class="col-md-12">
              <h4>Notes</h4>
              <p>{{ $supplier->notes }}</p>
            </div>
          </div>
        @endif

        <div class="row mt-4">
          <div class="col-md-12">
            <h4>Related Products</h4>
            @if($supplier->products->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Product Name</th>
                      <th>Brand</th>
                      <th>Category</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($supplier->products as $product)
                      <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->brand ?? 'N/A' }}</td>
                        <td>{{ $product->category ?? 'N/A' }}</td>
                        <td>
                          <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                          </span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted">No products from this supplier yet.</p>
            @endif
          </div>
        </div>

        <div class="row mt-4">
          <div class="col-md-12">
            <h4>Stock Receipts</h4>
            @if($supplier->stockReceipts->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Receipt Number</th>
                      <th>Product</th>
                      <th>Quantity</th>
                      <th>Total Cost</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($supplier->stockReceipts->take(10) as $receipt)
                      <tr>
                        <td>{{ $receipt->receipt_number }}</td>
                        <td>{{ $receipt->productVariant->product->name ?? 'N/A' }}</td>
                        <td>{{ $receipt->total_units }} units</td>
                        <td>TSh {{ number_format($receipt->total_buying_cost, 2) }}</td>
                        <td>{{ $receipt->received_date->format('M d, Y') }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted">No stock receipts from this supplier yet.</p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
