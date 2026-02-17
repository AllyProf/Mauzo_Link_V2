@extends('layouts.dashboard')

@section('title', 'New Stock Receipt')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-download"></i> New Stock Receipt</h1>
    <p>Record stock received from supplier</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-receipts.index') }}">Stock Receipts</a></li>
    <li class="breadcrumb-item">New Stock Receipt</li>
  </ul>
</div>

<div class="row">
  <!-- Calculation Summary Section - Left Side -->
  <div class="col-md-4">
    <div class="tile" style="background-color: #f8f9fa; border-left: 4px solid #007bff; position: sticky; top: 20px;">
      <h3 class="tile-title" style="color: #007bff;">
        <i class="fa fa-calculator"></i> Calculation Summary
      </h3>
      <div class="tile-body">
        <div class="form-group">
          <label class="control-label" style="font-weight: 600;" id="totalPackagesLabel">Total Packages</label>
          <div class="input-group">
            <input type="text" class="form-control" id="total_packages" readonly 
                   style="background-color: #ffffff; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #dee2e6;">
            <div class="input-group-append">
              <span class="input-group-text" style="background-color: #e9ecef;" id="packageUnit">packages</span>
            </div>
          </div>
          <small class="form-text text-muted">Quantity Received</small>
        </div>
        
        <div class="form-group">
          <label class="control-label" style="font-weight: 600;">Total Bottles</label>
          <div class="input-group">
            <input type="text" class="form-control" id="total_units" readonly 
                   style="background-color: #ffffff; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #dee2e6;">
            <div class="input-group-append">
              <span class="input-group-text" style="background-color: #e9ecef;">bottle(s)</span>
            </div>
          </div>
          <small class="form-text text-muted">Packages × Items per Package</small>
        </div>
        
        <div class="form-group">
          <label class="control-label" style="font-weight: 600;">Profit per Bottle</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text" style="background-color: #e9ecef;">TSh</span>
            </div>
            <input type="text" class="form-control" id="profit_per_unit" readonly 
                   style="background-color: #ffffff; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #dee2e6;">
          </div>
          <small class="form-text text-muted">Selling Price - Buying Price</small>
        </div>
        
        <div class="form-group">
          <label class="control-label" style="font-weight: 600;">Total Buying Cost</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text" style="background-color: #e9ecef;">TSh</span>
            </div>
            <input type="text" class="form-control" id="total_buying_cost" readonly 
                   style="background-color: #ffffff; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #dee2e6;">
          </div>
          <small class="form-text text-muted">Total Bottles × Buying Price</small>
        </div>
        
        <div class="form-group" id="discountSection" style="display: none;">
          <label class="control-label" style="font-weight: 600; color: #ff9800;">Discount</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text" style="background-color: #fff3e0; color: #e65100;">TSh</span>
            </div>
            <input type="text" class="form-control" id="discount_value" readonly 
                   style="background-color: #fff3e0; color: #e65100; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #ff9800;">
          </div>
          <small class="form-text text-muted" id="discountLabel"></small>
        </div>
        
        <div class="form-group" id="finalCostSection" style="display: none;">
          <label class="control-label" style="font-weight: 600; color: #2196f3;">Final Buying Cost</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text" style="background-color: #e3f2fd; color: #1565c0;">TSh</span>
            </div>
            <input type="text" class="form-control" id="final_buying_cost" readonly 
                   style="background-color: #e3f2fd; color: #1565c0; font-size: 1.1em; font-weight: 600; text-align: center; border: 2px solid #2196f3;">
          </div>
          <small class="form-text text-muted">After Discount</small>
        </div>
        
        <div class="form-group">
          <label class="control-label" style="font-weight: 600; color: #28a745;">Total Profit</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text" style="background-color: #d4edda; color: #155724;">TSh</span>
            </div>
            <input type="text" class="form-control" id="total_profit" readonly 
                   style="background-color: #d4edda; color: #155724; font-size: 1.2em; font-weight: bold; text-align: center; border: 2px solid #28a745;">
          </div>
          <small class="form-text text-muted" style="color: #28a745;">Total Bottles × Profit per Bottle</small>
        </div>
        
        <div class="alert alert-info mt-3 mb-0" role="alert" style="font-size: 0.9em;">
          <i class="fa fa-info-circle"></i> <strong>Note:</strong> Calculations update automatically as you enter values.
        </div>
      </div>
    </div>
  </div>

  <!-- Form Section - Right Side -->
  <div class="col-md-8">
    <div class="tile">
      <h3 class="tile-title">Stock Receipt Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.stock-receipts.store') }}" id="stockReceiptForm">
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
                <div id="existingStockInfo" class="mt-2" style="display: none;">
                  <div class="alert alert-info mb-0 py-2" style="font-size: 0.9em;">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Existing Stock:</strong> 
                    <span id="existingStockText"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Supplier *</label>
                <select class="form-control @error('supplier_id') is-invalid @enderror" name="supplier_id" required>
                  <option value="">Select Supplier</option>
                  @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                      {{ $supplier->company_name }}
                    </option>
                  @endforeach
                </select>
                @error('supplier_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Quantity Received (Packages) *</label>
                <input type="number" class="form-control @error('quantity_received') is-invalid @enderror" 
                       name="quantity_received" 
                       id="quantity_received"
                       value="{{ old('quantity_received', 1) }}" 
                       min="1" 
                       required>
                @error('quantity_received')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted" id="quantityInfo">e.g., 10 crates</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Buying Price per Bottle (TSh) *</label>
                <input type="number" class="form-control @error('buying_price_per_unit') is-invalid @enderror" 
                       name="buying_price_per_unit" 
                       id="buying_price_per_unit"
                       value="{{ old('buying_price_per_unit') }}" 
                       step="0.01" 
                       min="0" 
                       required>
                @error('buying_price_per_unit')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Selling Price per Bottle (TSh) *</label>
                <input type="number" class="form-control @error('selling_price_per_unit') is-invalid @enderror" 
                       name="selling_price_per_unit" 
                       id="selling_price_per_unit"
                       value="{{ old('selling_price_per_unit') }}" 
                       step="0.01" 
                       min="0" 
                       required>
                @error('selling_price_per_unit')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <!-- Price per Tot (Conditional) -->
          <div class="row" id="totPriceRow" style="display: none;">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Selling Price per Shot/Tot (TSh) *</label>
                <input type="number" class="form-control @error('selling_price_per_tot') is-invalid @enderror" 
                       name="selling_price_per_tot" 
                       id="selling_price_per_tot"
                       value="{{ old('selling_price_per_tot') }}" 
                       step="0.01" 
                       min="0">
                @error('selling_price_per_tot')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Set price for individual shots/glasses</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Discount Type (Optional)</label>
                <select class="form-control @error('discount_type') is-invalid @enderror" 
                        name="discount_type" 
                        id="discount_type">
                  <option value="">None</option>
                  <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                  <option value="percent" {{ old('discount_type') == 'percent' ? 'selected' : '' }}>Percentage</option>
                </select>
                @error('discount_type')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted" id="discountHint">Select discount type</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Discount Amount</label>
                <div class="input-group">
                  <div class="input-group-prepend" id="discountPrefix" style="display: none;">
                    <span class="input-group-text" id="discountPrefixText">TSh</span>
                  </div>
                  <input type="number" 
                         class="form-control @error('discount_amount') is-invalid @enderror" 
                         name="discount_amount" 
                         id="discount_amount"
                         value="{{ old('discount_amount') }}" 
                         step="0.01" 
                         min="0" 
                         placeholder="0.00"
                         style="display: none;">
                  <div class="input-group-append" id="discountSuffix" style="display: none;">
                    <span class="input-group-text" id="discountSuffixText">%</span>
                  </div>
                  @error('discount_amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <small class="form-text text-muted" id="discountAmountHint">Enter discount amount</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Received Date *</label>
                <input type="date" class="form-control @error('received_date') is-invalid @enderror" 
                       name="received_date" 
                       value="{{ old('received_date', date('Y-m-d')) }}" 
                       required>
                @error('received_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Expiry Date (Optional)</label>
                <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" 
                       name="expiry_date" 
                       value="{{ old('expiry_date') }}">
                @error('expiry_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
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
                          placeholder="Any additional notes about this stock receipt">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          {{-- Barcode Preview Section --}}
          <div class="row mt-4" id="barcodePreviewSection" style="display: none;">
            <div class="col-md-12">
              <div class="tile" style="background-color: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h3 class="tile-title"><i class="fa fa-barcode"></i> Barcode Preview</h3>
                  <button type="button" class="btn btn-sm btn-primary" onclick="printBarcodePreview()">
                    <i class="fa fa-print"></i> Print Preview
                  </button>
                </div>
                <div class="tile-body">
                  <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Barcodes will be generated after saving the stock receipt. You can print them from the receipt details page.
                  </div>
                  <div class="row" id="barcodePreviewContainer">
                    <!-- Barcodes will be generated here dynamically -->
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i>Save Stock Receipt
            </button>
            <a class="btn btn-secondary" href="{{ route('bar.stock-receipts.index') }}">
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
<!-- JsBarcode Library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script type="text/javascript">
  (function() {
    const productsData = @json($productsData);
    
    // Convert to object keyed by product ID for easier lookup
    const products = {};
    productsData.forEach(function(product) {
      products[product.id] = product;
    });

    const productSelect = document.getElementById('product_id');
    const variantSelect = document.getElementById('product_variant_id');
    const variantInfo = document.getElementById('variantInfo');
    const quantityInfo = document.getElementById('quantityInfo');
    const quantityInput = document.getElementById('quantity_received');
    const buyingPriceInput = document.getElementById('buying_price_per_unit');
    const sellingPriceInput = document.getElementById('selling_price_per_unit');
    const totPriceRow = document.getElementById('totPriceRow');
    const totPriceInput = document.getElementById('selling_price_per_tot');
    const discountTypeSelect = document.getElementById('discount_type');
    const discountAmountInput = document.getElementById('discount_amount');
    const discountHint = document.getElementById('discountHint');
    const discountAmountHint = document.getElementById('discountAmountHint');
    const profitPerUnitInput = document.getElementById('profit_per_unit');
    const totalPackagesInput = document.getElementById('total_packages');
    const totalPackagesLabel = document.getElementById('totalPackagesLabel');
    const packageUnitText = document.getElementById('packageUnit');
    const totalUnitsInput = document.getElementById('total_units');
    const totalBuyingCostInput = document.getElementById('total_buying_cost');
    const discountValueInput = document.getElementById('discount_value');
    const finalBuyingCostInput = document.getElementById('final_buying_cost');
    const discountSection = document.getElementById('discountSection');
    const finalCostSection = document.getElementById('finalCostSection');
    const discountLabel = document.getElementById('discountLabel');
    const totalProfitInput = document.getElementById('total_profit');

    let selectedVariant = null;

    // Store variants by ID for quick lookup
    const variantsById = {};
    productsData.forEach(function(product) {
      if (product.variants) {
        product.variants.forEach(function(variant) {
          variantsById[variant.id] = variant;
        });
      }
    });

    function updateVariants() {
      const productId = parseInt(productSelect.value);
      variantSelect.innerHTML = '<option value="">Select Variant</option>';
      variantInfo.textContent = '';
      quantityInfo.textContent = '';
      
      // Clear prices when product changes
      buyingPriceInput.value = '';
      sellingPriceInput.value = '';
      clearCalculations();

      if (productId && products[productId]) {
        const product = products[productId];
        if (product && product.variants && product.variants.length > 0) {
          product.variants.forEach(function(variant) {
            const option = document.createElement('option');
            option.value = variant.id;
            option.textContent = variant.measurement + ' - ' + variant.packaging;
            option.dataset.itemsPerPackage = variant.items_per_package;
            option.dataset.packaging = variant.packaging;
            variantSelect.appendChild(option);
          });
        }
      }
    }

    function updateVariantInfo() {
      const selectedOption = variantSelect.options[variantSelect.selectedIndex];
      const existingStockInfo = document.getElementById('existingStockInfo');
      const existingStockText = document.getElementById('existingStockText');
      
      if (selectedOption && selectedOption.value) {
        const variantId = parseInt(selectedOption.value);
        const variant = variantsById[variantId];
        
        if (variant) {
          selectedVariant = {
            id: variant.id,
            itemsPerPackage: variant.items_per_package,
            packaging: variant.packaging,
            measurement: variant.measurement,
            buyingPrice: variant.buying_price_per_unit,
            sellingPrice: variant.selling_price_per_unit
          };
          
          // Display variant information
          variantInfo.textContent = variant.measurement + ' | ' + variant.items_per_package + ' items per ' + variant.packaging;
          quantityInfo.textContent = 'e.g., 10 ' + variant.packaging.toLowerCase();
          
          // Display existing stock
          if (variant.existing_quantity !== undefined) {
            const existingQuantity = variant.existing_quantity || 0;
            const existingPackages = variant.existing_packages || 0;
            const packagingLower = variant.packaging.toLowerCase();
            
            if (existingQuantity > 0) {
              let stockText = '';
              if (existingPackages > 0) {
                stockText = existingPackages + ' ' + packagingLower + ' (' + existingQuantity.toLocaleString() + ' bottle(s))';
              } else {
                stockText = existingQuantity.toLocaleString() + ' bottle(s)';
              }
              existingStockText.textContent = stockText;
              existingStockInfo.style.display = 'block';
            } else {
              existingStockText.textContent = 'No existing stock';
              existingStockInfo.style.display = 'block';
            }
          } else {
            existingStockInfo.style.display = 'none';
          }
          
          // Update package unit text and label
          const packagingLower = variant.packaging.toLowerCase();
          const packagingCapitalized = variant.packaging.charAt(0).toUpperCase() + variant.packaging.slice(1).toLowerCase();
          packageUnitText.textContent = packagingLower;
          totalPackagesLabel.textContent = 'Total ' + packagingCapitalized;
          
          // Load registered prices if available
          if (variant.buying_price_per_unit) {
            buyingPriceInput.value = parseFloat(variant.buying_price_per_unit).toFixed(2);
          } else {
            buyingPriceInput.value = '';
          }
          
          if (variant.selling_price_per_unit) {
            sellingPriceInput.value = parseFloat(variant.selling_price_per_unit).toFixed(2);
          } else {
            sellingPriceInput.value = '';
          }
          
          // Handle Tot pricing visibility
          if (variant.can_sell_in_tots) {
            totPriceRow.style.display = 'flex';
            totPriceInput.required = true;
            if (variant.selling_price_per_tot) {
              totPriceInput.value = parseFloat(variant.selling_price_per_tot).toFixed(2);
            } else {
              totPriceInput.value = '';
            }
          } else {
            totPriceRow.style.display = 'none';
            totPriceInput.required = false;
            totPriceInput.value = '';
          }
          
          // Trigger calculation after loading prices
          calculateTotals();
        }
      } else {
        selectedVariant = null;
        variantInfo.textContent = '';
        quantityInfo.textContent = '';
        existingStockInfo.style.display = 'none';
        buyingPriceInput.value = '';
        sellingPriceInput.value = '';
        totPriceRow.style.display = 'none';
        totPriceInput.required = false;
        totPriceInput.value = '';
        totalPackagesLabel.textContent = 'Total Packages';
        packageUnitText.textContent = 'packages';
        clearCalculations();
      }
    }

    function calculateTotals() {
      if (!selectedVariant) {
        clearCalculations();
        return;
      }

      const quantity = parseInt(quantityInput.value) || 0;
      const buyingPrice = parseFloat(buyingPriceInput.value) || 0;
      const sellingPrice = parseFloat(sellingPriceInput.value) || 0;
      const discountType = discountTypeSelect.value;
      const discountAmount = parseFloat(discountAmountInput.value) || 0;

      const totalPackages = quantity;
      const totalUnits = quantity * selectedVariant.itemsPerPackage;
      const profitPerUnit = sellingPrice - buyingPrice;
      let totalBuyingCost = totalUnits * buyingPrice;
      
      // Calculate discount
      let discountValue = 0;
      let finalBuyingCost = totalBuyingCost;
      
      if (discountType && discountAmount > 0) {
        if (discountType === 'fixed') {
          discountValue = Math.min(discountAmount, totalBuyingCost); // Discount cannot exceed total cost
          finalBuyingCost = totalBuyingCost - discountValue;
        } else if (discountType === 'percent') {
          discountValue = (totalBuyingCost * discountAmount) / 100;
          finalBuyingCost = totalBuyingCost - discountValue;
        }
        
        // Show discount section
        discountSection.style.display = 'block';
        finalCostSection.style.display = 'block';
        discountValueInput.value = discountValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        finalBuyingCostInput.value = finalBuyingCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        discountLabel.textContent = discountType === 'fixed' ? 'Fixed Amount Discount' : discountAmount + '% Discount';
      } else {
        // Hide discount section
        discountSection.style.display = 'none';
        finalCostSection.style.display = 'none';
      }
      
      const totalProfit = totalUnits * profitPerUnit;

      totalPackagesInput.value = totalPackages > 0 ? totalPackages.toLocaleString() : '';
      totalUnitsInput.value = totalUnits > 0 ? totalUnits.toLocaleString() : '';
      profitPerUnitInput.value = profitPerUnit.toFixed(2);
      totalBuyingCostInput.value = totalBuyingCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      totalProfitInput.value = totalProfit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function clearCalculations() {
      totalPackagesInput.value = '';
      totalUnitsInput.value = '';
      profitPerUnitInput.value = '';
      totalBuyingCostInput.value = '';
      discountValueInput.value = '';
      finalBuyingCostInput.value = '';
      totalProfitInput.value = '';
      discountSection.style.display = 'none';
      finalCostSection.style.display = 'none';
      packageUnitText.textContent = 'packages';
    }
    
    // Handle discount type change
    const discountPrefix = document.getElementById('discountPrefix');
    const discountPrefixText = document.getElementById('discountPrefixText');
    const discountSuffix = document.getElementById('discountSuffix');
    const discountSuffixText = document.getElementById('discountSuffixText');
    
    discountTypeSelect.addEventListener('change', function() {
      if (this.value) {
        discountAmountInput.style.display = 'block';
        discountAmountInput.required = true;
        if (this.value === 'fixed') {
          discountHint.textContent = 'Fixed amount discount';
          discountAmountHint.textContent = 'Enter fixed amount (e.g., 50000)';
          discountAmountInput.placeholder = '0.00';
          discountAmountInput.step = '0.01';
          discountPrefix.style.display = 'flex';
          discountSuffix.style.display = 'none';
          discountPrefixText.textContent = 'TSh';
        } else {
          discountHint.textContent = 'Percentage discount';
          discountAmountHint.textContent = 'Enter percentage (e.g., 5 for 5%)';
          discountAmountInput.placeholder = '0';
          discountAmountInput.step = '1';
          discountPrefix.style.display = 'none';
          discountSuffix.style.display = 'flex';
          discountSuffixText.textContent = '%';
        }
        calculateTotals();
      } else {
        discountAmountInput.style.display = 'none';
        discountAmountInput.required = false;
        discountAmountInput.value = '';
        discountPrefix.style.display = 'none';
        discountSuffix.style.display = 'none';
        discountHint.textContent = 'Select discount type';
        discountAmountHint.textContent = 'Enter discount amount';
        calculateTotals();
      }
    });

    // Event listeners
    productSelect.addEventListener('change', updateVariants);
    variantSelect.addEventListener('change', updateVariantInfo);
    quantityInput.addEventListener('input', calculateTotals);
    buyingPriceInput.addEventListener('input', calculateTotals);
    sellingPriceInput.addEventListener('input', calculateTotals);
    discountAmountInput.addEventListener('input', calculateTotals);
    
    // Initialize discount field visibility
    if (discountTypeSelect.value) {
      discountAmountInput.style.display = 'block';
      discountAmountInput.required = true;
      if (discountTypeSelect.value === 'fixed') {
        discountHint.textContent = 'Fixed amount discount';
        discountAmountHint.textContent = 'Enter fixed amount (e.g., 50000)';
        discountAmountInput.step = '0.01';
        discountPrefix.style.display = 'flex';
        discountSuffix.style.display = 'none';
        discountPrefixText.textContent = 'TSh';
      } else {
        discountHint.textContent = 'Percentage discount';
        discountAmountHint.textContent = 'Enter percentage (e.g., 5 for 5%)';
        discountAmountInput.step = '1';
        discountPrefix.style.display = 'none';
        discountSuffix.style.display = 'flex';
        discountSuffixText.textContent = '%';
      }
    }

    // Initialize if product is pre-selected
    if (productSelect.value) {
      updateVariants();
      const oldVariantId = '{{ old("product_variant_id") }}';
      if (oldVariantId) {
        variantSelect.value = oldVariantId;
        updateVariantInfo();
      }
    }

    // Update barcode preview when quantity changes
    quantityInput.addEventListener('input', function() {
      updateBarcodePreview();
    });

    variantSelect.addEventListener('change', function() {
      updateBarcodePreview();
    });

    function updateBarcodePreview() {
      const quantity = parseInt(quantityInput.value) || 0;
      const previewContainer = document.getElementById('barcodePreviewContainer');
      const previewSection = document.getElementById('barcodePreviewSection');
      
      if (quantity > 0 && selectedVariant) {
        previewSection.style.display = 'block';
        previewContainer.innerHTML = '';
        
        // Generate preview barcodes (limited to 4 for preview)
        const previewCount = Math.min(quantity, 4);
        for (let i = 1; i <= previewCount; i++) {
          const barcodeValue = 'PREVIEW-' + i;
          const barcodeDiv = document.createElement('div');
          barcodeDiv.className = 'col-md-3 mb-3 text-center';
          barcodeDiv.style.cssText = 'border: 1px solid #dee2e6; padding: 15px; margin: 5px;';
          barcodeDiv.innerHTML = `
            <div class="mb-2">
              <strong>${productSelect.options[productSelect.selectedIndex]?.text || 'Product'}</strong><br>
              <small>${selectedVariant.measurement} - ${selectedVariant.packaging}</small><br>
              <small class="text-muted">#${i}/${quantity}</small>
            </div>
            <svg id="preview-barcode-${i}" class="barcode-svg"></svg>
            <div class="mt-2">
              <small class="text-muted">${barcodeValue}</small>
            </div>
          `;
          previewContainer.appendChild(barcodeDiv);
          
          // Generate barcode
          setTimeout(() => {
            try {
              if (typeof JsBarcode !== 'undefined') {
                JsBarcode('#preview-barcode-' + i, barcodeValue, {
                  format: "CODE128",
                  width: 2,
                  height: 50,
                  displayValue: true,
                  fontSize: 10,
                  margin: 5
                });
              }
            } catch (e) {
              console.error('Barcode preview error:', e);
            }
          }, 100);
        }
        
        if (quantity > 4) {
          const moreDiv = document.createElement('div');
          moreDiv.className = 'col-md-12 text-center mt-2';
          moreDiv.innerHTML = `<small class="text-muted">... and ${quantity - 4} more barcode(s) will be generated after saving</small>`;
          previewContainer.appendChild(moreDiv);
        }
      } else {
        previewSection.style.display = 'none';
      }
    }
  })();
</script>
<script>
  function printBarcodePreview() {
    const printContent = document.getElementById('barcodePreviewSection').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
  }
</script>
@endsection
