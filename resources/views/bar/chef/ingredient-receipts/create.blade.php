@extends('layouts.dashboard')

@section('title', 'Create Ingredient Receipt')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-shopping-cart"></i> Create Ingredient Receipt</h1>
    <p>Record new ingredient stock receipt</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.ingredient-receipts') }}">Ingredient Receipts</a></li>
    <li class="breadcrumb-item">Create</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">New Ingredient Receipt</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.chef.ingredient-receipts.store') }}">
          @csrf

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Ingredient <span class="text-danger">*</span></label>
                <select name="ingredient_id" id="ingredient_id" class="form-control @error('ingredient_id') is-invalid @enderror" required>
                  <option value="">Select Ingredient</option>
                  @foreach($ingredients as $ingredient)
                    <option value="{{ $ingredient->id }}" 
                            data-unit="{{ $ingredient->unit }}" 
                            {{ old('ingredient_id') == $ingredient->id ? 'selected' : '' }}>
                      {{ $ingredient->name }} ({{ $ingredient->unit }})
                    </option>
                  @endforeach
                </select>
                @error('ingredient_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label>Supplier</label>
                <select name="supplier_id" class="form-control @error('supplier_id') is-invalid @enderror">
                  <option value="">Select Supplier (Optional)</option>
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
          </div>

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Quantity Received <span class="text-danger">*</span></label>
                <input type="number" name="quantity_received" id="quantity_received" class="form-control @error('quantity_received') is-invalid @enderror" 
                       value="{{ old('quantity_received') }}" step="0.01" min="0.01" required>
                @error('quantity_received')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label>Unit <span class="text-danger">*</span></label>
                <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" required>
                  <option value="">Select Unit</option>
                  <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>kg (Kilogram)</option>
                  <option value="g" {{ old('unit') == 'g' ? 'selected' : '' }}>g (Gram)</option>
                  <option value="liter" {{ old('unit') == 'liter' ? 'selected' : '' }}>liter (Liter)</option>
                  <option value="ml" {{ old('unit') == 'ml' ? 'selected' : '' }}>ml (Milliliter)</option>
                  <option value="piece" {{ old('unit') == 'piece' ? 'selected' : '' }}>piece (Piece)</option>
                  <option value="box" {{ old('unit') == 'box' ? 'selected' : '' }}>box (Box)</option>
                  <option value="bottle" {{ old('unit') == 'bottle' ? 'selected' : '' }}>bottle (Bottle)</option>
                  <option value="bag" {{ old('unit') == 'bag' ? 'selected' : '' }}>bag (Bag)</option>
                  <option value="can" {{ old('unit') == 'can' ? 'selected' : '' }}>can (Can)</option>
                </select>
                @error('unit')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label>Cost Per Unit (TSh) <span class="text-danger">*</span></label>
                <input type="number" name="cost_per_unit" id="cost_per_unit" class="form-control @error('cost_per_unit') is-invalid @enderror" 
                       value="{{ old('cost_per_unit') }}" step="0.01" min="0" required>
                @error('cost_per_unit')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label>Total Cost (TSh)</label>
                <input type="number" name="total_cost" id="total_cost" class="form-control @error('total_cost') is-invalid @enderror" 
                       value="{{ old('total_cost') }}" step="0.01" min="0" readonly>
                @error('total_cost')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted"><i class="fa fa-calculator"></i> Auto-calculated</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Received Date <span class="text-danger">*</span></label>
                <input type="date" name="received_date" class="form-control @error('received_date') is-invalid @enderror" 
                       value="{{ old('received_date', date('Y-m-d')) }}" required>
                @error('received_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" 
                       value="{{ old('expiry_date') }}">
                @error('expiry_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Batch Number</label>
                <input type="text" name="batch_number" class="form-control @error('batch_number') is-invalid @enderror" 
                       value="{{ old('batch_number') }}" placeholder="Optional">
                @error('batch_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Storage Location</label>
                <select name="location" class="form-control @error('location') is-invalid @enderror">
                  <option value="">Select Location</option>
                  <option value="Kitchen" {{ old('location', 'Kitchen') == 'Kitchen' ? 'selected' : '' }}>Kitchen</option>
                  <option value="Freezer" {{ old('location') == 'Freezer' ? 'selected' : '' }}>Freezer</option>
                  <option value="Refrigerator" {{ old('location') == 'Refrigerator' ? 'selected' : '' }}>Refrigerator</option>
                  <option value="Pantry" {{ old('location') == 'Pantry' ? 'selected' : '' }}>Pantry</option>
                  <option value="Storage Room" {{ old('location') == 'Storage Room' ? 'selected' : '' }}>Storage Room</option>
                  <option value="Dry Storage" {{ old('location') == 'Dry Storage' ? 'selected' : '' }}>Dry Storage</option>
                  <option value="Cold Storage" {{ old('location') == 'Cold Storage' ? 'selected' : '' }}>Cold Storage</option>
                  <option value="Warehouse" {{ old('location') == 'Warehouse' ? 'selected' : '' }}>Warehouse</option>
                </select>
                @error('location')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" 
                      placeholder="Additional notes about this receipt">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="tile-footer">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Create Receipt
            </button>
            <a href="{{ route('bar.chef.ingredient-receipts') }}" class="btn btn-secondary">
              <i class="fa fa-times"></i> Cancel
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
$(document).ready(function() {
    // Auto-calculate total cost when quantity or cost per unit changes
    function calculateTotalCost() {
        var quantity = parseFloat($('#quantity_received').val()) || 0;
        var costPerUnit = parseFloat($('#cost_per_unit').val()) || 0;
        var totalCost = quantity * costPerUnit;
        
        $('#total_cost').val(totalCost.toFixed(2));
    }

    $('#quantity_received, #cost_per_unit').on('input change', function() {
        calculateTotalCost();
    });

    // Auto-select unit based on selected ingredient
    $('#ingredient_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var unit = selectedOption.data('unit');
        
        if (unit) {
            // Try to find exact match first
            var unitSelect = $('#unit');
            var found = false;
            
            unitSelect.find('option').each(function() {
                var optionValue = $(this).val();
                // Check for exact match or case-insensitive match
                if (optionValue === unit || optionValue.toLowerCase() === unit.toLowerCase()) {
                    unitSelect.val(optionValue);
                    found = true;
                    return false; // Break the loop
                }
            });
            
            // If not found, try to match common variations
            if (!found) {
                var unitMap = {
                    'kg': 'kg',
                    'kilogram': 'kg',
                    'kilograms': 'kg',
                    'g': 'g',
                    'gram': 'g',
                    'grams': 'g',
                    'liter': 'liter',
                    'litre': 'liter',
                    'liters': 'liter',
                    'litres': 'liter',
                    'l': 'liter',
                    'ml': 'ml',
                    'milliliter': 'ml',
                    'millilitre': 'ml',
                    'milliliters': 'ml',
                    'millilitres': 'ml',
                    'piece': 'piece',
                    'pieces': 'piece',
                    'pcs': 'piece',
                    'pack': 'pack',
                    'packs': 'pack',
                    'package': 'pack',
                    'packages': 'pack',
                    'box': 'box',
                    'boxes': 'box',
                    'bottle': 'bottle',
                    'bottles': 'bottle',
                    'bag': 'bag',
                    'bags': 'bag',
                    'can': 'can',
                    'cans': 'can'
                };
                
                var normalizedUnit = unit.toLowerCase().trim();
                if (unitMap[normalizedUnit]) {
                    unitSelect.val(unitMap[normalizedUnit]);
                }
            }
        } else {
            // Clear unit selection if no ingredient selected
            $('#unit').val('');
        }
    });
    
    // Auto-select unit on page load if ingredient is already selected
    if ($('#ingredient_id').val()) {
        $('#ingredient_id').trigger('change');
    }

    // Calculate on page load if values exist
    calculateTotalCost();
});
</script>
@endpush

