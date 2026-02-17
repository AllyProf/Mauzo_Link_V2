@extends('layouts.dashboard')

@section('title', 'Edit Ingredient')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-flask"></i> Edit Ingredient</h1>
    <p>Update ingredient information</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.ingredients') }}">Ingredients</a></li>
    <li class="breadcrumb-item">Edit Ingredient</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Ingredient Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.chef.ingredients.update', $ingredient) }}">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Ingredient Name *</label>
                <input class="form-control @error('name') is-invalid @enderror" 
                       type="text" 
                       name="name" 
                       value="{{ old('name', $ingredient->name) }}" 
                       placeholder="e.g., Chicken, Flour, Oil" 
                       required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Unit of Measurement *</label>
                <select class="form-control @error('unit') is-invalid @enderror" name="unit" required>
                  <option value="">Select Unit</option>
                  <option value="kg" {{ old('unit', $ingredient->unit) == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                  <option value="g" {{ old('unit', $ingredient->unit) == 'g' ? 'selected' : '' }}>Gram (g)</option>
                  <option value="liter" {{ old('unit', $ingredient->unit) == 'liter' ? 'selected' : '' }}>Liter</option>
                  <option value="ml" {{ old('unit', $ingredient->unit) == 'ml' ? 'selected' : '' }}>Milliliter (ml)</option>
                  <option value="piece" {{ old('unit', $ingredient->unit) == 'piece' ? 'selected' : '' }}>Piece</option>
                  <option value="bunch" {{ old('unit', $ingredient->unit) == 'bunch' ? 'selected' : '' }}>Bunch</option>
                </select>
                @error('unit')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Current Stock</label>
                <input class="form-control" 
                       type="text" 
                       value="{{ number_format($ingredient->current_stock, 2) }} {{ $ingredient->unit }}" 
                       readonly
                       disabled>
                <small class="form-text text-muted">Stock is managed through ingredient receipts</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Minimum Stock Level *</label>
                <input class="form-control @error('min_stock_level') is-invalid @enderror" 
                       type="number" 
                       name="min_stock_level" 
                       value="{{ old('min_stock_level', $ingredient->min_stock_level) }}" 
                       step="0.01"
                       min="0"
                       required>
                <small class="form-text text-muted">Alert when stock falls below this level</small>
                @error('min_stock_level')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Expiry Date</label>
                <input class="form-control @error('expiry_date') is-invalid @enderror" 
                       type="date" 
                       name="expiry_date" 
                       value="{{ old('expiry_date', $ingredient->expiry_date ? $ingredient->expiry_date->format('Y-m-d') : '') }}">
                <small class="form-text text-muted">Optional - for ingredients with expiry dates</small>
                @error('expiry_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-check">
              <input class="form-check-input" 
                     type="checkbox" 
                     name="is_active" 
                     value="1" 
                     id="is_active"
                     {{ old('is_active', $ingredient->is_active) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">
                Active
              </label>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i> Update Ingredient
            </button>
            <a class="btn btn-secondary" href="{{ route('bar.chef.ingredients') }}">
              <i class="fa fa-fw fa-lg fa-times-circle"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

