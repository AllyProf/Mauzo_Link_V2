@extends('layouts.dashboard')

@section('title', 'Ingredients')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-flask"></i> Ingredients</h1>
    <p>Manage your kitchen ingredients</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.dashboard') }}">Chef</a></li>
    <li class="breadcrumb-item">Ingredients</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Ingredients</h3>
        <a href="{{ route('bar.chef.ingredients.create') }}" class="btn btn-primary">
          <i class="fa fa-plus"></i> Add Ingredient
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

      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fa fa-exclamation-triangle"></i> {{ session('error') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        @if($ingredients->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Unit</th>
                  <th>Current Stock</th>
                  <th>Min Level</th>
                  <th>Location</th>
                  <th>Cost/Unit</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($ingredients as $ingredient)
                  <tr class="{{ $ingredient->isLowStock() ? 'table-warning' : '' }}">
                    <td><strong>{{ $ingredient->name }}</strong></td>
                    <td>{{ $ingredient->unit }}</td>
                    <td>
                      <strong>{{ number_format($ingredient->current_stock, 2) }}</strong>
                      @if($ingredient->isLowStock())
                        <span class="badge badge-danger ml-2">Low Stock!</span>
                      @endif
                    </td>
                    <td>{{ number_format($ingredient->min_stock_level, 2) }}</td>
                    <td>{{ $ingredient->location ?? 'N/A' }}</td>
                    <td>{{ $ingredient->cost_per_unit ? 'TSh ' . number_format($ingredient->cost_per_unit, 2) : 'N/A' }}</td>
                    <td>
                      <span class="badge {{ $ingredient->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $ingredient->is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td>
                      <a href="{{ route('bar.chef.ingredients.edit', $ingredient) }}" class="btn btn-sm btn-warning">
                        <i class="fa fa-pencil"></i> Edit
                      </a>
                      <button type="button" class="btn btn-sm btn-danger" onclick="deleteIngredient({{ $ingredient->id }}, '{{ addslashes($ingredient->name) }}')">
                        <i class="fa fa-trash"></i> Delete
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            @if($ingredients->hasPages())
              @php
                $currentPage = $ingredients->currentPage();
                $lastPage = $ingredients->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);
              @endphp
              <ul class="pagination justify-content-center">
                {{-- Previous Page Link --}}
                @if($ingredients->onFirstPage())
                  <li class="page-item disabled">
                    <span class="page-link">«</span>
                  </li>
                @else
                  <li class="page-item">
                    <a class="page-link" href="{{ $ingredients->previousPageUrl() }}" rel="prev">«</a>
                  </li>
                @endif

                {{-- First Page --}}
                @if($startPage > 1)
                  <li class="page-item">
                    <a class="page-link" href="{{ $ingredients->url(1) }}">1</a>
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
                      <a class="page-link" href="{{ $ingredients->url($page) }}">{{ $page }}</a>
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
                    <a class="page-link" href="{{ $ingredients->url($lastPage) }}">{{ $lastPage }}</a>
                  </li>
                @endif

                {{-- Next Page Link --}}
                @if($ingredients->hasMorePages())
                  <li class="page-item">
                    <a class="page-link" href="{{ $ingredients->nextPageUrl() }}" rel="next">»</a>
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
            <i class="fa fa-info-circle"></i> No ingredients found. <a href="{{ route('bar.chef.ingredients.create') }}">Add your first ingredient</a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function deleteIngredient(ingredientId, ingredientName) {
  Swal.fire({
    title: 'Delete Ingredient?',
    html: `Are you sure you want to delete <strong>${ingredientName}</strong>?<br><br>This action cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: '<i class="fa fa-trash"></i> Yes, delete it!',
    cancelButtonText: 'Cancel',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      // Create a form and submit it
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = `{{ url('/bar/chef/ingredients') }}/${ingredientId}`;
      
      // Add CSRF token
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = '_token';
      csrfInput.value = '{{ csrf_token() }}';
      form.appendChild(csrfInput);
      
      // Add method spoofing for DELETE
      const methodInput = document.createElement('input');
      methodInput.type = 'hidden';
      methodInput.name = '_method';
      methodInput.value = 'DELETE';
      form.appendChild(methodInput);
      
      document.body.appendChild(form);
      form.submit();
    }
  });
}
</script>
@endpush





