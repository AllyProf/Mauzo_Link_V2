@extends('layouts.dashboard')

@section('title', 'Payment Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Payment Details</h1>
    <p>View payment information</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item">Payment Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Payment #{{ $payment->payment_number }}</h3>
        <a href="{{ route('bar.payments.index') }}" class="btn btn-secondary">
          <i class="fa fa-arrow-left"></i> Back
        </a>
      </div>

      <div class="tile-body">
        <div class="row">
          <div class="col-md-6">
            <h4>Payment Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Payment Number:</th>
                <td><strong>{{ $payment->payment_number }}</strong></td>
              </tr>
              <tr>
                <th>Amount:</th>
                <td><strong class="text-success">TSh {{ number_format($payment->amount, 2) }}</strong></td>
              </tr>
              <tr>
                <th>Payment Method:</th>
                <td>
                  <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                </td>
              </tr>
              <tr>
                <th>Status:</th>
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
              </tr>
              <tr>
                <th>Processed By:</th>
                <td>{{ $payment->processedBy->name ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Payment Date:</th>
                <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <h4>Order Information</h4>
            @if($payment->order)
              <table class="table table-borderless">
                <tr>
                  <th width="40%">Order Number:</th>
                  <td>
                    <a href="{{ route('bar.orders.show', $payment->order) }}">
                      <strong>{{ $payment->order->order_number }}</strong>
                    </a>
                  </td>
                </tr>
                <tr>
                  <th>Order Status:</th>
                  <td>
                    <span class="badge badge-info">{{ ucfirst($payment->order->status) }}</span>
                  </td>
                </tr>
                <tr>
                  <th>Payment Status:</th>
                  <td>
                    @if($payment->order->payment_status === 'paid')
                      <span class="badge badge-success">Paid</span>
                    @elseif($payment->order->payment_status === 'pending')
                      <span class="badge badge-warning">Pending</span>
                    @else
                      <span class="badge badge-info">{{ ucfirst($payment->order->payment_status) }}</span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <th>Total Amount:</th>
                  <td><strong>TSh {{ number_format($payment->order->total_amount, 2) }}</strong></td>
                </tr>
                <tr>
                  <th>Paid Amount:</th>
                  <td><strong>TSh {{ number_format($payment->order->paid_amount, 2) }}</strong></td>
                </tr>
                <tr>
                  <th>Remaining:</th>
                  <td><strong>TSh {{ number_format($payment->order->total_amount - $payment->order->paid_amount, 2) }}</strong></td>
                </tr>
              </table>
            @else
              <p class="text-muted">No order associated with this payment.</p>
            @endif
          </div>
        </div>

        @if($payment->notes)
          <div class="row mt-3">
            <div class="col-md-12">
              <h4>Notes</h4>
              <p>{{ $payment->notes }}</p>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection







