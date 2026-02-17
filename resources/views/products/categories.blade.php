@extends('layouts.dashboard')

@section('title', 'Categories')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-folder"></i> Categories</h1>
    <p>Manage product categories</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
    <li class="breadcrumb-item">Categories</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body text-center py-5">
        <i class="fa fa-folder fa-5x text-muted mb-4"></i>
        <h3>Product Categories</h3>
        <p class="text-muted">This feature is coming soon. You'll be able to organize your products into categories for better management.</p>
        <p class="text-muted">Stay tuned for updates!</p>
      </div>
    </div>
  </div>
</div>
@endsection












