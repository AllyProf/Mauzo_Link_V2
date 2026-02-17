@extends('layouts.dashboard')

@section('title', 'Create Plan')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-plus"></i> Create New Plan</h1>
    <p>Add a new subscription plan</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
    <li class="breadcrumb-item">Create</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="tile">
      <h3 class="tile-title">Plan Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('admin.plans.store') }}">
          @csrf
          
          <div class="form-group">
            <label for="name">Plan Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="slug">Slug <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug') }}" required>
            <small class="form-text text-muted">URL-friendly identifier (e.g., basic-plan)</small>
            @error('slug')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="price">Price (TSh) <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', 0) }}" step="0.01" min="0" required>
                @error('price')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="trial_days">Trial Days <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('trial_days') is-invalid @enderror" id="trial_days" name="trial_days" value="{{ old('trial_days', 0) }}" min="0" required>
                @error('trial_days')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="max_locations">Max Locations <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('max_locations') is-invalid @enderror" id="max_locations" name="max_locations" value="{{ old('max_locations', 1) }}" min="1" required>
                <small class="form-text text-muted">Use 999 for unlimited</small>
                @error('max_locations')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="max_users">Max Users <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('max_users') is-invalid @enderror" id="max_users" name="max_users" value="{{ old('max_users', 1) }}" min="1" required>
                <small class="form-text text-muted">Use 999 for unlimited</small>
                @error('max_users')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                @error('sort_order')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">
                    Active Plan
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Features (one per line)</label>
            <textarea class="form-control" id="features_text" rows="5" placeholder="Enter features, one per line"></textarea>
            <small class="form-text text-muted">Features will be converted to an array</small>
          </div>

          <div class="form-group">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Create Plan
            </button>
            <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.querySelector('form').addEventListener('submit', function(e) {
    const featuresText = document.getElementById('features_text').value;
    if (featuresText.trim()) {
      const features = featuresText.split('\n').filter(f => f.trim()).map(f => f.trim());
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'features';
      hiddenInput.value = JSON.stringify(features);
      this.appendChild(hiddenInput);
    }
  });
</script>
@endsection












