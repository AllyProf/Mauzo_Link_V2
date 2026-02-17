@extends('layouts.dashboard')

@section('title', 'Point of Sale')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-shopping-cart"></i> Point of Sale</h1>
    <p>POS system for processing sales</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Point of Sale</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body text-center py-5">
        <i class="fa fa-shopping-cart fa-5x text-muted mb-4"></i>
        <h3>Point of Sale</h3>
        <p class="text-muted">This feature is coming soon. The POS system will allow you to process sales transactions, manage cart items, and print receipts.</p>
        <p class="text-muted">Stay tuned for updates!</p>
      </div>
    </div>
  </div>
</div>
@endsection












