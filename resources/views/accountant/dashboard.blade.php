@extends('layouts.dashboard')

@section('title', 'Accountant Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-calculator"></i> Accountant Dashboard</h1>
    <p>Financial Overview & Reconciliation Management</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Accountant</li>
  </ul>
</div>

<!-- Date Selector -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('accountant.dashboard') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="date" class="mr-2">Today's Date:</label>
          <input type="date" name="date" id="date" class="form-control" value="{{ $date }}" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> Update
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-3">
  <div class="col-md-4">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4 style="color: white;">Today's Revenue</h4>
        <p style="color: white;"><b>TSh {{ number_format($todayRevenue, 0) }}</b></p>
        <small style="color: white;">Bar: TSh {{ number_format($todayBarSales, 0) }} | Food: TSh {{ number_format($todayFoodSales, 0) }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-bank fa-3x"></i>
      <div class="info">
        <h4>Cash Collected</h4>
        <p><b>TSh {{ number_format($todayCash, 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-mobile fa-3x"></i>
      <div class="info">
        <h4>Mobile Money</h4>
        <p><b>TSh {{ number_format($todayMobileMoney, 0) }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row mt-3">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-3 mb-3">
            <a href="{{ route('accountant.reconciliations') }}" class="btn btn-primary btn-block btn-lg">
              <i class="fa fa-exchange"></i><br>
              Reconciliations
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('accountant.reports') }}" class="btn btn-info btn-block btn-lg">
              <i class="fa fa-line-chart"></i><br>
              Financial Reports
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

