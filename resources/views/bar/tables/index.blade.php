@extends('layouts.dashboard')

@section('title', 'Tables')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-table"></i> Tables</h1>
    <p>Manage your bar tables</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Tables</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Tables</h3>
        <a href="{{ route('bar.tables.create') }}" class="btn btn-primary">
          <i class="fa fa-plus"></i> Add Table
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
          {{ session('error') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        @if($tables->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tablesTable">
              <thead>
                <tr>
                  <th>Table Number</th>
                  <th>Table Name</th>
                  <th>Capacity</th>
                  <th>Currently Occupied</th>
                  <th>Remaining Seats</th>
                  <th>Location</th>
                  <th>Active</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($tables as $table)
                  @php
                    $current = $table->current_people;
                    $remaining = $table->remaining_capacity;
                  @endphp
                  <tr>
                    <td><strong>{{ $table->table_number }}</strong></td>
                    <td>{{ $table->table_name ?? 'N/A' }}</td>
                    <td>{{ $table->capacity }} seats</td>
                    <td>
                      <span class="badge {{ $current > 0 ? 'badge-info' : 'badge-secondary' }}">
                        {{ $current }} {{ $current == 1 ? 'person' : 'people' }}
                      </span>
                    </td>
                    <td>
                      <span class="badge {{ $remaining > 0 ? 'badge-success' : 'badge-danger' }}">
                        {{ $remaining }} {{ $remaining == 1 ? 'seat' : 'seats' }} available
                      </span>
                    </td>
                    <td>{{ $table->location ?? 'N/A' }}</td>
                    <td>
                      <span class="badge {{ $table->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $table->is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td>
                      <a href="{{ route('bar.tables.show', $table) }}" class="btn btn-info btn-sm">
                        <i class="fa fa-eye"></i> View
                      </a>
                      <a href="{{ route('bar.tables.edit', $table) }}" class="btn btn-warning btn-sm">
                        <i class="fa fa-pencil"></i> Edit
                      </a>
                      <form action="{{ route('bar.tables.destroy', $table) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this table?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                          <i class="fa fa-trash"></i> Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No tables registered yet. 
            <a href="{{ route('bar.tables.create') }}">Add your first table</a> to get started.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<!-- Data table plugin-->
<script type="text/javascript" src="{{ asset('js/admin/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/admin/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#tablesTable').DataTable({
      "paging": true,
      "info": true,
      "searching": true,
    });
  });
</script>
@endsection

