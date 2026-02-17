@extends('layouts.dashboard')

@section('title', 'Ingredient Receipts')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-shopping-cart"></i> Ingredient Receipts</h1>
    <p>Manage ingredient stock receipts</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.dashboard') }}">Chef</a></li>
    <li class="breadcrumb-item">Ingredient Receipts</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Ingredient Receipts</h3>
        <a href="{{ route('bar.chef.ingredient-receipts.create') }}" class="btn btn-primary">
          <i class="fa fa-plus"></i> New Receipt
        </a>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <!-- Filters -->
      <form method="GET" action="{{ route('bar.chef.ingredient-receipts') }}" class="mb-3">
        <div class="row">
          <div class="col-md-3">
            <select name="ingredient_id" class="form-control">
              <option value="">All Ingredients</option>
              @foreach($ingredients as $ingredient)
                <option value="{{ $ingredient->id }}" {{ request('ingredient_id') == $ingredient->id ? 'selected' : '' }}>
                  {{ $ingredient->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
          </div>
          <div class="col-md-3">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-info"><i class="fa fa-filter"></i> Filter</button>
            <a href="{{ route('bar.chef.ingredient-receipts') }}" class="btn btn-secondary"><i class="fa fa-times"></i> Clear</a>
          </div>
        </div>
      </form>

      <div class="tile-body">
        @if($receipts->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Receipt #</th>
                  <th>Ingredient</th>
                  <th>Quantity</th>
                  <th>Unit</th>
                  <th>Cost/Unit</th>
                  <th>Total Cost</th>
                  <th>Supplier</th>
                  <th>Received Date</th>
                  <th>Expiry Date</th>
                  <th>Received By</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($receipts as $receipt)
                  <tr>
                    <td><strong>{{ $receipt->receipt_number }}</strong></td>
                    <td>{{ $receipt->ingredient->name }}</td>
                    <td><strong>{{ number_format($receipt->quantity_received, 2) }}</strong></td>
                    <td>{{ $receipt->unit }}</td>
                    <td>TSh {{ number_format($receipt->cost_per_unit, 2) }}</td>
                    <td><strong class="text-primary">TSh {{ number_format($receipt->total_cost, 2) }}</strong></td>
                    <td>{{ $receipt->supplier->company_name ?? 'N/A' }}</td>
                    <td>{{ $receipt->received_date->format('M d, Y') }}</td>
                    <td>
                      @if($receipt->expiry_date)
                        <span class="{{ $receipt->expiry_date->isPast() ? 'text-danger' : ($receipt->expiry_date->isToday() ? 'text-warning' : '') }}">
                          {{ $receipt->expiry_date->format('M d, Y') }}
                        </span>
                      @else
                        N/A
                      @endif
                    </td>
                    <td>{{ $receipt->receivedByStaff->full_name ?? 'N/A' }}</td>
                    <td>
                      <a href="{{ route('bar.chef.ingredient-receipts.show', $receipt) }}" class="btn btn-sm btn-info">
                        <i class="fa fa-eye"></i> View
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            @if($receipts->hasPages())
              @php
                $currentPage = $receipts->currentPage();
                $lastPage = $receipts->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);
              @endphp
              <ul class="pagination justify-content-center">
                {{-- Previous Page Link --}}
                @if($receipts->onFirstPage())
                  <li class="page-item disabled">
                    <span class="page-link">«</span>
                  </li>
                @else
                  <li class="page-item">
                    <a class="page-link" href="{{ $receipts->previousPageUrl() }}" rel="prev">«</a>
                  </li>
                @endif

                {{-- First Page --}}
                @if($startPage > 1)
                  <li class="page-item">
                    <a class="page-link" href="{{ $receipts->url(1) }}">1</a>
                  </li>
                  @if($startPage > 2)
                    <li class="page-item disabled">
                      <span class="page-link">...</span>
                    </li>
                  @endif
                @endif

                {{-- Pagination Elements --}}
                @for($page = $startPage; $page <= $endPage; $page++)
                  @if($page == $currentPage)
                    <li class="page-item active">
                      <span class="page-link">{{ $page }}</span>
                    </li>
                  @else
                    <li class="page-item">
                      <a class="page-link" href="{{ $receipts->url($page) }}">{{ $page }}</a>
                    </li>
                  @endif
                @endfor

                {{-- Last Page --}}
                @if($endPage < $lastPage)
                  @if($endPage < $lastPage - 1)
                    <li class="page-item disabled">
                      <span class="page-link">...</span>
                    </li>
                  @endif
                  <li class="page-item">
                    <a class="page-link" href="{{ $receipts->url($lastPage) }}">{{ $lastPage }}</a>
                  </li>
                @endif

                {{-- Next Page Link --}}
                @if($receipts->hasMorePages())
                  <li class="page-item">
                    <a class="page-link" href="{{ $receipts->nextPageUrl() }}" rel="next">»</a>
                  </li>
                @else
                  <li class="page-item disabled">
                    <span class="page-link">»</span>
                  </li>
                @endif
              </ul>
            @endif
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No ingredient receipts found. <a href="{{ route('bar.chef.ingredient-receipts.create') }}">Create your first receipt</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

