@extends('layouts.dashboard')

@section('title', 'Ingredient Batches')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-boxes"></i> Ingredient Batches</h1>
    <p>Track ingredient batches with FIFO</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.dashboard') }}">Chef</a></li>
    <li class="breadcrumb-item">Ingredient Batches</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">All Ingredient Batches</h3>

      <!-- Search and Filters (Client-side Real-time) -->
      <div class="mb-3">
        <div class="row mb-2">
          <div class="col-md-12">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
              </div>
              <input type="text" id="searchInput" class="form-control" 
                     placeholder="Search by batch number, ingredient name, or location...">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <label class="control-label">Filter by Ingredient</label>
            <select id="ingredientFilter" class="form-control">
              <option value="">All Ingredients</option>
              @foreach($ingredients as $ingredient)
                <option value="{{ $ingredient->id }}">
                  {{ $ingredient->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="control-label">Filter by Status</label>
            <select id="statusFilter" class="form-control">
              <option value="">All Statuses</option>
              <option value="active">Active</option>
              <option value="depleted">Depleted</option>
              <option value="expired">Expired</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="control-label">&nbsp;</label>
            <div>
              <button type="button" id="clearFilters" class="btn btn-secondary">
                <i class="fa fa-times"></i> Clear All
              </button>
            </div>
          </div>
        </div>
        <div class="row mt-2">
          <div class="col-md-12">
            <small class="text-muted">
              <i class="fa fa-info-circle"></i> 
              <span id="resultCount">Showing all batches</span>
            </small>
          </div>
        </div>
      </div>

      <div class="tile-body">
        @if($batches->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Batch #</th>
                  <th>Ingredient</th>
                  <th>Initial Qty</th>
                  <th>Remaining Qty</th>
                  <th>Used Qty</th>
                  <th>Unit</th>
                  <th>Received Date</th>
                  <th>Expiry Date</th>
                  <th>Cost/Unit</th>
                  <th>Location</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="batchesTableBody">
                @foreach($batches as $batch)
                  <tr class="batch-row {{ $batch->isExpired() ? 'table-danger' : ($batch->isDepleted() ? 'table-secondary' : '') }}"
                      data-batch-number="{{ strtolower($batch->batch_number ?? 'BATCH-' . $batch->id) }}"
                      data-ingredient-name="{{ strtolower($batch->ingredient->name) }}"
                      data-ingredient-id="{{ $batch->ingredient_id }}"
                      data-location="{{ strtolower($batch->location ?? '') }}"
                      data-status="{{ strtolower($batch->status) }}">
                    <td><strong>{{ $batch->batch_number ?? 'BATCH-' . $batch->id }}</strong></td>
                    <td>{{ $batch->ingredient->name }}</td>
                    <td>{{ number_format($batch->initial_quantity, 2) }}</td>
                    <td>
                      <strong>{{ number_format($batch->remaining_quantity, 2) }}</strong>
                      @if($batch->remaining_quantity <= 0)
                        <span class="badge badge-secondary">Depleted</span>
                      @endif
                    </td>
                    <td>{{ number_format($batch->used_quantity, 2) }}</td>
                    <td>{{ $batch->unit }}</td>
                    <td>{{ $batch->received_date->format('M d, Y') }}</td>
                    <td>
                      @if($batch->expiry_date)
                        <span class="{{ $batch->isExpired() ? 'text-danger' : ($batch->expiry_date->isToday() ? 'text-warning' : '') }}">
                          {{ $batch->expiry_date->format('M d, Y') }}
                          @if($batch->isExpired())
                            <span class="badge badge-danger">Expired</span>
                          @endif
                        </span>
                      @else
                        N/A
                      @endif
                    </td>
                    <td>TSh {{ number_format($batch->cost_per_unit, 2) }}</td>
                    <td>{{ $batch->location ?? 'N/A' }}</td>
                    <td>
                      <span class="badge badge-{{ $batch->status === 'active' ? 'success' : ($batch->status === 'depleted' ? 'secondary' : ($batch->status === 'expired' ? 'danger' : 'warning')) }}">
                        {{ ucfirst($batch->status) }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No ingredient batches found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const $searchInput = $('#searchInput');
    const $ingredientFilter = $('#ingredientFilter');
    const $statusFilter = $('#statusFilter');
    const $clearFilters = $('#clearFilters');
    const $resultCount = $('#resultCount');
    const $batchRows = $('.batch-row');
    const totalBatches = $batchRows.length;

    // Function to filter rows
    function filterBatches() {
        const searchText = $searchInput.val().toLowerCase().trim();
        const selectedIngredient = $ingredientFilter.val();
        const selectedStatus = $statusFilter.val();

        let visibleCount = 0;

        $batchRows.each(function() {
            const $row = $(this);
            const batchNumber = $row.data('batch-number') || '';
            const ingredientName = $row.data('ingredient-name') || '';
            const ingredientId = $row.data('ingredient-id') || '';
            const location = $row.data('location') || '';
            const status = $row.data('status') || '';

            // Search filter
            const matchesSearch = !searchText || 
                batchNumber.includes(searchText) ||
                ingredientName.includes(searchText) ||
                location.includes(searchText);

            // Ingredient filter
            const matchesIngredient = !selectedIngredient || 
                ingredientId.toString() === selectedIngredient;

            // Status filter
            const matchesStatus = !selectedStatus || 
                status === selectedStatus.toLowerCase();

            // Show/hide row based on all filters
            if (matchesSearch && matchesIngredient && matchesStatus) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        // Update result count
        if (visibleCount === totalBatches) {
            $resultCount.text('Showing all ' + totalBatches + ' batches');
        } else {
            $resultCount.text('Showing ' + visibleCount + ' of ' + totalBatches + ' batches');
        }

        // Show/hide "no results" message
        if (visibleCount === 0) {
            if ($('#noResultsMessage').length === 0) {
                $('#batchesTableBody').after(
                    '<tr id="noResultsMessage"><td colspan="11" class="text-center py-4">' +
                    '<i class="fa fa-search fa-2x text-muted mb-2"></i><br>' +
                    '<strong>No batches found matching your filters.</strong><br>' +
                    '<small class="text-muted">Try adjusting your search or filter criteria.</small>' +
                    '</td></tr>'
                );
            }
        } else {
            $('#noResultsMessage').remove();
        }
    }

    // Real-time filtering on input/change
    $searchInput.on('input', filterBatches);
    $ingredientFilter.on('change', filterBatches);
    $statusFilter.on('change', filterBatches);

    // Clear all filters
    $clearFilters.on('click', function() {
        $searchInput.val('');
        $ingredientFilter.val('');
        $statusFilter.val('');
        filterBatches();
    });

    // Initial filter (in case page loads with filters from URL)
    filterBatches();
});
</script>
@endpush

