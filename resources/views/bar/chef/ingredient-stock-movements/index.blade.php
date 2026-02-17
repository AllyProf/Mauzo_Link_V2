@extends('layouts.dashboard')

@section('title', 'Ingredient Stock Movements')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange-alt"></i> Ingredient Stock Movements</h1>
    <p>Track all ingredient stock movements</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.dashboard') }}">Chef</a></li>
    <li class="breadcrumb-item">Stock Movements</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">All Stock Movements</h3>

      <!-- Filters -->
      <form method="GET" action="{{ route('bar.chef.ingredient-stock-movements') }}" class="mb-3">
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
            <select name="movement_type" class="form-control">
              <option value="">All Types</option>
              <option value="receipt" {{ request('movement_type') == 'receipt' ? 'selected' : '' }}>Receipt</option>
              <option value="usage" {{ request('movement_type') == 'usage' ? 'selected' : '' }}>Usage</option>
              <option value="adjustment" {{ request('movement_type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
              <option value="waste" {{ request('movement_type') == 'waste' ? 'selected' : '' }}>Waste</option>
              <option value="transfer" {{ request('movement_type') == 'transfer' ? 'selected' : '' }}>Transfer</option>
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
          </div>
          <div class="col-md-3">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
            <button type="submit" class="btn btn-info mt-2"><i class="fa fa-filter"></i> Filter</button>
            <a href="{{ route('bar.chef.ingredient-stock-movements') }}" class="btn btn-secondary mt-2"><i class="fa fa-times"></i> Clear</a>
          </div>
        </div>
      </form>

      <div class="tile-body">
        @if($movements->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Ingredient</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Unit</th>
                  <th>From Location</th>
                  <th>To Location</th>
                  <th>Batch</th>
                  <th>Created By</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @foreach($movements as $movement)
                  <tr>
                    <td>{{ $movement->created_at->format('M d, Y H:i') }}</td>
                    <td>{{ $movement->ingredient->name }}</td>
                    <td>
                      <span class="badge badge-{{ $movement->movement_type === 'receipt' ? 'success' : ($movement->movement_type === 'usage' ? 'danger' : ($movement->movement_type === 'adjustment' ? 'warning' : 'info')) }}">
                        {{ ucfirst($movement->movement_type) }}
                      </span>
                    </td>
                    <td>
                      <strong class="{{ $movement->quantity < 0 ? 'text-danger' : 'text-success' }}">
                        {{ $movement->quantity < 0 ? '' : '+' }}{{ number_format($movement->quantity, 2) }}
                      </strong>
                    </td>
                    <td>{{ $movement->unit }}</td>
                    <td>{{ $movement->from_location ?? 'N/A' }}</td>
                    <td>{{ $movement->to_location ?? 'N/A' }}</td>
                    <td>{{ $movement->ingredientBatch->batch_number ?? 'N/A' }}</td>
                    <td>{{ $movement->createdByStaff->full_name ?? 'N/A' }}</td>
                    <td><small>{{ $movement->notes ?? 'N/A' }}</small></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            @if($movements->hasPages())
              @php
                $currentPage = $movements->currentPage();
                $lastPage = $movements->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);
              @endphp
              <ul class="pagination justify-content-center">
                {{-- Previous Page Link --}}
                @if($movements->onFirstPage())
                  <li class="page-item disabled">
                    <span class="page-link">«</span>
                  </li>
                @else
                  <li class="page-item">
                    <a class="page-link" href="{{ $movements->previousPageUrl() }}" rel="prev">«</a>
                  </li>
                @endif

                {{-- First Page --}}
                @if($startPage > 1)
                  <li class="page-item">
                    <a class="page-link" href="{{ $movements->url(1) }}">1</a>
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
                      <a class="page-link" href="{{ $movements->url($page) }}">{{ $page }}</a>
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
                    <a class="page-link" href="{{ $movements->url($lastPage) }}">{{ $lastPage }}</a>
                  </li>
                @endif

                {{-- Next Page Link --}}
                @if($movements->hasMorePages())
                  <li class="page-item">
                    <a class="page-link" href="{{ $movements->nextPageUrl() }}" rel="next">»</a>
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
            <i class="fa fa-info-circle"></i> No stock movements found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

