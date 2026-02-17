@extends('layouts.dashboard')

@section('title', 'Stock Transfer Requests')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> Stock Transfer Requests</h1>
    <p>View your stock transfer requests</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Counter Dashboard</a></li>
    <li class="breadcrumb-item">Stock Transfer Requests</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">My Stock Transfer Requests</h3>
        <div>
          <a href="{{ route('bar.stock-transfers.available') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> New Request
          </a>
          <a href="{{ route('bar.counter.dashboard') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
          </a>
        </div>
      </div>

      <div class="tile-body">
        @if($transfers->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Transfer #</th>
                  <th>Product</th>
                  <th>Quantity Requested</th>
                  <th>Total Units</th>
                  <th>Status</th>
                  <th>Requested Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($transfers as $transfer)
                <tr>
                  <td><strong>{{ $transfer->transfer_number }}</strong></td>
                  <td>
                    @if($transfer->productVariant)
                      <strong>{{ $transfer->productVariant->product->name }}</strong><br>
                      <small class="text-muted">{{ $transfer->productVariant->measurement }}</small>
                    @else
                      <span class="text-muted">N/A</span>
                    @endif
                  </td>
                  <td>{{ number_format($transfer->quantity_requested) }} packages</td>
                  <td>{{ number_format($transfer->total_units) }} bottle(s)</td>
                  <td>
                    @if($transfer->status === 'pending')
                      <span class="badge badge-warning">Pending</span>
                    @elseif($transfer->status === 'approved')
                      <span class="badge badge-info">Approved</span>
                    @elseif($transfer->status === 'rejected')
                      <span class="badge badge-danger">Rejected</span>
                      @if($transfer->rejection_reason)
                        <br><small class="text-muted">{{ $transfer->rejection_reason }}</small>
                      @endif
                    @elseif($transfer->status === 'completed')
                      <span class="badge badge-success">Completed</span>
                    @endif
                  </td>
                  <td>{{ $transfer->created_at->format('M d, Y H:i') }}</td>
                  <td>
                    <a href="{{ route('bar.stock-transfers.show', $transfer) }}" class="btn btn-sm btn-info">
                      <i class="fa fa-eye"></i> View
                    </a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-center">
            {{ $transfers->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No stock transfer requests found.
            <a href="{{ route('bar.stock-transfers.available') }}" class="alert-link">Create a new request</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection








