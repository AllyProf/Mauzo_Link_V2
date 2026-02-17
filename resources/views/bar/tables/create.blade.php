@extends('layouts.dashboard')

@section('title', 'Add Table')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-table"></i> Add Table</h1>
    <p>Register a new table</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.tables.index') }}">Tables</a></li>
    <li class="breadcrumb-item">Add Table</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Table Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.tables.store') }}">
          @csrf

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Table Number *</label>
                <input class="form-control @error('table_number') is-invalid @enderror" 
                       type="text" 
                       name="table_number" 
                       value="{{ old('table_number') }}" 
                       placeholder="e.g., T01, Table 1" 
                       required>
                @error('table_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Unique identifier for this table</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Table Name</label>
                <input class="form-control @error('table_name') is-invalid @enderror" 
                       type="text" 
                       name="table_name" 
                       value="{{ old('table_name') }}" 
                       placeholder="e.g., VIP Table, Window Table">
                @error('table_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Optional descriptive name</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Capacity (Seats) *</label>
                <input class="form-control @error('capacity') is-invalid @enderror" 
                       type="number" 
                       name="capacity" 
                       value="{{ old('capacity', 4) }}" 
                       min="1" 
                       max="100" 
                       required>
                @error('capacity')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Number of seats at this table</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Location/Zone</label>
                <input class="form-control @error('location') is-invalid @enderror" 
                       type="text" 
                       name="location" 
                       value="{{ old('location') }}" 
                       placeholder="e.g., Main Hall, VIP Section, Outdoor">
                @error('location')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Table location or zone</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Active</label>
                <div class="form-check">
                  <input class="form-check-input" 
                         type="checkbox" 
                         name="is_active" 
                         value="1" 
                         {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label">
                    Table is active and available for use
                  </label>
                </div>
                @error('is_active')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="alert alert-info mt-4">
                <i class="fa fa-info-circle"></i> <strong>Note:</strong> Table status is automatically managed by the system. When orders are created, the table becomes "Occupied" and returns to "Available" when orders are paid.
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label class="control-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" 
                          name="notes" 
                          rows="3" 
                          placeholder="Any additional notes about this table">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i>Save Table
            </button>
            <a class="btn btn-secondary" href="{{ route('bar.tables.index') }}">
              <i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

