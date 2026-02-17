@extends('layouts.dashboard')

@section('title', 'Payments')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Payments</h1>
    <p>View all bar payments</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Payments</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Payments</h3>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          {{ session('error') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        @if($payments->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="paymentsTable">
              <thead>
                <tr>
                  <th>Payment #</th>
                  <th>Order #</th>
                  <th>Amount</th>
                  <th>Payment Method</th>
                  <th>Status</th>
                  <th>Processed By</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($payments as $payment)
                  <tr>
                    <td><strong>{{ $payment->payment_number }}</strong></td>
                    <td>
                      @if($payment->order)
                        <a href="{{ route('bar.orders.show', $payment->order) }}">{{ $payment->order->order_number }}</a>
                      @else
                        <span class="text-muted">N/A</span>
                      @endif
                    </td>
                    <td><strong>TSh {{ number_format($payment->amount, 2) }}</strong></td>
                    <td>
                      <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                    </td>
                    <td>
                      @if($payment->status === 'completed')
                        <span class="badge badge-success">Completed</span>
                      @elseif($payment->status === 'pending')
                        <span class="badge badge-warning">Pending</span>
                      @elseif($payment->status === 'refunded')
                        <span class="badge badge-danger">Refunded</span>
                      @else
                        <span class="badge badge-info">{{ ucfirst($payment->status) }}</span>
                      @endif
                    </td>
                    <td>{{ $payment->processedBy->name ?? 'N/A' }}</td>
                    <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      <a href="{{ route('bar.payments.show', $payment) }}" class="btn btn-info btn-sm">
                        <i class="fa fa-eye"></i> View
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $payments->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No payments found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- Data table plugin-->
<script type="text/javascript" src="{{ asset('js/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#paymentsTable').DataTable({
      "paging": false,
      "info": false,
      "searching": true,
    });
  });
</script>
@endpush








