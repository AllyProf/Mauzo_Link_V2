@extends('layouts.dashboard')

@section('title', 'Inventory')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-archive"></i> Inventory</h1>
    <p>Manage inventory and stock levels</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
    <li class="breadcrumb-item">Inventory</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body text-center py-5">
        <i class="fa fa-archive fa-5x text-muted mb-4"></i>
        <h3>Inventory Management</h3>
        <p class="text-muted">This feature is coming soon. You'll be able to track stock levels, manage inventory, and receive low stock alerts.</p>
        <p class="text-muted">Stay tuned for updates!</p>
      </div>
    </div>
  </div>
</div>
@endsection












