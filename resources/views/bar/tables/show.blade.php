@extends('layouts.dashboard')

@section('title', 'Table Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-table"></i> Table Details</h1>
    <p>View table information and orders</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.tables.index') }}">Tables</a></li>
    <li class="breadcrumb-item">Table Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Table Information</h3>
        <div>
          <a href="{{ route('bar.tables.edit', $table) }}" class="btn btn-warning">
            <i class="fa fa-pencil"></i> Edit
          </a>
          <a href="{{ route('bar.tables.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
          </a>
        </div>
      </div>

      <div class="tile-body">
        <div class="row">
          <div class="col-md-6">
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <th width="40%">Table Number</th>
                  <td><strong>{{ $table->table_number }}</strong></td>
                </tr>
                <tr>
                  <th>Table Name</th>
                  <td>{{ $table->table_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Capacity</th>
                  <td>{{ $table->capacity }} seats</td>
                </tr>
                <tr>
                  <th>Location</th>
                  <td>{{ $table->location ?? 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Currently Occupied</th>
                  <td>
                    <span class="badge {{ $table->current_people > 0 ? 'badge-info' : 'badge-secondary' }}">
                      {{ $table->current_people }} {{ $table->current_people == 1 ? 'person' : 'people' }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <th>Remaining Seats</th>
                  <td>
                    <span class="badge {{ $table->remaining_capacity > 0 ? 'badge-success' : 'badge-danger' }}">
                      {{ $table->remaining_capacity }} {{ $table->remaining_capacity == 1 ? 'seat' : 'seats' }} available
                    </span>
                  </td>
                </tr>
                <tr>
                  <th>Active</th>
                  <td>
                    <span class="badge {{ $table->is_active ? 'badge-success' : 'badge-danger' }}">
                      {{ $table->is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                </tr>
                @if($table->notes)
                <tr>
                  <th>Notes</th>
                  <td>{{ $table->notes }}</td>
                </tr>
                @endif
                <tr>
                  <th>Created At</th>
                  <td>{{ $table->created_at->format('M d, Y H:i') }}</td>
                </tr>
                <tr>
                  <th>Last Updated</th>
                  <td>{{ $table->updated_at->format('M d, Y H:i') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <hr>

        <h5 class="mt-4">Recent Orders</h5>
        @if($table->orders->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Order Number</th>
                  <th>Customer</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                  <th>Payment Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($table->orders as $order)
                  <tr>
                    <td><strong>{{ $order->order_number }}</strong></td>
                    <td>{{ $order->customer_name ?? 'Walk-in' }}</td>
                    <td>TSh {{ number_format($order->total_amount, 2) }}</td>
                    <td>
                      <span class="badge badge-{{ $order->status === 'served' ? 'success' : ($order->status === 'preparing' ? 'warning' : 'info') }}">
                        {{ ucfirst($order->status) }}
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">
                        {{ ucfirst($order->payment_status) }}
                      </span>
                    </td>
                    <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      <a href="{{ route('bar.orders.show', $order) }}" class="btn btn-info btn-sm">
                        <i class="fa fa-eye"></i> View
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No orders for this table yet.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

