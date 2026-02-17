@extends('layouts.dashboard')

@section('title', 'Edit Product')

@section('content')
<div class="app-title bg-white shadow-sm mb-4 border-bottom">
  <div>
    <h1 class="text-dark font-weight-bold"><i class="fa fa-pencil-square-o text-primary mr-2"></i> Edit Product</h1>
    <p class="text-muted small">Update product information and handle variant-specific details.</p>
  </div>
  <ul class="app-breadcrumb breadcrumb px-3 py-2 bg-light rounded-pill">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.products.index') }}" class="text-dark">Products</a></li>
    <li class="breadcrumb-item active">Edit Product</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-9">
    <div class="tile shadow-sm border-0 rounded-lg">
      <h3 class="tile-title">General Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.products.update', $product) }}" id="productForm" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label class="control-label font-weight-bold">Supplier</label>
                <select class="form-control @error('supplier_id') is-invalid @enderror" name="supplier_id">
                  <option value="">Select Supplier (Optional)</option>
                  @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                      {{ $supplier->company_name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="control-label font-weight-bold">Brand / Manufacturer</label>
                <input class="form-control" type="text" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="e.g., TBL, Coca-Cola">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="control-label font-weight-bold">Category *</label>
                <select class="form-control @error('category') is-invalid @enderror" name="category" required>
                  <option value="">Select Category</option>
                  <option value="Soda" {{ old('category', $product->category) == 'Soda' ? 'selected' : '' }}>Soda</option>
                  <option value="Water" {{ old('category', $product->category) == 'Water' ? 'selected' : '' }}>Water</option>
                  <option value="Energies" {{ old('category', $product->category) == 'Energies' ? 'selected' : '' }}>Energies</option>
                  <option value="Beer/Lager" {{ old('category', $product->category) == 'Beer/Lager' ? 'selected' : '' }}>Beer/Lager</option>
                  <option value="Can Beer" {{ old('category', $product->category) == 'Can Beer' ? 'selected' : '' }}>Can Beer</option>
                  <option value="Wine by Bottle" {{ old('category', $product->category) == 'Wine by Bottle' ? 'selected' : '' }}>Wine by Bottle</option>
                  <option value="Brandy/Whisky/RUM/Gin" {{ old('category', $product->category) == 'Brandy/Whisky/RUM/Gin' ? 'selected' : '' }}>Brandy/Whisky/RUM/Gin</option>
                  <option value="Alcoholic Beverages" {{ old('category', $product->category) == 'Alcoholic Beverages' ? 'selected' : '' }}>Other Alcoholic Beverages</option>
                  <option value="Non-Alcoholic Beverages" {{ old('category', $product->category) == 'Non-Alcoholic Beverages' ? 'selected' : '' }}>Other Non-Alcoholic Beverages</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label class="control-label font-weight-bold">Common Description</label>
                <textarea class="form-control" name="description" rows="2" placeholder="Shared description (optional)">{{ old('description', $product->description) }}</textarea>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="control-label font-weight-bold">Global Status</label>
                <div class="form-check pt-2">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ $product->is_active ? 'checked' : '' }}>
                  <label class="form-check-label font-weight-bold" for="is_active">
                    Product is Active
                  </label>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">
          <h4 class="mb-4 font-weight-bold text-primary"><i class="fa fa-list-ul mr-2"></i>Product Variants (Items)</h4>

          <div id="variantsContainer">
            @foreach($product->variants as $index => $variant)
            <div class="variant-item mb-4 p-4 border rounded bg-light shadow-sm position-relative">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-dark">Variant Details</h5>
                @if($product->variants->count() > 1)
                <button type="button" class="btn btn-sm btn-outline-danger remove-variant px-3 h-100">
                  <i class="fa fa-trash"></i> Remove
                </button>
                @endif
              </div>
              
              <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
              
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label font-weight-bold">Product Variant Name *</label>
                    <input type="text" class="form-control" name="variants[{{ $index }}][name]" value="{{ old('variants.'.$index.'.name', $variant->name) }}" required>
                    <small class="text-muted">Descriptive name (e.g., {{ $product->brand }} {{ $variant->measurement }})</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label font-weight-bold">Variant Image</label>
                    <input type="file" class="form-control variant-image-input" name="variants[{{ $index }}][image]" accept="image/*">
                    <div class="mt-2 text-center current-v-image">
                       @if($variant->image)
                         <img src="{{ asset('storage/' . $variant->image) }}" alt="Current" class="img-thumbnail" style="max-height: 80px;">
                         <p class="small text-muted mb-0">Current Image</p>
                       @endif
                    </div>
                    <div class="mt-2 text-center variant-image-preview" style="display: none;">
                      <img src="" alt="Preview" class="img-thumbnail border-primary" style="max-height: 100px;">
                      <p class="small text-primary mb-0 font-weight-bold">New Preview</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row align-items-end mt-2">
                <div class="col-md-4 mb-3">
                  <div class="form-group">
                    <label class="control-label font-weight-bold">Measurement (Capacity) *</label>
                    <input type="text" class="form-control" name="variants[{{ $index }}][measurement]" value="{{ old('variants.'.$index.'.measurement', $variant->measurement) }}" required>
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <div class="form-group">
                    <label class="control-label font-weight-bold">Packaging Type *</label>
                    <select class="form-control packaging-select" name="variants[{{ $index }}][packaging]" required>
                      <option value="">Select Packaging</option>
                      <option value="Piece" {{ (old('variants.'.$index.'.packaging', $variant->packaging) == 'Piece') ? 'selected' : '' }}>Piece (Single Unit)</option>
                      <option value="Bottle" {{ (old('variants.'.$index.'.packaging', $variant->packaging) == 'Bottle') ? 'selected' : '' }}>Bottle (Single Unit)</option>
                      <option value="Crates" {{ (old('variants.'.$index.'.packaging', $variant->packaging) == 'Crates') ? 'selected' : '' }}>Crates (Package)</option>
                      <option value="Cartons" {{ (old('variants.'.$index.'.packaging', $variant->packaging) == 'Cartons') ? 'selected' : '' }}>Cartons (Package)</option>
                      <option value="Boxes" {{ (old('variants.'.$index.'.packaging', $variant->packaging) == 'Boxes') ? 'selected' : '' }}>Boxes (Package)</option>
                      <option value="Cans" {{ (old('variants.'.$index.'.packaging', $variant->packaging) == 'Cans') ? 'selected' : '' }}>Cans (Single Unit)</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4 mb-3 items-per-package-container">
                  <div class="form-group">
                    <label class="control-label font-weight-bold">Items per Package *</label>
                    <div class="input-group">
                      <input type="number" class="form-control" name="variants[{{ $index }}][items_per_package]" value="{{ old('variants.'.$index.'.items_per_package', $variant->items_per_package ?? 1) }}" min="1" required>
                      <div class="input-group-append">
                        <span class="input-group-text">units</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endforeach
          </div>

          <div class="text-center mb-4">
            <button type="button" class="btn btn-outline-primary px-4" id="addVariant">
              <i class="fa fa-plus-circle mr-1"></i> Add Another Size/Packaging
            </button>
          </div>

          <div class="tile-footer text-right border-top pt-4">
            <button class="btn btn-primary btn-lg shadow-sm px-5" type="submit">
              <i class="fa fa-save mr-1"></i> Update Product
            </button>
            <a class="btn btn-light btn-lg px-4 ml-2" href="{{ route('bar.products.index') }}">
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="tile shadow-sm border-0 rounded-lg bg-light text-center p-4">
        <div class="mb-3">
            @php
                $firstVariant = $product->variants->first();
            @endphp
            @if($firstVariant && $firstVariant->image)
                <img src="{{ asset('storage/' . $firstVariant->image) }}" class="img-fluid rounded shadow-sm border" style="max-height: 150px;">
            @else
                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                    <i class="fa fa-cube fa-3x"></i>
                </div>
            @endif
        </div>
        <h4 class="mb-1">{{ $product->name }}</h4>
        <p class="text-primary font-weight-bold small mb-3">{{ $product->category }}</p>
        <div class="badge badge-primary px-3 py-2 rounded-pill">
            {{ $product->variants->count() }} Variants
        </div>
    </div>
    
    <div class="tile shadow-sm border-0 rounded-lg">
        <h4 class="tile-title small text-uppercase text-muted">Quick Help</h4>
        <div class="tile-body">
            <p class="small">Updating a product brand name will affect all its variants. You can add new variants or remove existing ones using the controls on the left.</p>
        </div>
    </div>
  </div>
</div>

<style>
  .tile { border-radius: 12px !important; }
  .tile-title { font-weight: 700; color: #333; }
  .form-control:focus {
    border-color: #940000;
    box-shadow: 0 0 0 0.2rem rgba(148, 0, 0, 0.15);
  }
  .variant-item { 
    transition: all 0.3s ease;
    border: 1px solid #eee !important;
  }
  .variant-item:hover {
    border-color: #940000 !important;
    background-color: #fff !important;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  (function() {
    let variantCount = {{ $product->variants->count() }};

    // Use event delegation for packaging toggle
    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('packaging-select')) {
        const val = e.target.value;
        const row = e.target.closest('.row');
        const container = row.querySelector('.items-per-package-container');
        const input = container.querySelector('input');
        
        if (['Piece', 'Bottle', 'Cans'].includes(val)) {
          $(container).fadeOut(200);
          input.value = 1;
        } else {
          $(container).fadeIn(200);
        }
      }

      // Handle variant image preview
      if (e.target.classList.contains('variant-image-input')) {
        const file = e.target.files[0];
        const parent = e.target.closest('.form-group');
        const previewContainer = parent.querySelector('.variant-image-preview');
        const currentContainer = parent.querySelector('.current-v-image');
        const previewImg = previewContainer.querySelector('img');
        
        if (file && file.type.match('image.*') && file.size <= 2 * 1024 * 1024) {
          const reader = new FileReader();
          reader.onload = (e) => {
            previewImg.src = e.target.result;
            $(previewContainer).fadeIn(200);
            if (currentContainer) $(currentContainer).fadeOut(200);
          };
          reader.readAsDataURL(file);
        } else {
          $(previewContainer).fadeOut(200);
          if (currentContainer) $(currentContainer).fadeIn(200);
          if (file) alert('Invalid image file or too large (>2MB)');
        }
      }
    });

    function addVariant() {
      const container = document.getElementById('variantsContainer');
      const newVariant = document.createElement('div');
      newVariant.className = 'variant-item mb-4 p-4 border rounded bg-light shadow-sm position-relative';
      newVariant.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0 text-dark">New Variant Details</h5>
          <button type="button" class="btn btn-sm btn-outline-danger remove-variant px-3 h-100">
            <i class="fa fa-trash"></i> Remove
          </button>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label font-weight-bold">Product Variant Name *</label>
              <input type="text" class="form-control" name="variants[${variantCount}][name]" placeholder="e.g., Kilimanjaro 500ml" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label font-weight-bold">Variant Image</label>
              <input type="file" class="form-control variant-image-input" name="variants[${variantCount}][image]" accept="image/*">
              <div class="mt-2 text-center variant-image-preview" style="display: none;">
                <img src="" alt="Preview" class="img-thumbnail border-primary" style="max-height: 100px;">
                <p class="small text-primary mb-0 font-weight-bold">New Preview</p>
              </div>
            </div>
          </div>
        </div>

        <div class="row align-items-end mt-2">
          <div class="col-md-4 mb-3">
            <div class="form-group">
              <label class="control-label font-weight-bold">Measurement (Capacity) *</label>
              <input type="text" class="form-control" name="variants[${variantCount}][measurement]" placeholder="e.g., 500ml" required>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="form-group">
              <label class="control-label font-weight-bold">Packaging Type *</label>
              <select class="form-control packaging-select" name="variants[${variantCount}][packaging]" required>
                <option value="">Select Packaging</option>
                <option value="Piece">Piece (Single Unit)</option>
                <option value="Bottle">Bottle (Single Unit)</option>
                <option value="Crates">Crates (Package)</option>
                <option value="Cartons">Cartons (Package)</option>
                <option value="Boxes">Boxes (Package)</option>
                <option value="Cans">Cans (Single Unit)</option>
              </select>
            </div>
          </div>
          <div class="col-md-4 mb-3 items-per-package-container">
            <div class="form-group">
              <label class="control-label font-weight-bold">Items per Package *</label>
              <div class="input-group">
                <input type="number" class="form-control" name="variants[${variantCount}][items_per_package]" value="1" min="1" required>
                <div class="input-group-append">
                  <span class="input-group-text">units</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
      container.appendChild(newVariant);
      variantCount++;
      updateRemoveButtons();
    }

    function removeVariant(button) {
      const variantItems = document.querySelectorAll('.variant-item');
      if (variantItems.length > 1) {
        button.closest('.variant-item').remove();
        updateRemoveButtons();
      }
    }

    function updateRemoveButtons() {
      const variants = document.querySelectorAll('.variant-item');
      document.querySelectorAll('.remove-variant').forEach(btn => {
        btn.style.display = variants.length > 1 ? 'block' : 'none';
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      const addBtn = document.getElementById('addVariant');
      if (addBtn) addBtn.addEventListener('click', addVariant);
      
      document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-variant')) {
          removeVariant(e.target.closest('.remove-variant'));
        }
      });
      
      // Initial trigger for packaging logic
      document.querySelectorAll('.packaging-select').forEach(sel => {
        const event = new Event('change', { bubbles: true });
        sel.dispatchEvent(event);
      });
    });
  })();
</script>
@endsection
