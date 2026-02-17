@extends('layouts.dashboard')

@section('title', 'New Stock Transfer')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> New Stock Transfer</h1>
    <p>Request stock transfer from warehouse to counter</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-transfers.index') }}">Stock Transfers</a></li>
    <li class="breadcrumb-item">New Transfer</li>
  </ul>
</div>

<div class="row">
  <!-- Stock Information Section - Left Side -->
  <div class="col-md-4">
    <div class="tile" style="background-color: #f8f9fa; border-left: 4px solid #28a745; position: sticky; top: 20px;">
      <h3 class="tile-title" style="color: #28a745;">
        <i class="fa fa-info-circle"></i> Stock Information
      </h3>
      <div class="tile-body">
        <div class="form-group">
          <label class="control-label" style="font-weight: 600;">Warehouse Stock</label>
          <div class="input-group">
            <input type="text" class="form-control" id="warehouse_stock" readonly 
                   style="background-color: #ffffff; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #dee2e6;">
            <div class="input-group-append">
              <span class="input-group-text" style="background-color: #e9ecef;">bottle(s)</span>
            </div>
          </div>
          <small class="form-text text-muted">Available in warehouse</small>
        </div>
        
        <div class="form-group">
          <label class="control-label" style="font-weight: 600;">Available Packages</label>
          <div class="input-group">
            <input type="text" class="form-control" id="available_packages" readonly 
                   style="background-color: #ffffff; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #dee2e6;">
            <div class="input-group-append">
              <span class="input-group-text" style="background-color: #e9ecef;">packages</span>
            </div>
          </div>
          <small class="form-text text-muted" id="packagingInfo"></small>
        </div>
        
        <div class="form-group">
          <label class="control-label" style="font-weight: 600;">Total Bottles to Transfer</label>
          <div class="input-group">
            <input type="text" class="form-control" id="total_units" readonly 
                   style="background-color: #d4edda; color: #155724; font-size: 1.2em; font-weight: bold; text-align: center; border: 2px solid #28a745;">
            <div class="input-group-append">
              <span class="input-group-text" style="background-color: #d4edda; color: #155724;">bottle(s)</span>
            </div>
          </div>
          <small class="form-text text-muted" style="color: #28a745;">Packages × Items per Package</small>
        </div>
        
        <div class="alert alert-info mt-3 mb-0" role="alert" style="font-size: 0.9em;">
          <i class="fa fa-info-circle"></i> <strong>Note:</strong> Select a product variant to see available stock. Transfer requests require approval.
        </div>
      </div>
    </div>
  </div>

  <!-- Form Section - Right Side -->
  <div class="col-md-8">
    <div class="tile">
      <h3 class="tile-title">Transfer Request Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.stock-transfers.store') }}" id="stockTransferForm">
          @csrf

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Product *</label>
                <select class="form-control @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                  <option value="">Select Product</option>
                  @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                      {{ $product->name }} @if($product->brand) - {{ $product->brand }} @endif
                    </option>
                  @endforeach
                </select>
                @error('product_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Product Variant *</label>
                <select class="form-control @error('product_variant_id') is-invalid @enderror" id="product_variant_id" name="product_variant_id" required>
                  <option value="">Select Product First</option>
                </select>
                @error('product_variant_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted" id="variantInfo"></small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Quantity Requested (Packages) *</label>
                <input type="number" class="form-control @error('quantity_requested') is-invalid @enderror" 
                       name="quantity_requested" 
                       id="quantity_requested"
                       value="{{ old('quantity_requested', 1) }}" 
                       min="1" 
                       required>
                @error('quantity_requested')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted" id="quantityInfo">e.g., 10 packages</small>
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
                          placeholder="Any additional notes about this transfer request">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i>Submit Transfer Request
            </button>
            <a class="btn btn-secondary" href="{{ route('bar.stock-transfers.index') }}">
              <i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  (function() {
    const productsData = @json($productsData);
    
    // Convert to object keyed by product ID for easier lookup
    const products = {};
    productsData.forEach(function(product) {
      products[product.id] = {
        id: product.id,
        name: product.name || 'Unknown Product',
        brand: product.brand || '',
        variants: product.variants || []
      };
    });

    // Store variants by ID for quick lookup
    const variantsById = {};
    productsData.forEach(function(product) {
      if (product.variants) {
        product.variants.forEach(function(variant) {
          variantsById[variant.id] = variant;
        });
      }
    });

    const productSelect = document.getElementById('product_id');
    const variantSelect = document.getElementById('product_variant_id');
    const variantInfo = document.getElementById('variantInfo');
    const quantityInfo = document.getElementById('quantityInfo');
    const quantityInput = document.getElementById('quantity_requested');
    const warehouseStockInput = document.getElementById('warehouse_stock');
    const availablePackagesInput = document.getElementById('available_packages');
    const totalUnitsInput = document.getElementById('total_units');
    const packagingInfo = document.getElementById('packagingInfo');

    let selectedVariant = null;

    function updateVariants() {
      const productId = parseInt(productSelect.value);
      variantSelect.innerHTML = '<option value="">Select Variant</option>';
      variantInfo.textContent = '';
      quantityInfo.textContent = '';
      clearStockInfo();

      if (productId && products[productId]) {
        const product = products[productId];
        if (product && product.variants && product.variants.length > 0) {
          product.variants.forEach(function(variant) {
            const option = document.createElement('option');
            option.value = variant.id;
            option.textContent = variant.measurement + ' - ' + variant.packaging + ' (Available: ' + variant.warehouse_packages + ' packages)';
            option.dataset.itemsPerPackage = variant.items_per_package;
            option.dataset.packaging = variant.packaging;
            option.dataset.warehouseQuantity = variant.warehouse_quantity;
            option.dataset.warehousePackages = variant.warehouse_packages;
            variantSelect.appendChild(option);
          });
        }
      }
    }

    function updateVariantInfo() {
      const selectedOption = variantSelect.options[variantSelect.selectedIndex];
      if (selectedOption && selectedOption.value) {
        const variantId = parseInt(selectedOption.value);
        const variant = variantsById[variantId];
        
        if (variant) {
          selectedVariant = {
            id: variant.id,
            itemsPerPackage: variant.items_per_package,
            packaging: variant.packaging,
            measurement: variant.measurement,
            warehouseQuantity: variant.warehouse_quantity,
            warehousePackages: variant.warehouse_packages
          };
          
          // Display variant information
          variantInfo.textContent = variant.measurement + ' | ' + variant.items_per_package + ' items per ' + variant.packaging;
          quantityInfo.textContent = 'e.g., 10 ' + variant.packaging.toLowerCase();
          packagingInfo.textContent = variant.packaging;
          
          // Update stock information
          warehouseStockInput.value = selectedVariant.warehouseQuantity.toLocaleString();
          availablePackagesInput.value = selectedVariant.warehousePackages;
          quantityInput.max = selectedVariant.warehousePackages;
          
          calculateTotals();
        }
      } else {
        selectedVariant = null;
        variantInfo.textContent = '';
        quantityInfo.textContent = '';
        clearStockInfo();
      }
    }

    function calculateTotals() {
      if (!selectedVariant) {
        clearStockInfo();
        return;
      }

      const quantity = parseInt(quantityInput.value) || 0;
      const totalUnits = quantity * selectedVariant.itemsPerPackage;

      totalUnitsInput.value = totalUnits.toLocaleString();
    }

    function clearStockInfo() {
      warehouseStockInput.value = '';
      availablePackagesInput.value = '';
      totalUnitsInput.value = '';
      packagingInfo.textContent = '';
      quantityInput.max = '';
    }

    // Event listeners
    productSelect.addEventListener('change', updateVariants);
    variantSelect.addEventListener('change', updateVariantInfo);
    quantityInput.addEventListener('input', calculateTotals);

    // Initialize if product is pre-selected
    if (productSelect.value) {
      updateVariants();
      const oldVariantId = '{{ old("product_variant_id") }}';
      if (oldVariantId) {
        variantSelect.value = oldVariantId;
        updateVariantInfo();
      }
    }
  })();
</script>
@endsection




