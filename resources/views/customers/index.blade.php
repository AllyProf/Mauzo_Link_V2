@extends('layouts.dashboard')

@section('title', 'Customers')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> Customers</h1>
    <p>Manage your customer database</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Customers</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">Customer Records</h3>
        <div class="btn-group">
          <form class="form-inline" method="GET" action="{{ route('customers.index') }}">
            <input class="form-control mr-sm-2" type="search" name="search" placeholder="Search customer..." aria-label="Search" value="{{ $search ?? '' }}">
            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
          </form>
        </div>
      </div>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="customerTable">
            <thead>
              <tr class="bg-primary text-white">
                <th>Customer Name</th>
                <th>Phone Number</th>
                <th class="text-center">Visits</th>
                <th class="text-right">Total Spent</th>
                <th>Last Visit</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($customers as $customer)
                <tr>
                  <td><strong>{{ $customer->name ?? 'Regular Customer' }}</strong></td>
                  <td><a href="tel:{{ $customer->customer_phone }}">{{ $customer->customer_phone }}</a></td>
                  <td class="text-center"><span class="badge badge-info">{{ $customer->total_orders }}</span></td>
                  <td class="text-right">TSh {{ number_format($customer->total_spent) }}</td>
                  <td>{{ \Carbon\Carbon::parse($customer->last_visit)->format('M d, Y h:i A') }}</td>
                  <td>
                    <button class="btn btn-sm btn-info" title="View History"><i class="fa fa-history"></i></button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4">
                    <i class="fa fa-user-times fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No customers found matching your criteria.</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-4">
            {{ $customers->appends(['search' => $search])->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection












