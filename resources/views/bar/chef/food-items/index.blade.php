@extends('layouts.dashboard')

@section('title', 'Food Items')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cutlery"></i> Food Items</h1>
    <p>Manage your food menu items</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.dashboard') }}">Chef</a></li>
    <li class="breadcrumb-item">Food Items</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Food Items</h3>
        <a href="{{ route('bar.chef.food-items.create') }}" class="btn btn-primary">
          <i class="fa fa-plus"></i> Add Food Item
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

      <div class="tile-body">
        @if($foodItems->count() > 0)
          <div class="row">
            @foreach($foodItems as $item)
              <div class="col-md-4 mb-3">
                <div class="card">
                  @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top" alt="{{ $item->name }}" style="height: 200px; object-fit: cover;">
                  @else
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                      <i class="fa fa-image fa-3x text-muted"></i>
                    </div>
                  @endif
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h5 class="card-title mb-0">{{ $item->name }}</h5>
                      @if($item->recipe)
                        <span class="badge badge-info" title="Has Recipe">
                          <i class="fa fa-book"></i> Recipe
                        </span>
                      @endif
                    </div>
                    @if($item->variant_name)
                      <p class="text-muted mb-2"><small>{{ $item->variant_name }}</small></p>
                    @endif
                    <p class="card-text">
                      <strong>Price:</strong> TSh {{ number_format($item->price, 2) }}
                    </p>
                    @if($item->prep_time_minutes)
                      <p class="card-text">
                        <small class="text-muted">Prep Time: {{ $item->prep_time_minutes }} min</small>
                      </p>
                    @endif
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="badge {{ $item->is_available ? 'badge-success' : 'badge-danger' }}">
                        {{ $item->is_available ? 'Available' : 'Unavailable' }}
                      </span>
                      @if($item->recipe)
                        <span class="badge badge-info">
                          <i class="fa fa-check-circle"></i> Recipe Added
                        </span>
                      @endif
                    </div>
                    <div class="btn-group-vertical w-100" role="group" style="gap: 5px;">
                      <a href="{{ route('bar.chef.food-items.recipe', $item) }}" class="btn btn-sm btn-info" title="Manage Recipe">
                        <i class="fa fa-book"></i> {{ $item->recipe ? 'Edit Recipe' : 'Add Recipe' }}
                      </a>
                      <div class="btn-group w-100" role="group">
                        <a href="{{ route('bar.chef.food-items.edit', $item) }}" class="btn btn-sm btn-warning" style="flex: 1;">
                          <i class="fa fa-pencil"></i> Edit
                        </a>
                        <form action="{{ route('bar.chef.food-items.destroy', $item) }}" method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this food item?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger w-100">
                            <i class="fa fa-trash"></i> Delete
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <div class="mt-3">
            @if($foodItems->hasPages())
              @php
                $currentPage = $foodItems->currentPage();
                $lastPage = $foodItems->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);
              @endphp
              <ul class="pagination justify-content-center">
                {{-- Previous Page Link --}}
                @if($foodItems->onFirstPage())
                  <li class="page-item disabled">
                    <span class="page-link">«</span>
                  </li>
                @else
                  <li class="page-item">
                    <a class="page-link" href="{{ $foodItems->previousPageUrl() }}" rel="prev">«</a>
                  </li>
                @endif

                {{-- First Page --}}
                @if($startPage > 1)
                  <li class="page-item">
                    <a class="page-link" href="{{ $foodItems->url(1) }}">1</a>
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
                      <a class="page-link" href="{{ $foodItems->url($page) }}">{{ $page }}</a>
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
                    <a class="page-link" href="{{ $foodItems->url($lastPage) }}">{{ $lastPage }}</a>
                  </li>
                @endif

                {{-- Next Page Link --}}
                @if($foodItems->hasMorePages())
                  <li class="page-item">
                    <a class="page-link" href="{{ $foodItems->nextPageUrl() }}" rel="next">»</a>
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
            <i class="fa fa-info-circle"></i> No food items found. <a href="{{ route('bar.chef.food-items.create') }}">Create your first food item</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection



