@extends('layouts.dashboard')

@section('title', 'Customer Groups')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-group"></i> Customer Groups</h1>
    <p>Organize customers into groups</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item">Groups</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body text-center py-5">
        <i class="fa fa-group fa-5x text-muted mb-4"></i>
        <h3>Customer Groups</h3>
        <p class="text-muted">This feature is coming soon. You'll be able to organize customers into groups for targeted marketing and pricing.</p>
        <p class="text-muted">Stay tuned for updates!</p>
      </div>
    </div>
  </div>
</div>
@endsection












