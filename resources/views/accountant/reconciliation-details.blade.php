@extends('layouts.dashboard')

@section('title', 'Reconciliation Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-balance-scale"></i> Reconciliation Details</h1>
    <p>Detailed view of waiter reconciliation</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accountant.dashboard') }}">Accountant</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accountant.reconciliations') }}">Reconciliations</a></li>
    <li class="breadcrumb-item">Details</li>
  </ul>
</div>

<!-- Reconciliation Summary -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Reconciliation Summary</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-3">
            <strong>Date:</strong><br>
            {{ $reconciliation->reconciliation_date->format('F d, Y') }}
          </div>
          <div class="col-md-3">
            <strong>Waiter:</strong><br>
            {{ $reconciliation->waiter->full_name }}<br>
            <small class="text-muted">{{ $reconciliation->waiter->email }}</small>
          </div>
          <div class="col-md-3">
            <strong>Status:</strong><br>
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
            @if($reconciliation->verifiedBy)
              <strong>Verified By:</strong><br>
              {{ $reconciliation->verifiedBy->full_name }}<br>
              <small class="text-muted">{{ $reconciliation->verified_at->format('M d, Y H:i') }}</small>
            @endif
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-md-3">
            <div class="widget-small primary coloured-icon">
              <i class="icon fa fa-money fa-3x"></i>
              <div class="info">
                <h4>Expected Amount</h4>
                <p><b>TSh {{ number_format($reconciliation->expected_amount, 0) }}</b></p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="widget-small success coloured-icon">
              <i class="icon fa fa-check-circle fa-3x"></i>
              <div class="info">
                <h4>Submitted Amount</h4>
                <p><b>TSh {{ number_format($reconciliation->submitted_amount, 0) }}</b></p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="widget-small {{ $reconciliation->difference >= 0 ? 'success' : 'danger' }} coloured-icon">
              <i class="icon fa fa-balance-scale fa-3x"></i>
              <div class="info">
                <h4>Difference</h4>
                <p><b>{{ $reconciliation->difference >= 0 ? '+' : '' }}TSh {{ number_format($reconciliation->difference, 0) }}</b></p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="widget-small info coloured-icon">
              <i class="icon fa fa-bank fa-3x"></i>
              <div class="info">
                <h4>Cash Collected</h4>
                <p><b>TSh {{ number_format($reconciliation->cash_collected, 0) }}</b></p>
                <small>Mobile: TSh {{ number_format($reconciliation->mobile_money_collected, 0) }}</small>
              </div>
            </div>
          </div>
        </div>
        @if($reconciliation->notes)
          <hr>
          <div class="row">
            <div class="col-md-12">
              <strong>Notes:</strong><br>
              <p>{{ $reconciliation->notes }}</p>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Orders in Reconciliation -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Orders in This Reconciliation</h3>
      <div class="tile-body">
        @if($reconciliation->orders->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date & Time</th>
                  <th>Bar Items (Drinks)</th>
                  <th>Food Items</th>
                  <th>Bar Amount</th>
                  <th>Food Amount</th>
                  <th>Total Amount</th>
                  <th>Payment Method</th>
                  <th>Payment Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($reconciliation->orders as $order)
                <tr>
                  <td><strong>{{ $order->order_number }}</strong></td>
                  <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                  <td>
                    @if($order->items && $order->items->count() > 0)
                      @foreach($order->items as $item)
                        <span class="badge badge-primary">{{ $item->quantity }}x {{ $item->productVariant->product->name ?? 'N/A' }}</span>
                      @endforeach
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($order->kitchenOrderItems && $order->kitchenOrderItems->count() > 0)
                      @foreach($order->kitchenOrderItems as $item)
                        <span class="badge badge-info">{{ $item->quantity }}x {{ $item->food_item_name }}</span>
                      @endforeach
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($order->items && $order->items->count() > 0)
                      <strong>TSh {{ number_format($order->items->sum('total_price'), 0) }}</strong>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($order->kitchenOrderItems && $order->kitchenOrderItems->count() > 0)
                      <strong>TSh {{ number_format($order->kitchenOrderItems->sum('total_price'), 0) }}</strong>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td><strong>TSh {{ number_format($order->total_amount, 0) }}</strong></td>
                  <td>
                    @if($order->payment_method)
                      <span class="badge badge-{{ $order->payment_method === 'cash' ? 'warning' : 'success' }}">
                        {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                      </span>
                    @else
                      <span class="badge badge-secondary">Not Set</span>
                    @endif
                  </td>
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
                  <th colspan="4">Total</th>
                  <th>TSh {{ number_format($reconciliation->orders->sum(function($order) { return $order->items ? $order->items->sum('total_price') : 0; }), 0) }}</th>
                  <th>TSh {{ number_format($reconciliation->orders->sum(function($order) { return $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0; }), 0) }}</th>
                  <th>TSh {{ number_format($reconciliation->orders->sum('total_amount'), 0) }}</th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No orders found in this reconciliation.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row mt-3">
  <div class="col-md-12">
    <a href="{{ route('accountant.reconciliations') }}" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Back to Reconciliations
    </a>
  </div>
</div>
@endsection




