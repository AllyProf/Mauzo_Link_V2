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
      <div class="tile-body text-center py-5">
        <i class="fa fa-users fa-5x text-muted mb-4"></i>
        <h3>Customer Management</h3>
        <p class="text-muted">This feature is coming soon. You'll be able to manage customer information, view purchase history, and track customer loyalty.</p>
        <p class="text-muted">Stay tuned for updates!</p>
      </div>
    </div>
  </div>
</div>
@endsection












