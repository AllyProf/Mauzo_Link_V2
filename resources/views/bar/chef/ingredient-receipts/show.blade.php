@extends('layouts.dashboard')

@section('title', 'Ingredient Receipt Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-shopping-cart"></i> Receipt: {{ $receipt->receipt_number }}</h1>
    <p>Ingredient receipt details</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.ingredient-receipts') }}">Ingredient Receipts</a></li>
    <li class="breadcrumb-item">{{ $receipt->receipt_number }}</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="tile">
      <h3 class="tile-title">Receipt Information</h3>
      <div class="tile-body">
        <table class="table table-borderless">
          <tr>
            <th width="30%">Receipt Number:</th>
            <td><strong>{{ $receipt->receipt_number }}</strong></td>
          </tr>
          <tr>
            <th>Ingredient:</th>
            <td><strong>{{ $receipt->ingredient->name }}</strong></td>
          </tr>
          <tr>
            <th>Quantity:</th>
            <td><strong>{{ number_format($receipt->quantity_received, 2) }} {{ $receipt->unit }}</strong></td>
          </tr>
          <tr>
            <th>Cost Per Unit:</th>
            <td>TSh {{ number_format($receipt->cost_per_unit, 2) }}</td>
          </tr>
          <tr>
            <th>Total Cost:</th>
            <td><strong class="text-primary">TSh {{ number_format($receipt->total_cost, 2) }}</strong></td>
          </tr>
          <tr>
            <th>Supplier:</th>
            <td>{{ $receipt->supplier->company_name ?? 'N/A' }}</td>
          </tr>
          <tr>
            <th>Received Date:</th>
            <td>{{ $receipt->received_date->format('M d, Y') }}</td>
          </tr>
          <tr>
            <th>Expiry Date:</th>
            <td>
              @if($receipt->expiry_date)
                <span class="{{ $receipt->expiry_date->isPast() ? 'text-danger' : ($receipt->expiry_date->isToday() ? 'text-warning' : '') }}">
                  {{ $receipt->expiry_date->format('M d, Y') }}
                </span>
              @else
                N/A
              @endif
            </td>
          </tr>
          <tr>
            <th>Batch Number:</th>
            <td>{{ $receipt->batch_number ?? 'N/A' }}</td>
          </tr>
          <tr>
            <th>Location:</th>
            <td>{{ $receipt->location ?? 'N/A' }}</td>
          </tr>
          <tr>
            <th>Received By:</th>
            <td>{{ $receipt->receivedByStaff->full_name ?? 'N/A' }}</td>
          </tr>
          @if($receipt->notes)
          <tr>
            <th>Notes:</th>
            <td>{{ $receipt->notes }}</td>
          </tr>
          @endif
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="tile">
      <h3 class="tile-title">Batches Created</h3>
      <div class="tile-body">
        @if($receipt->batches->count() > 0)
          @foreach($receipt->batches as $batch)
            <div class="card mb-2">
              <div class="card-body">
                <strong>Batch: {{ $batch->batch_number ?? $batch->id }}</strong><br>
                <small>Initial: {{ number_format($batch->initial_quantity, 2) }} {{ $batch->unit }}</small><br>
                <small>Remaining: {{ number_format($batch->remaining_quantity, 2) }} {{ $batch->unit }}</small><br>
                <span class="badge badge-{{ $batch->status === 'active' ? 'success' : ($batch->status === 'depleted' ? 'secondary' : 'danger') }}">
                  {{ ucfirst($batch->status) }}
                </span>
              </div>
            </div>
          @endforeach
        @else
          <p class="text-muted">No batches created yet.</p>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <a href="{{ route('bar.chef.ingredient-receipts') }}" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Back to Receipts
    </a>
  </div>
</div>
@endsection

