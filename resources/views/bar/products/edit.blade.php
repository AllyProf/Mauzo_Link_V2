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
      <form method="POST" action="{{ route('bar.products.update', $product) }}" id="productForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="d-flex align-items-center mb-4">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px;">
                <i class="fa fa-info"></i>
            </div>
            <h3 class="tile-title mb-0">General Information</h3>
        </div>
        
        <div class="tile-body">
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
                <label class="control-label font-weight-bold">Brand Name <span class="text-danger">*</span></label>
                <input class="form-control @error('brand') is-invalid @enderror" type="text" name="brand" value="{{ old('brand', $product->brand) }}" required>
                @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label class="control-label font-weight-bold">Category <span class="text-danger">*</span></label>
                <select class="form-control @error('category') is-invalid @enderror" name="category" required>
                  <option value="">Select Category</option>
                  @php 
                    $cats = ['Beers', 'Spirits', 'Wines', 'Soft Drinks', 'Water', 'Energies']; 
                  @endphp
                  @foreach($cats as $cat)
                    <option value="{{ $cat }}" {{ old('category', $product->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-2">
          <div class="d-flex align-items-center mb-4 mt-4 border-top pt-4">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px;">
                  <i class="fa fa-list"></i>
              </div>
              <h3 class="tile-title mb-0">Product Sizes & Variants</h3>
          </div>
          
          <div class="tile-body">
            <div id="variantsContainer">
              @foreach($product->variants as $index => $variant)
              <div class="variant-item mb-5 p-4 border rounded bg-white position-relative shadow-sm hover-shadow">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0 font-weight-bold text-dark"><span class="badge badge-primary mr-2">{{ $index + 1 }}</span> Variant Details</h5>
                  <button type="button" class="btn btn-sm btn-outline-danger remove-variant" style="{{ $product->variants->count() > 1 ? '' : 'display: none;' }}">
                    <i class="fa fa-trash"></i> Remove Variant
                  </button>
                </div>
                
                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                
                <div class="row">
                  <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="control-label">Exact Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control variant-name-input" name="variants[{{ $index }}][name]" value="{{ old('variants.'.$index.'.name', $variant->name) }}" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="control-label">Volume / Size <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control variant-measurement-input" name="variants[{{ $index }}][measurement]" value="{{ old('variants.'.$index.'.measurement', $variant->measurement) }}" required>
                                    <div class="input-group-append">
                                        <select class="form-control variant-unit-select" name="variants[{{ $index }}][unit]" required style="border-top-left-radius: 0; border-bottom-left-radius: 0; background-color: #f8f9fa; border-left:0;">
                                            <option value="ml" {{ $variant->unit == 'ml' ? 'selected' : '' }}>ml</option>
                                            <option value="L" {{ $variant->unit == 'L' ? 'selected' : '' }}>L</option>
                                            <option value="PCS" {{ $variant->unit == 'PCS' ? 'selected' : '' }}>PCS</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Measurement Manner <span class="text-danger">*</span></label>
                                <select class="form-control packaging-select" name="variants[{{ $index }}][packaging]" required>
                                    <option value="Piece" {{ $variant->packaging == 'Piece' ? 'selected' : '' }}>Piece / Bottle</option>
                                    <option value="Carton" {{ $variant->packaging == 'Carton' ? 'selected' : '' }}>Carton</option>
                                    <option value="Crate" {{ $variant->packaging == 'Crate' ? 'selected' : '' }}>Crate</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 items-per-package-container {{ in_array($variant->packaging, ['Carton', 'Crate']) ? '' : 'd-none' }}">
                            <div class="form-group">
                                <label class="control-label">Items in Package <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="variants[{{ $index }}][items_per_package]" value="{{ $variant->items_per_package ?? 1 }}" min="1">
                                    <div class="input-group-append">
                                        <span class="input-group-text">pcs</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Selling Format <span class="text-danger">*</span></label>
                                <select class="form-control selling-type-select" name="variants[{{ $index }}][selling_type]" required>
                                    <option value="bottle" {{ $variant->selling_type == 'bottle' ? 'selected' : '' }}>Bottle Only</option>
                                    <option value="glass" {{ $variant->selling_type == 'glass' ? 'selected' : '' }}>Glass Only</option>
                                    <option value="mixed" {{ $variant->selling_type == 'mixed' ? 'selected' : '' }}>Mixed (Both)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 servings-container {{ in_array($variant->selling_type, ['glass', 'mixed']) ? '' : 'd-none' }}">
                            <div class="form-group">
                                <label class="control-label text-primary font-weight-bold">Tots per Bottle <span class="text-danger">*</span></label>
                                <input type="number" class="form-control border-primary" name="variants[{{ $index }}][total_tots]" value="{{ $variant->total_tots }}" placeholder="e.g., 30">
                            </div>
                        </div>
                    </div>
                  </div>

                  <div class="col-md-4 border-left">
                    <div class="form-group mb-0 h-100 d-flex flex-column text-center">
                      <label class="control-label font-weight-bold mb-3 d-block">Product Image</label>
                      <div class="image-upload-wrapper flex-grow-1">
                        <label class="image-upload-area" for="variant-img-{{ $index }}">
                          <input type="file" class="variant-image-input d-none" id="variant-img-{{ $index }}" name="variants[{{ $index }}][image]" accept="image/*">
                          
                          <div class="upload-placeholder {{ $variant->image ? 'd-none' : '' }}">
                                <div class="upload-icon mb-2"><i class="fa fa-cloud-upload"></i></div>
                                <span class="d-block font-weight-bold small">Click to Upload</span>
                          </div>
                          
                          <div class="variant-image-preview {{ $variant->image ? '' : 'd-none' }}">
                            <img src="{{ $variant->image ? asset('storage/' . $variant->image) : '' }}" alt="Preview">
                            <div class="change-overlay"><i class="fa fa-refresh mr-1"></i> Change Image</div>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              @endforeach
            </div>

            <div class="text-center mt-4">
              <button type="button" class="btn btn-outline-primary border-dashed px-5 py-2 font-weight-bold" id="addVariant">
                <i class="fa fa-plus-circle mr-2"></i> Add Another Size/Variant
              </button>
            </div>
          </div>
        </div>

        <div class="tile-footer border-top pt-4 mt-5 text-right">
          <a class="btn btn-light btn-lg px-4 mr-3" href="{{ route('bar.products.index') }}">
              <i class="fa fa-times-circle mr-1"></i> Cancel
          </a>
          <button class="btn btn-primary btn-lg shadow-sm px-5" type="submit">
              <i class="fa fa-save mr-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="tile shadow-sm border-0 rounded-lg bg-light text-center p-4">
        <div class="mb-3">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" class="img-fluid rounded shadow-sm border" style="max-height: 150px;">
            @else
                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                    <i class="fa fa-cube fa-3x"></i>
                </div>
            @endif
        </div>
        <h4 class="mb-1 font-weight-bold">{{ $product->name }}</h4>
        <p class="text-primary font-weight-bold small mb-3 text-uppercase">{{ $product->category }}</p>
        <div class="badge badge-primary px-3 py-2 rounded-pill">
            {{ $product->variants->count() }} Variants Found
        </div>
    </div>
    
    <div class="tile shadow-sm border-0 rounded-lg">
        <h4 class="tile-title small text-uppercase text-muted"><i class="fa fa-question-circle mr-2"></i> Quick Help</h4>
        <div class="tile-body">
            <p class="small text-muted">Updating the brand name will automatically update all variant names unless they were manually edited.</p>
            <p class="small text-muted mb-0">Changes here are immediate once saved. Stock levels can be updated via the "Stock Reception" module.</p>
        </div>
    </div>
  </div>
</div>

<style>
  .variant-item { transition: all 0.3s; border: 1px solid #e0e0e0 !important; border-radius: 12px !important; }
  .variant-item:hover { border-color: #940000 !important; box-shadow: 0 10px 25px rgba(148,0,0,0.1) !important; }
  .form-control:focus { border-color: #940000; box-shadow: 0 0 0 0.2rem rgba(148, 0, 0, 0.15); }
  .tile-title { font-weight: 700; color: #333; letter-spacing: -0.5px; }
  
  /* Image Upload Area */
  .image-upload-area { display: flex; align-items: center; justify-content: center; width: 100%; height: 160px; border: 2px dashed #ccd1d9; background-color: #f9fafb; border-radius: 10px; cursor: pointer; transition: all 0.25s; overflow: hidden; position: relative; }
  .image-upload-area:hover { border-color: #940000; background-color: #f2f4f7; }
  .variant-image-preview { width: 100%; height: 100%; position: absolute; top: 0; left: 0; background: white; display: flex; align-items: center; justify-content: center; }
  .variant-image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
  .change-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(148, 0, 0, 0.8); color: white; font-size: 0.75rem; padding: 4px; text-align: center; opacity: 0; transition: opacity 0.2s; }
  .image-upload-area:hover .change-overlay { opacity: 1; }
  .upload-icon i { font-size: 32px; color: #940000; opacity: 0.6; }
  .border-dashed { border-style: dashed !important; border-width: 2px !important; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  (function() {
    let variantCount = {{ $product->variants->count() }};

    function toggleServings(select) {
        const item = select.closest('.variant-item');
        const servingsContainer = item.querySelector('.servings-container');
        const servingsInput = servingsContainer.querySelector('input');
        if (select.value === 'glass' || select.value === 'mixed') {
            servingsContainer.classList.remove('d-none');
            servingsInput.setAttribute('required', 'required');
        } else {
            servingsContainer.classList.add('d-none');
            servingsInput.removeAttribute('required');
        }
    }

    function togglePackaging(select) {
        const item = select.closest('.variant-item');
        const itemsContainer = item.querySelector('.items-per-package-container');
        const itemsInput = itemsContainer.querySelector('input');
        if (select.value === 'Carton' || select.value === 'Crate') {
            itemsContainer.classList.remove('d-none');
            itemsInput.setAttribute('required', 'required');
        } else {
            itemsContainer.classList.add('d-none');
            itemsInput.removeAttribute('required');
        }
    }

    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('selling-type-select')) toggleServings(e.target);
      if (e.target.classList.contains('packaging-select')) togglePackaging(e.target);
      
      if (e.target.classList.contains('variant-image-input')) {
          const file = e.target.files[0];
          const item = e.target.closest('.variant-item');
          const preview = item.querySelector('.variant-image-preview');
          const placeholder = item.querySelector('.upload-placeholder');
          if (file) {
              const reader = new FileReader();
              reader.onload = function(event) {
                  preview.querySelector('img').src = event.target.result;
                  preview.classList.remove('d-none');
                  placeholder.classList.add('d-none');
              };
              reader.readAsDataURL(file);
          }
      }
    });

    function addVariant() {
      const container = document.getElementById('variantsContainer');
      const newIndex = variantCount;
      const brand = document.querySelector('input[name="brand"]').value;
      
      const newVariant = document.createElement('div');
      newVariant.className = 'variant-item mb-5 p-4 border rounded bg-white position-relative shadow-sm hover-shadow';
      newVariant.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="mb-0 font-weight-bold text-dark"><span class="badge badge-primary mr-2">${newIndex + 1}</span> Variant Details</h5>
          <button type="button" class="btn btn-sm btn-outline-danger remove-variant px-3"><i class="fa fa-trash"></i> Remove</button>
        </div>
        <div class="row">
          <div class="col-md-8">
            <div class="row">
                <div class="col-md-7"><div class="form-group"><label class="control-label">Name *</label><input type="text" class="form-control" name="variants[${newIndex}][name]" value="${brand} NEW" required></div></div>
                <div class="col-md-5"><div class="form-group"><label class="control-label">Size *</label><div class="input-group"><input type="number" class="form-control" name="variants[${newIndex}][measurement]" value="500" required><div class="input-group-append"><select class="form-control" name="variants[${newIndex}][unit]"><option value="ml">ml</option><option value="L">L</option><option value="PCS">PCS</option></select></div></div></div></div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4"><div class="form-group"><label class="control-label">Packaging *</label><select class="form-control packaging-select" name="variants[${newIndex}][packaging]"><option value="Piece">Piece</option><option value="Carton">Carton</option><option value="Crate">Crate</option></select></div></div>
                <div class="col-md-4 items-per-package-container d-none"><div class="form-group"><label class="control-label">Items *</label><input type="number" class="form-control" name="variants[${newIndex}][items_per_package]" value="1"></div></div>
                <div class="col-md-4"><div class="form-group"><label class="control-label">Format *</label><select class="form-control selling-type-select" name="variants[${newIndex}][selling_type]"><option value="bottle">Bottle</option><option value="glass">Glass</option><option value="mixed">Mixed</option></select></div></div>
                <div class="col-md-4 servings-container d-none"><div class="form-group"><label class="control-label">Shots *</label><input type="number" class="form-control" name="variants[${newIndex}][total_tots]" placeholder="e.g. 30"></div></div>
            </div>
          </div>
          <div class="col-md-4 border-left">
            <div class="form-group mb-0 h-100 d-flex flex-column text-center">
                <label class="font-weight-bold mb-3">Image</label>
                <div class="image-upload-wrapper flex-grow-1">
                    <label class="image-upload-area" for="variant-img-${newIndex}">
                        <input type="file" class="variant-image-input d-none" id="variant-img-${newIndex}" name="variants[${newIndex}][image]" accept="image/*">
                        <div class="upload-placeholder"><div class="upload-icon"><i class="fa fa-cloud-upload"></i></div><span class="small">Click to Upload</span></div>
                        <div class="variant-image-preview d-none"><img src="" alt="Preview"><div class="change-overlay"><i class="fa fa-refresh mr-1"></i> Change</div></div>
                    </label>
                </div>
            </div>
          </div>
        </div>
      `;
      container.appendChild(newVariant);
      variantCount++;
      updateRemoveButtons();
    }

    function reindexVariants() {
      const items = document.querySelectorAll('.variant-item');
      items.forEach((item, index) => {
          item.querySelector('.badge').textContent = index + 1;
          item.querySelectorAll('input, select').forEach(el => {
              if (el.name) el.name = el.name.replace(/variants\[\d+\]/, `variants[${index}]`);
              if (el.id) el.id = el.id.replace(/variant-img-\d+/, `variant-img-${index}`);
          });
          item.querySelectorAll('label').forEach(el => {
              if (el.htmlFor) el.htmlFor = el.htmlFor.replace(/variant-img-\d+/, `variant-img-${index}`);
          });
      });
      variantCount = items.length;
    }

    function updateRemoveButtons() {
      const variants = document.querySelectorAll('.variant-item');
      document.querySelectorAll('.remove-variant').forEach(btn => btn.style.display = variants.length > 1 ? 'block' : 'none');
    }

    document.getElementById('addVariant').addEventListener('click', addVariant);
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-variant')) {
            e.target.closest('.variant-item').remove();
            updateRemoveButtons();
            reindexVariants();
        }
    });

  })();
</script>
@endsection
