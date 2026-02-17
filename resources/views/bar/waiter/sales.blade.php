@extends('layouts.dashboard')

@section('title', 'My Sales Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-line-chart"></i> My Sales Dashboard</h1>
    <p>View your daily sales and submit reconciliation</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.waiter.dashboard') }}">Waiter Dashboard</a></li>
    <li class="breadcrumb-item">Sales</li>
  </ul>
</div>

<!-- Date Selector -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('bar.waiter.sales') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="date" class="mr-2">Select Date:</label>
          <input type="date" name="date" id="date" class="form-control" value="{{ $date }}" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> View Sales
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Sales Summary Cards -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Sales</h4>
        <p><b>TSh {{ number_format($totalSales, 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-shopping-cart fa-3x"></i>
      <div class="info">
        <h4>Total Orders</h4>
        <p><b>{{ $totalOrders }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-bank fa-3x"></i>
      <div class="info">
        <h4>Cash Collected</h4>
        <p><b>TSh {{ number_format($cashCollected, 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-mobile fa-3x"></i>
      <div class="info">
        <h4>Mobile Money</h4>
        <p><b>TSh {{ number_format($mobileMoneyCollected, 0) }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Reconciliation Status -->
@if($reconciliation)
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Reconciliation Status</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-3">
            <strong>Status:</strong> 
            @if($reconciliation->status === 'verified')
              <span class="badge badge-success">Verified</span>
            @elseif($reconciliation->status === 'submitted')
              <span class="badge badge-info">Submitted</span>
            @elseif($reconciliation->status === 'disputed')
              <span class="badge badge-danger">Disputed</span>
            @else
              <span class="badge badge-warning">Pending</span>
            @endif
          </div>
          <div class="col-md-3">
            <strong>Expected Amount:</strong> TSh {{ number_format($reconciliation->expected_amount, 0) }}
          </div>
          <div class="col-md-3">
            <strong>Submitted Amount:</strong> TSh {{ number_format($reconciliation->submitted_amount, 0) }}
          </div>
          <div class="col-md-3">
            <strong>Difference:</strong> 
            <span class="{{ $reconciliation->difference >= 0 ? 'text-success' : 'text-danger' }}">
              TSh {{ number_format($reconciliation->difference, 0) }}
            </span>
          </div>
        </div>
        @if($reconciliation->verified_at)
          <div class="mt-2">
            <small class="text-muted">
              Verified by: {{ $reconciliation->verifiedBy->full_name ?? 'N/A' }} on 
              {{ $reconciliation->verified_at->format('M d, Y H:i') }}
            </small>
          </div>
        @endif
        @if($reconciliation->notes)
          <div class="mt-2">
            <strong>Notes:</strong> {{ $reconciliation->notes }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endif

<!-- Orders List -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Orders for {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</h3>
      <div class="tile-body">
        @if($orders->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Time</th>
                  <th>Items</th>
                  <th>Table</th>
                  <th>Payment Method</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($orders as $order)
                <tr>
                  <td><strong>{{ $order->order_number }}</strong></td>
                  <td>{{ $order->created_at->format('H:i') }}</td>
                  <td>
                    @foreach($order->items as $item)
                      <span class="badge badge-secondary">{{ $item->quantity }}x {{ $item->productVariant->product->name ?? 'N/A' }}</span>
                    @endforeach
                    @foreach($order->kitchenOrderItems as $item)
                      <span class="badge badge-info">{{ $item->quantity }}x {{ $item->food_item_name }}</span>
                    @endforeach
                  </td>
                  <td>{{ $order->table ? $order->table->table_number : 'N/A' }}</td>
                  <td>
                    @if($order->payment_method)
                      <span class="badge badge-{{ $order->payment_method === 'cash' ? 'warning' : 'success' }}">
                        {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                      </span>
                    @else
                      <span class="badge badge-secondary">Not Set</span>
                    @endif
                  </td>
                  <td><strong>TSh {{ number_format($order->total_amount, 0) }}</strong></td>
                  <td>
                    @if($order->payment_status === 'paid')
                      <span class="badge badge-success">Paid</span>
                    @else
                      <span class="badge badge-warning">Pending</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="5" class="text-right">Total:</th>
                  <th>TSh {{ number_format($totalSales, 0) }}</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No orders found for this date.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Submit Reconciliation -->
@if(!$reconciliation || $reconciliation->status === 'pending')
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Submit Reconciliation</h3>
      <div class="tile-body">
        <form id="submit-reconciliation-form">
          @csrf
          <input type="hidden" name="date" value="{{ $date }}">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Expected Amount (Total Sales)</label>
                <input type="text" class="form-control" value="TSh {{ number_format($expectedAmount, 0) }}" readonly>
                <small class="form-text text-muted">This is calculated from your orders</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Amount You Are Submitting *</label>
                <input type="number" name="submitted_amount" id="submitted_amount" class="form-control" 
                       step="0.01" min="0" value="{{ $expectedAmount }}" required>
                <small class="form-text text-muted">Enter the actual amount you collected</small>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes..."></textarea>
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fa fa-check"></i> Submit Reconciliation
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // Submit reconciliation
  $('#submit-reconciliation-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const submittedAmount = parseFloat($('#submitted_amount').val());
    const expectedAmount = {{ $expectedAmount }};
    const difference = submittedAmount - expectedAmount;
    
    Swal.fire({
      title: 'Submit Reconciliation?',
      html: `
        <div class="text-left">
          <p><strong>Expected Amount:</strong> TSh ${expectedAmount.toLocaleString()}</p>
          <p><strong>Submitted Amount:</strong> TSh ${submittedAmount.toLocaleString()}</p>
          <p><strong>Difference:</strong> 
            <span class="${difference >= 0 ? 'text-success' : 'text-danger'}">
              TSh ${difference.toLocaleString()}
            </span>
          </p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Submit',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '{{ route("bar.waiter.submit-reconciliation") }}',
          method: 'POST',
          data: formData,
          success: function(response) {
            if (response.success) {
              Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Reconciliation submitted successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to submit reconciliation';
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error
            });
          }
        });
      }
    });
  });
});
</script>
@endpush





