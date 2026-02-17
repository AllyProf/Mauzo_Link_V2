@extends('layouts.dashboard')

@section('title', 'Add Product')

@section('content')
<div class="app-title bg-white shadow-sm mb-4 border-bottom">
  <div>
    <h1 class="text-dark font-weight-bold"><i class="fa fa-cube text-primary mr-2"></i> Add Product</h1>
    <p class="text-muted small">Register new products and their variants with high-precision data.</p>
  </div>
  <ul class="app-breadcrumb breadcrumb px-3 py-2 bg-light rounded-pill">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-dark">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.products.index') }}" class="text-dark">Products</a></li>
    <li class="breadcrumb-item active">Add Product</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-9">
    <div class="tile shadow-sm border-0 rounded-lg">
      <form method="POST" action="{{ route('bar.products.store') }}" id="productForm" enctype="multipart/form-data">
        @csrf
        
        <div class="d-flex align-items-center mb-4">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px;">
                <i class="fa fa-info"></i>
            </div>
            <h3 class="tile-title mb-0">General Information</h3>
        </div>
        
        <div class="tile-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label font-weight-bold">Brand Name <span class="text-danger">*</span></label>
                <input class="form-control form-control-lg @error('brand') is-invalid @enderror" type="text" name="brand" value="{{ old('brand') }}" placeholder="e.g., Kilimanjaro, Coca-Cola" required>
                @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label font-weight-bold">Category <span class="text-danger">*</span></label>
                <select class="form-control form-control-lg @error('category') is-invalid @enderror" name="category" required>
                  <option value="">Select Category</option>
                  <option value="Beers" {{ old('category') == 'Beers' ? 'selected' : '' }}>Beers</option>
                  <option value="Spirits" {{ old('category') == 'Spirits' ? 'selected' : '' }}>Spirits</option>
                  <option value="Wines" {{ old('category') == 'Wines' ? 'selected' : '' }}>Wines</option>
                  <option value="Soft Drinks" {{ old('category') == 'Soft Drinks' ? 'selected' : '' }}>Soft Drinks</option>
                  <option value="Water" {{ old('category') == 'Water' ? 'selected' : '' }}>Water</option>
                  <option value="Energies" {{ old('category') == 'Energies' ? 'selected' : '' }}>Energies</option>
                </select>
                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
            <p class="text-muted mb-4 ml-2"><i class="fa fa-lightbulb-o mr-1 text-warning"></i> Add multiple variants for different sizes (e.g. 350ml and 500ml) under the same brand.</p>
            
            <div id="variantsContainer">
              <!-- Variant Item Template -->
              <div class="variant-item mb-5 p-4 border rounded bg-white position-relative shadow-sm hover-shadow">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h5 class="mb-0 font-weight-bold text-dark"><span class="badge badge-primary mr-2">1</span> Variant Details</h5>
                  <button type="button" class="btn btn-sm btn-outline-danger remove-variant" style="display: none;">
                    <i class="fa fa-trash"></i> Remove Variant
                  </button>
                </div>
                
                <div class="row">
                  <!-- Left side: Form Fields -->
                  <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="control-label">Exact Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control variant-name-input" name="variants[0][name]" placeholder="e.g., Kilimanjaro 500ml" required>
                                <small class="text-muted">Display name for POS and receipts</small>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="control-label">Volume / Size <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control variant-measurement-input" name="variants[0][measurement]" placeholder="500" required>
                                    <div class="input-group-append">
                                        <select class="form-control variant-unit-select" name="variants[0][unit]" required style="border-top-left-radius: 0; border-bottom-left-radius: 0; background-color: #f8f9fa; border-left:0;">
                                            <option value="ml">ml</option>
                                            <option value="L">L</option>
                                            <option value="PCS">PCS</option>
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
                                <select class="form-control packaging-select" name="variants[0][packaging]" required>
                                    <option value="Piece">Piece / Bottle</option>
                                    <option value="Carton">Carton</option>
                                    <option value="Crate">Crate</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 items-per-package-container d-none">
                            <div class="form-group">
                                <label class="control-label">Items in Package <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="variants[0][items_per_package]" value="1" min="1">
                                    <div class="input-group-append">
                                        <span class="input-group-text">pcs</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Selling Format <span class="text-danger">*</span></label>
                                <select class="form-control selling-type-select" name="variants[0][selling_type]" required>
                                    <option value="bottle">Bottle Only</option>
                                    <option value="glass">Glass Only</option>
                                    <option value="mixed">Mixed (Both)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 servings-container d-none">
                            <div class="form-group">
                                <label class="control-label text-primary font-weight-bold">Tots per Bottle <span class="text-danger">*</span></label>
                                <input type="number" class="form-control border-primary" name="variants[0][total_tots]" placeholder="e.g., 30">
                            </div>
                        </div>
                    </div>
                  </div>

                  <!-- Right side: Modern Image Upload -->
                  <div class="col-md-4 border-left">
                    <div class="form-group mb-0 h-100 d-flex flex-column">
                      <label class="control-label font-weight-bold mb-3 d-block text-center">Product Image</label>
                      
                      <div class="image-upload-wrapper flex-grow-1">
                        <label class="image-upload-area" for="variant-img-0">
                          <input type="file" class="variant-image-input d-none" id="variant-img-0" name="variants[0][image]" accept="image/*">
                          
                          <div class="upload-placeholder text-center">
                            <div class="upload-icon mb-2">
                                <i class="fa fa-cloud-upload"></i>
                            </div>
                            <span class="d-block font-weight-bold small">Click to Upload</span>
                            <span class="text-muted smallest">JPG, PNG allowed</span>
                          </div>
                          
                          <div class="variant-image-preview d-none">
                            <img src="" alt="Preview">
                            <div class="change-overlay">
                                <i class="fa fa-refresh mr-1"></i> Change
                            </div>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
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
              <i class="fa fa-check-circle mr-1"></i> Complete Registration
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="tile shadow-sm border-0 rounded-lg bg-light">
        <h4 class="tile-title"><i class="fa fa-lightbulb-o text-warning mr-2"></i> Tips</h4>
        <div class="tile-body">
            <ul class="list-unstyled">
                <li class="mb-3">
                    <small class="font-weight-bold text-uppercase d-block text-muted">Brand Name</small>
                    <p class="small text-dark">Enter the brand name clearly (e.g., Kilimanjaro, Pepsi). This helps in grouping variants.</p>
                </li>
                <li class="mb-3">
                    <small class="font-weight-bold text-uppercase d-block text-muted">Variants</small>
                    <p class="small text-dark">You can add multiple sizes for the same brand. For example, add both 500ml and 1.5L bottles here.</p>
                </li>
                <li class="mb-3">
                    <small class="font-weight-bold text-uppercase d-block text-muted">Images</small>
                    <p class="small text-dark">High-quality images help staff identify products quickly during sales.</p>
                </li>
                <li>
                    <small class="font-weight-bold text-uppercase d-block text-muted">Selling Format</small>
                    <p class="small text-dark">Choose "Glass Only" if you sell spirits by the shot/tot.</p>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="tile shadow-sm border-0 rounded-lg bg-dark text-white">
        <h4 class="tile-title text-white">System Status</h4>
        <div class="tile-body">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-check-circle text-success mr-2"></i> 
                <span class="small">Database Connected</span>
            </div>
            <div class="d-flex align-items-center">
                <i class="fa fa-shield text-info mr-2"></i>
                <span class="small">Secure Session Active</span>
            </div>
        </div>
    </div>
  </div>
</div>

<style>
  /* Premium Design Tweaks */
  .variant-item {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: 1px solid #e0e0e0 !important;
    border-radius: 12px !important;
  }
  .variant-item:hover {
    border-color: #940000 !important;
    box-shadow: 0 10px 25px rgba(148,0,0,0.1) !important;
  }
  .form-control:focus {
    border-color: #940000;
    box-shadow: 0 0 0 0.2rem rgba(148, 0, 0, 0.15);
  }
  .tile-title {
    font-weight: 700;
    color: #333;
    letter-spacing: -0.5px;
  }
  .hover-shadow:hover {
    transform: translateY(-2px);
  }
  .control-label {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #555;
    margin-bottom: 8px;
  }
  
  /* Modern Image Upload Styling */
  .image-upload-wrapper {
    min-height: 160px;
    position: relative;
  }
  .image-upload-area {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border: 2px dashed #ccd1d9;
    background-color: #f9fafb;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.25s;
    overflow: hidden;
    position: relative;
    margin-bottom: 0;
  }
  .image-upload-area:hover {
    border-color: #940000;
    background-color: #f2f4f7;
  }
  .upload-icon i {
    font-size: 32px;
    color: #940000;
    opacity: 0.6;
  }
  .smallest { font-size: 0.7rem; }
  
  .variant-image-preview {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .variant-image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
  .change-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(148, 0, 0, 0.8);
    color: white;
    font-size: 0.75rem;
    padding: 4px;
    text-align: center;
    opacity: 0;
    transition: opacity 0.2s;
  }
  .image-upload-area:hover .change-overlay {
    opacity: 1;
  }
  
  .border-dashed {
    border-style: dashed !important;
    border-width: 2px !important;
  }
  .badge-primary { background-color: #940000; }
  .form-control-lg { border-radius: 8px; }
  .input-group-text { font-size: 0.8rem; background: #eee; border-left:0; }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
  (function() {
    let variantCount = 1;

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
            itemsInput.value = 1;
        }
    }

    function updateVariantName(card) {
        const brand = document.querySelector('input[name="brand"]').value;
        const nameInput = card.querySelector('.variant-name-input');
        const sizeInput = card.querySelector('.variant-measurement-input');
        const unitSelect = card.querySelector('.variant-unit-select');
        
        if (brand && sizeInput.value && !nameInput.dataset.manual) {
            nameInput.value = brand + ' ' + sizeInput.value + unitSelect.value;
        }
    }

    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('selling-type-select')) {
          toggleServings(e.target);
      }
      
      if (e.target.classList.contains('packaging-select')) {
          togglePackaging(e.target);
      }
      
      if (e.target.classList.contains('variant-image-input')) {
          const file = e.target.files[0];
          const item = e.target.closest('.variant-item');
          const preview = item.querySelector('.variant-image-preview');
          const placeholder = item.querySelector('.upload-placeholder');
          const img = preview.querySelector('img');
          
          if (file) {
              const reader = new FileReader();
              reader.onload = function(event) {
                  img.src = event.target.result;
                  preview.classList.remove('d-none');
                  placeholder.classList.add('d-none');
              };
              reader.readAsDataURL(file);
          }
      }

      if (e.target.name === 'brand' || e.target.classList.contains('variant-measurement-input') || e.target.classList.contains('variant-unit-select')) {
          document.querySelectorAll('.variant-item').forEach(item => updateVariantName(item));
      }
    });

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('variant-name-input')) {
            e.target.dataset.manual = "true";
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
          <button type="button" class="btn btn-sm btn-outline-danger remove-variant px-3">
            <i class="fa fa-trash"></i> Remove Variant
          </button>
        </div>
        
        <div class="row">
          <div class="col-md-8">
            <div class="row">
                <div class="col-md-7">
                    <div class="form-group">
                        <label class="control-label">Exact Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control variant-name-input" name="variants[${newIndex}][name]" placeholder="e.g., ${brand ? brand + ' ' : ''}500ml" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label class="control-label">Volume / Size <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control variant-measurement-input" name="variants[${newIndex}][measurement]" placeholder="500" required>
                            <div class="input-group-append">
                                <select class="form-control variant-unit-select" name="variants[${newIndex}][unit]" required style="border-top-left-radius: 0; border-bottom-left-radius: 0; background-color: #f8f9fa; border-left:0;">
                                    <option value="ml">ml</option>
                                    <option value="L">L</option>
                                    <option value="PCS">PCS</option>
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
                        <select class="form-control packaging-select" name="variants[${newIndex}][packaging]" required>
                            <option value="Piece">Piece / Bottle</option>
                            <option value="Carton">Carton</option>
                            <option value="Crate">Crate</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 items-per-package-container d-none">
                    <div class="form-group">
                        <label class="control-label">Items in Package <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="variants[${newIndex}][items_per_package]" value="1" min="1">
                            <div class="input-group-append">
                                <span class="input-group-text">pcs</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label">Selling Format <span class="text-danger">*</span></label>
                        <select class="form-control selling-type-select" name="variants[${newIndex}][selling_type]" required>
                            <option value="bottle">Bottle Only</option>
                            <option value="glass">Glass Only</option>
                            <option value="mixed">Mixed (Both)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 servings-container d-none">
                    <div class="form-group">
                        <label class="control-label text-primary font-weight-bold">Tots per Bottle <span class="text-danger">*</span></label>
                        <input type="number" class="form-control border-primary" name="variants[${newIndex}][total_tots]" placeholder="e.g., 30">
                    </div>
                </div>
            </div>
          </div>

          <div class="col-md-4 border-left">
            <div class="form-group mb-0 h-100 d-flex flex-column">
              <label class="control-label font-weight-bold mb-3 d-block text-center">Product Image</label>
              <div class="image-upload-wrapper flex-grow-1">
                <label class="image-upload-area" for="variant-img-${newIndex}">
                  <input type="file" class="variant-image-input d-none" id="variant-img-${newIndex}" name="variants[${newIndex}][image]" accept="image/*">
                  <div class="upload-placeholder text-center">
                    <div class="upload-icon mb-2"><i class="fa fa-cloud-upload"></i></div>
                    <span class="d-block font-weight-bold small">Click to Upload</span>
                    <span class="text-muted smallest">JPG, PNG allowed</span>
                  </div>
                  <div class="variant-image-preview d-none">
                    <img src="" alt="Preview">
                    <div class="change-overlay"><i class="fa fa-refresh mr-1"></i> Change</div>
                  </div>
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

    function updateRemoveButtons() {
      const variants = document.querySelectorAll('.variant-item');
      document.querySelectorAll('.remove-variant').forEach(btn => {
        btn.style.display = variants.length > 1 ? 'block' : 'none';
      });
    }

    function reindexVariants() {
      const items = document.querySelectorAll('.variant-item');
      items.forEach((item, index) => {
          item.querySelector('.badge').textContent = index + 1;
          item.querySelectorAll('input, select, label').forEach(el => {
              if (el.name) {
                  el.name = el.name.replace(/variants\[\d+\]/, `variants[${index}]`);
              }
              if (el.htmlFor) {
                  el.htmlFor = el.htmlFor.replace(/variant-img-\d+/, `variant-img-${index}`);
              }
              if (el.id) {
                  el.id = el.id.replace(/variant-img-\d+/, `variant-img-${index}`);
              }
          });
      });
      variantCount = items.length;
    }

    document.addEventListener('DOMContentLoaded', function() {
      const addBtn = document.getElementById('addVariant');
      if (addBtn) addBtn.addEventListener('click', addVariant);
      
      document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-variant')) {
           e.target.closest('.variant-item').remove();
           updateRemoveButtons();
           reindexVariants();
        }
      });
    });
  })();
</script>
@endsection
