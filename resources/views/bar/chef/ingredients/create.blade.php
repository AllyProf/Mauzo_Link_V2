@extends('layouts.dashboard')

@section('title', 'Add Ingredients')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-flask"></i> Add Ingredients</h1>
    <p>Add new ingredients to your inventory</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.ingredients') }}">Ingredients</a></li>
    <li class="breadcrumb-item">Add Ingredients</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Ingredient Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.chef.ingredients.store') }}" id="ingredientsForm">
          @csrf

          <div id="ingredientsContainer">
            <!-- First ingredient form -->
            <div class="ingredient-form mb-4 p-3 border rounded" data-index="0">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Ingredient #<span class="ingredient-number">1</span></h5>
                <button type="button" class="btn btn-sm btn-danger remove-ingredient" style="display: none;">
                  <i class="fa fa-times"></i> Remove
                </button>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Ingredient Name *</label>
                    <input class="form-control" 
                           type="text" 
                           name="ingredients[0][name]" 
                           placeholder="e.g., Chicken, Flour, Oil" 
                           required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Unit of Measurement *</label>
                    <select class="form-control" name="ingredients[0][unit]" required>
                      <option value="">Select Unit</option>
                      <option value="kg">Kilogram (kg)</option>
                      <option value="g">Gram (g)</option>
                      <option value="liter">Liter</option>
                      <option value="ml">Milliliter (ml)</option>
                      <option value="piece">Piece</option>
                      <option value="bunch">Bunch</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Minimum Stock Level *</label>
                    <input class="form-control" 
                           type="number" 
                           name="ingredients[0][min_stock_level]" 
                           value="0" 
                           step="0.01"
                           min="0"
                           required>
                    <small class="form-text text-muted">Alert when stock falls below this level</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Status</label>
                    <div class="form-check mt-2">
                      <input class="form-check-input" 
                             type="checkbox" 
                             name="ingredients[0][is_active]" 
                             value="1" 
                             checked>
                      <label class="form-check-label">
                        Active
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <button type="button" class="btn btn-info" id="addMoreIngredient">
              <i class="fa fa-plus"></i> Add More Ingredient
            </button>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i> Create Ingredients
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

@push('scripts')
<script>
$(document).ready(function() {
    // Get the highest index from existing forms
    let ingredientIndex = $('.ingredient-form').length;

    // Add more ingredient form
    $('#addMoreIngredient').on('click', function() {
        const template = $('.ingredient-form').first();
        const newForm = template.clone(true, true); // Deep clone with data and events
        
        // Update data-index and ingredient number
        newForm.attr('data-index', ingredientIndex);
        newForm.find('.ingredient-number').text(ingredientIndex + 1);
        newForm.find('.remove-ingredient').show();
        
        // Update all input names with new index - replace ALL occurrences
        newForm.find('input, select').each(function() {
            const $this = $(this);
            let name = $this.attr('name');
            
            if (name) {
                // Replace all occurrences of [0] or any number with the new index
                name = name.replace(/ingredients\[\d+\]/, 'ingredients[' + ingredientIndex + ']');
                $this.attr('name', name);
                
                // Clear values based on input type
                if ($this.is('input[type="text"]')) {
                    $this.val('');
                } else if ($this.is('input[type="number"]')) {
                    $this.val('0');
                } else if ($this.is('select')) {
                    $this.val('').prop('selectedIndex', 0);
                } else if ($this.is('input[type="checkbox"]')) {
                    $this.prop('checked', true);
                }
                
                // Remove any existing IDs and update if needed
                const id = $this.attr('id');
                if (id) {
                    $this.attr('id', id.replace(/\d+/, ingredientIndex));
                }
            }
        });

        // Remove validation classes and error messages
        newForm.find('.is-invalid').removeClass('is-invalid');
        newForm.find('.invalid-feedback').remove();
        newForm.find('.has-error').removeClass('has-error');
        newForm.find('.alert').remove();

        $('#ingredientsContainer').append(newForm);
        ingredientIndex++;
    });

    // Remove ingredient form
    $(document).on('click', '.remove-ingredient', function() {
        const formToRemove = $(this).closest('.ingredient-form');
        formToRemove.fadeOut(300, function() {
            $(this).remove();
            reindexForms();
            updateIngredientNumbers();
        });
    });

    // Re-index all forms after removal
    function reindexForms() {
        $('.ingredient-form').each(function(newIndex) {
            const $form = $(this);
            $form.attr('data-index', newIndex);
            
            // Update all input names to use the new index
            $form.find('input, select').each(function() {
                const $this = $(this);
                let name = $this.attr('name');
                if (name && name.includes('ingredients[')) {
                    name = name.replace(/ingredients\[\d+\]/, 'ingredients[' + newIndex + ']');
                    $this.attr('name', name);
                }
            });
        });
        
        // Update ingredientIndex to be the next available number
        ingredientIndex = $('.ingredient-form').length;
    }

    // Update ingredient numbers after removal
    function updateIngredientNumbers() {
        $('.ingredient-form').each(function(index) {
            $(this).find('.ingredient-number').text(index + 1);
        });
    }
});
</script>
@endpush
@endsection





