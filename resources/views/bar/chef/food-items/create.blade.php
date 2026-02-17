@extends('layouts.dashboard')

@section('title', 'Add Food Item')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cutlery"></i> Add Food Item</h1>
    <p>Add a new food item to your menu</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.food-items') }}">Food Items</a></li>
    <li class="breadcrumb-item">Add Food Item</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Food Item Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.chef.food-items.store') }}" enctype="multipart/form-data">
          @csrf

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Food Name *</label>
                <input class="form-control @error('name') is-invalid @enderror" 
                       type="text" 
                       name="name" 
                       value="{{ old('name') }}" 
                       placeholder="e.g., Chicken Wings" 
                       required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Variant Name</label>
                <input class="form-control @error('variant_name') is-invalid @enderror" 
                       type="text" 
                       name="variant_name" 
                       value="{{ old('variant_name') }}" 
                       placeholder="e.g., 6 pieces, Large, Regular">
                @error('variant_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Price (TSh) *</label>
                <input class="form-control @error('price') is-invalid @enderror" 
                       type="number" 
                       name="price" 
                       value="{{ old('price') }}" 
                       step="0.01"
                       min="0"
                       placeholder="15000" 
                       required>
                @error('price')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Prep Time (minutes)</label>
                <input class="form-control @error('prep_time_minutes') is-invalid @enderror" 
                       type="number" 
                       name="prep_time_minutes" 
                       value="{{ old('prep_time_minutes') }}" 
                       min="0"
                       placeholder="15">
                @error('prep_time_minutes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Food Image</label>
                <input class="form-control @error('image') is-invalid @enderror" 
                       type="file" 
                       name="image" 
                       accept="image/*"
                       id="foodImage">
                <small class="form-text text-muted">Upload food image (JPG, PNG, GIF - Max 2MB)</small>
                <div id="imagePreview" class="mt-2" style="display: none;">
                  <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                </div>
                @error('image')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Sort Order</label>
                <input class="form-control @error('sort_order') is-invalid @enderror" 
                       type="number" 
                       name="sort_order" 
                       value="{{ old('sort_order', 0) }}" 
                       min="0"
                       placeholder="0">
                <small class="form-text text-muted">Lower numbers appear first</small>
                @error('sort_order')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="control-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" 
                      name="description" 
                      rows="3" 
                      placeholder="Food description (optional)">{{ old('description') }}</textarea>
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <div class="form-check">
              <input class="form-check-input" 
                     type="checkbox" 
                     name="is_available" 
                     value="1" 
                     id="is_available"
                     {{ old('is_available', true) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_available">
                Available for ordering
              </label>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i> Create Food Item
            </button>
            <a class="btn btn-secondary" href="{{ route('bar.chef.food-items') }}">
              <i class="fa fa-fw fa-lg fa-times-circle"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Image preview
  document.getElementById('foodImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('imagePreview').style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else {
      document.getElementById('imagePreview').style.display = 'none';
    }
  });
</script>
@endpush





