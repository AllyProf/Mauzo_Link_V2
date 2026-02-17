@extends('layouts.dashboard')

@section('title', 'Order Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-shopping-cart"></i> Order Details</h1>
    <p>View order information and items</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.orders.index') }}">Orders</a></li>
    <li class="breadcrumb-item">Order Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Order #{{ $order->order_number }}</h3>
        <a href="{{ route('bar.orders.index') }}" class="btn btn-secondary">
          <i class="fa fa-arrow-left"></i> Back to Orders
        </a>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        <div class="row">
          <div class="col-md-6">
            <h4>Order Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Order Number:</th>
                <td><strong>{{ $order->order_number }}</strong></td>
              </tr>
              <tr>
                <th>Status:</th>
                <td>
                  @if($order->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @elseif($order->status === 'preparing')
                    <span class="badge badge-info">Preparing</span>
                  @elseif($order->status === 'served')
                    <span class="badge badge-success">Served</span>
                  @elseif($order->status === 'cancelled')
                    <span class="badge badge-danger">Cancelled</span>
                  @endif
                </td>
              </tr>
              <tr>
                <th>Payment Status:</th>
                <td>
                  @if($order->payment_status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @elseif($order->payment_status === 'paid')
                    <span class="badge badge-success">Paid</span>
                  @elseif($order->payment_status === 'partial')
                    <span class="badge badge-info">Partial</span>
                  @elseif($order->payment_status === 'refunded')
                    <span class="badge badge-danger">Refunded</span>
                  @endif
                </td>
              </tr>
              <tr>
                <th>Table:</th>
                <td>
                  @if($order->table)
                    {{ $order->table->table_number }} - {{ $order->table->table_name ?? 'Table ' . $order->table->table_number }}
                  @else
                    @php
                      // Try to extract table number from notes for old orders
                      $tableNumber = null;
                      if ($order->notes) {
                        if (preg_match('/Table Number:\s*([^\|]+)/i', $order->notes, $matches)) {
                          $tableNumber = trim($matches[1]);
                        }
                      }
                    @endphp
                    @if($tableNumber)
                      {{ $tableNumber }}
                      <small class="text-muted d-block">(extracted from notes)</small>
                    @else
                      <span class="text-muted">No table assigned</span>
                    @endif
                  @endif
                </td>
              </tr>
              <tr>
                <th>Customer:</th>
                <td>
                  @if($order->customer_name)
                    {{ $order->customer_name }}<br>
                    @if($order->customer_phone)
                      <small class="text-muted">{{ $order->customer_phone }}</small>
                    @endif
                  @else
                    <span class="text-muted">Walk-in customer</span>
                  @endif
                </td>
              </tr>
              <tr>
                <th>Created By:</th>
                <td>{{ $order->createdBy->name ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Created Date:</th>
                <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
              </tr>
              @if($order->served_at)
              <tr>
                <th>Served By:</th>
                <td>{{ $order->servedBy->name ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Served Date:</th>
                <td>{{ $order->served_at->format('M d, Y H:i') }}</td>
              </tr>
              @endif
            </table>
          </div>
          <div class="col-md-6">
            <h4>Payment Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Total Amount:</th>
                <td><strong class="text-primary">TSh {{ number_format($order->total_amount, 2) }}</strong></td>
              </tr>
              <tr>
                <th>Paid Amount:</th>
                <td><strong class="text-success">TSh {{ number_format($order->paid_amount, 2) }}</strong></td>
              </tr>
              <tr>
                <th>Remaining:</th>
                <td><strong class="text-danger">TSh {{ number_format($order->remaining_amount, 2) }}</strong></td>
              </tr>
            </table>

            @if($order->notes)
              <h4>Notes</h4>
              <p>{{ $order->notes }}</p>
            @endif
          </div>
        </div>

        <hr>

        <h4>Order Items</h4>
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                <th>Variant</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->items as $index => $item)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $item->productVariant->product->name ?? 'N/A' }}</td>
                  <td>{{ $item->productVariant->measurement ?? '' }} - {{ $item->productVariant->packaging ?? '' }}</td>
                  <td>{{ $item->quantity }}</td>
                  <td>TSh {{ number_format($item->unit_price, 2) }}</td>
                  <td><strong>TSh {{ number_format($item->total_price, 2) }}</strong></td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5" class="text-right"><strong>Total:</strong></td>
                <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection



