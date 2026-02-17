@extends('layouts.dashboard')

@section('title', 'Staff Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-dashboard"></i> Staff Dashboard</h1>
    <p>Welcome back, {{ $staff->full_name }}!</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
  </ul>
</div>

@if(isset($statistics) && !empty($statistics))
<!-- Statistics Cards -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-archive fa-3x"></i>
      <div class="info">
        <h4>Warehouse Stock</h4>
        <p><b>{{ $statistics['warehouseStockItems'] ?? 0 }} items</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-cubes fa-3x"></i>
      <div class="info">
        <h4>Counter Stock Items</h4>
        <p><b>{{ $statistics['counterStockItems'] ?? 0 }}</b></p>
        @if(isset($statistics['lowStockItems']) && $statistics['lowStockItems'] > 0)
          <small class="text-warning">{{ $statistics['lowStockItems'] }} low stock</small>
        @endif
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-exchange fa-3x"></i>
      <div class="info">
        <h4>Pending Transfers</h4>
        <p><b>{{ $statistics['pendingTransfers'] ?? 0 }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-exclamation-triangle fa-3x"></i>
      <div class="info">
        <h4>Low Stock Items</h4>
        <p><b>{{ $statistics['lowStockItems'] ?? 0 }}</b></p>
        <small>Need attention</small>
      </div>
    </div>
  </div>
</div>
@endif

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        @if($staff->role && $staff->role->hasPermission('sales', 'view'))
        <div class="row">
          <div class="col-md-4">
            <a href="{{ route('sales.pos') }}" class="btn btn-primary btn-lg btn-block">
              <i class="fa fa-shopping-cart fa-2x mb-2"></i><br>
              Point of Sale
            </a>
          </div>
          <div class="col-md-4">
            <a href="{{ route('sales.orders') }}" class="btn btn-info btn-lg btn-block">
              <i class="fa fa-list fa-2x mb-2"></i><br>
              View Orders
            </a>
          </div>
          <div class="col-md-4">
            <a href="{{ route('sales.transactions') }}" class="btn btn-success btn-lg btn-block">
              <i class="fa fa-exchange fa-2x mb-2"></i><br>
              Transactions
            </a>
          </div>
        </div>
        @elseif($staff->role && $staff->role->hasPermission('bar_orders', 'view'))
        {{-- Chef Quick Actions --}}
        <div class="row">
          <div class="col-md-4">
            <a href="{{ route('bar.chef.dashboard') }}" class="btn btn-primary btn-lg btn-block">
              <i class="fa fa-dashboard fa-2x mb-2"></i><br>
              Chef Dashboard
            </a>
          </div>
          <div class="col-md-4">
            <a href="{{ route('bar.chef.food-items') }}" class="btn btn-info btn-lg btn-block">
              <i class="fa fa-utensils fa-2x mb-2"></i><br>
              Food Items
            </a>
          </div>
          <div class="col-md-4">
            <a href="{{ route('bar.chef.ingredients') }}" class="btn btn-success btn-lg btn-block">
              <i class="fa fa-box fa-2x mb-2"></i><br>
              Ingredients
            </a>
          </div>
        </div>
        @elseif($staff->role && ($staff->role->hasPermission('inventory', 'view') || strtolower($staff->role->name ?? '') === 'stock keeper'))
        {{-- Stock Keeper Quick Actions --}}
        <div class="row">
          <div class="col-md-3">
            <a href="{{ route('bar.beverage-inventory.warehouse-stock') }}" class="btn btn-warning btn-lg btn-block">
              <i class="fa fa-archive fa-2x mb-2"></i><br>
              Warehouse Stock
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('bar.beverage-inventory.index') }}" class="btn btn-info btn-lg btn-block">
              <i class="fa fa-cubes fa-2x mb-2"></i><br>
              Beverage Inventory
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('bar.stock-transfers.index') }}" class="btn btn-success btn-lg btn-block position-relative">
              <i class="fa fa-exchange fa-2x mb-2"></i><br>
              Stock Transfers
              @if(isset($statistics['pendingTransfers']) && $statistics['pendingTransfers'] > 0)
                <span class="badge badge-danger badge-lg" style="position: absolute; top: 5px; right: 5px; font-size: 14px; padding: 5px 8px;">
                  {{ $statistics['pendingTransfers'] }}
                </span>
              @endif
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('bar.inventory-settings.index') }}" class="btn btn-secondary btn-lg btn-block">
              <i class="fa fa-cog fa-2x mb-2"></i><br>
              Settings
            </a>
          </div>
        </div>
        @elseif($staff->role && ($staff->role->hasPermission('marketing', 'view') || strtolower($staff->role->name ?? '') === 'marketing'))
        {{-- Marketing Quick Actions --}}
        <div class="row">
          <div class="col-md-3">
            <a href="{{ route('marketing.dashboard') }}" class="btn btn-primary btn-lg btn-block">
              <i class="fa fa-bullhorn fa-2x mb-2"></i><br>
              Marketing Dashboard
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('marketing.customers') }}" class="btn btn-info btn-lg btn-block">
              <i class="fa fa-users fa-2x mb-2"></i><br>
              Customer Database
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('marketing.campaigns.create') }}" class="btn btn-success btn-lg btn-block">
              <i class="fa fa-plus-circle fa-2x mb-2"></i><br>
              Create Campaign
            </a>
          </div>
          <div class="col-md-3">
            <a href="{{ route('marketing.campaigns') }}" class="btn btn-warning btn-lg btn-block">
              <i class="fa fa-list fa-2x mb-2"></i><br>
              Campaign History
            </a>
          </div>
        </div>
        @else
        <div class="alert alert-info">
          <i class="fa fa-info-circle"></i> Your role permissions are being configured. Please contact your administrator for access to features.
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

@if(isset($statistics['lowStockItemsList']) && $statistics['lowStockItemsList']->count() > 0)
<!-- Low Stock Items -->
<div class="row mt-4">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-exclamation-triangle text-warning"></i> Low Stock Items</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Warehouse Stock</th>
                <th>Counter Stock</th>
                <th>Total Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($statistics['lowStockItemsList'] as $item)
              <tr>
                <td><strong>{{ $item['product_name'] }}</strong></td>
                <td>{{ $item['variant'] }}</td>
                <td>{{ number_format($item['warehouse_qty']) }}</td>
                <td>{{ number_format($item['counter_qty']) }}</td>
                <td><strong class="text-danger">{{ number_format($item['total_qty']) }}</strong></td>
                <td>
                  <span class="badge badge-{{ isset($item['is_critical']) && $item['is_critical'] ? 'danger' : 'warning' }}">
                    {{ isset($item['is_critical']) && $item['is_critical'] ? 'Critical' : 'Low' }}
                  </span>
                </td>
                <td>
                  <a href="{{ route('bar.beverage-inventory.index') }}" class="btn btn-sm btn-warning">
                    <i class="fa fa-plus"></i> Restock
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <a href="{{ route('bar.beverage-inventory.low-stock-alerts') }}" class="btn btn-warning">
            <i class="fa fa-exclamation-triangle"></i> View All Low Stock Alerts
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection




