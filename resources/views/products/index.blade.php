@extends('layouts.dashboard')

@section('title', 'Products')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cube"></i> Products</h1>
    <p>Manage your product catalog</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Products</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body text-center py-5">
        <i class="fa fa-cube fa-5x text-muted mb-4"></i>
        <h3>Product Management</h3>
        <p class="text-muted">This feature is coming soon. You'll be able to add, edit, and manage all your products with categories, pricing, and inventory tracking.</p>
        <p class="text-muted">Stay tuned for updates!</p>
      </div>
    </div>
  </div>
</div>
@endsection












