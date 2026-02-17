@extends('layouts.dashboard')

@section('title', 'Manage Recipe - ' . $foodItem->name)

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-book"></i> Manage Recipe</h1>
    <p>Recipe for: <strong>{{ $foodItem->name }}</strong></p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.food-items') }}">Food Items</a></li>
    <li class="breadcrumb-item">Manage Recipe</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Recipe for: {{ $foodItem->name }}</h3>
        <a href="{{ route('bar.chef.food-items') }}" class="btn btn-secondary">
          <i class="fa fa-arrow-left"></i> Back to Food Items
        </a>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <ul class="mb-0">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        <form method="POST" action="{{ route('bar.chef.food-items.recipe.save', $foodItem) }}">
          @csrf

          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>Why add a recipe?</strong> When a chef marks this food item as "preparing", ingredients will be automatically deducted from inventory based on this recipe.
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Recipe Name</label>
                <input class="form-control" 
                       type="text" 
                       name="recipe[name]" 
                       value="{{ old('recipe.name', optional($foodItem->recipe)->name ?? $foodItem->name) }}" 
                       placeholder="e.g., Grilled Chicken Recipe">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="control-label">Prep Time (minutes)</label>
                <input class="form-control" 
                       type="number" 
                       name="recipe[prep_time_minutes]" 
                       value="{{ old('recipe.prep_time_minutes', optional($foodItem->recipe)->prep_time_minutes ?? '') }}" 
                       min="0"
                       placeholder="15">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="control-label">Cook Time (minutes)</label>
                <input class="form-control" 
                       type="number" 
                       name="recipe[cook_time_minutes]" 
                       value="{{ old('recipe.cook_time_minutes', optional($foodItem->recipe)->cook_time_minutes ?? '') }}" 
                       min="0"
                       placeholder="20">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="control-label">Recipe Instructions</label>
            <textarea class="form-control" 
                      name="recipe[instructions]" 
                      rows="4" 
                      placeholder="Step-by-step cooking instructions (optional)">{{ old('recipe.instructions', optional($foodItem->recipe)->instructions ?? '') }}</textarea>
          </div>

          <hr class="my-4">

          <div class="form-group">
            <label class="control-label"><strong>Recipe Ingredients</strong></label>
            <p class="text-muted">Add ingredients and quantities needed for this recipe. Example: 500g Chicken, 50g Salt, 20ml Oil</p>
            
            <div id="recipeIngredientsContainer">
              @if(optional($foodItem->recipe)->recipeIngredients && $foodItem->recipe->recipeIngredients->count() > 0)
                @foreach($foodItem->recipe->recipeIngredients as $index => $recipeIngredient)
                  <div class="recipe-ingredient-row mb-2 p-3 border rounded" data-index="{{ $index }}">
                    <div class="row align-items-center">
                      <div class="col-md-5">
                        <label class="control-label">Ingredient *</label>
                        <select class="form-control recipe-ingredient-select" 
                                name="recipe[ingredients][{{ $index }}][ingredient_id]" 
                                data-unit="{{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') }}"
                                required>
                          <option value="">Select Ingredient</option>
                          @foreach($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" 
                                    data-ingredient-unit="{{ $ingredient->unit }}"
                              {{ old("recipe.ingredients.{$index}.ingredient_id", $recipeIngredient->ingredient_id) == $ingredient->id ? 'selected' : '' }}>
                              {{ $ingredient->name }} ({{ $ingredient->unit }})
                            </option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="control-label">Quantity *</label>
                        <input class="form-control" 
                               type="number" 
                               name="recipe[ingredients][{{ $index }}][quantity_required]" 
                               value="{{ old("recipe.ingredients.{$index}.quantity_required", $recipeIngredient->quantity_required) }}" 
                               step="0.01"
                               min="0"
                               placeholder="500"
                               required>
                      </div>
                      <div class="col-md-3">
                        <label class="control-label">Unit *</label>
                        <select class="form-control recipe-unit-select" 
                                name="recipe[ingredients][{{ $index }}][unit]" 
                                required>
                          <option value="g" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'g' ? 'selected' : '' }}>g (Gram)</option>
                          <option value="kg" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'kg' ? 'selected' : '' }}>kg (Kilogram)</option>
                          <option value="ml" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'ml' ? 'selected' : '' }}>ml (Milliliter)</option>
                          <option value="liter" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'liter' ? 'selected' : '' }}>liter (Liter)</option>
                          <option value="piece" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'piece' ? 'selected' : '' }}>piece (Piece)</option>
                          <option value="box" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'box' ? 'selected' : '' }}>box (Box)</option>
                          <option value="bottle" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'bottle' ? 'selected' : '' }}>bottle (Bottle)</option>
                          <option value="bag" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'bag' ? 'selected' : '' }}>bag (Bag)</option>
                          <option value="can" {{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') == 'can' ? 'selected' : '' }}>can (Can)</option>
                        </select>
                      </div>
                      <div class="col-md-1">
                        <label class="control-label">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-danger btn-block remove-recipe-ingredient">
                          <i class="fa fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="recipe-ingredient-row mb-2 p-3 border rounded" data-index="0">
                  <div class="row align-items-center">
                    <div class="col-md-5">
                      <label class="control-label">Ingredient *</label>
                      <select class="form-control recipe-ingredient-select" 
                              name="recipe[ingredients][0][ingredient_id]"
                              data-unit="g">
                        <option value="">Select Ingredient</option>
                        @foreach($ingredients as $ingredient)
                          <option value="{{ $ingredient->id }}" 
                                  data-ingredient-unit="{{ $ingredient->unit }}">
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                          </option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="control-label">Quantity *</label>
                      <input class="form-control" 
                             type="number" 
                             name="recipe[ingredients][0][quantity_required]" 
                             step="0.01"
                             min="0"
                             placeholder="500">
                    </div>
                    <div class="col-md-3">
                      <label class="control-label">Unit *</label>
                      <select class="form-control recipe-unit-select" 
                              name="recipe[ingredients][0][unit]" 
                              required>
                        <option value="g" selected>g (Gram)</option>
                        <option value="kg">kg (Kilogram)</option>
                        <option value="ml">ml (Milliliter)</option>
                        <option value="liter">liter (Liter)</option>
                        <option value="piece">piece (Piece)</option>
                        <option value="box">box (Box)</option>
                        <option value="bottle">bottle (Bottle)</option>
                        <option value="bag">bag (Bag)</option>
                        <option value="can">can (Can)</option>
                      </select>
                    </div>
                    <div class="col-md-1">
                      <label class="control-label">&nbsp;</label>
                      <button type="button" class="btn btn-sm btn-danger btn-block remove-recipe-ingredient" style="display: none;">
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>
              @endif
            </div>
            
            <button type="button" class="btn btn-info mt-2" id="addRecipeIngredient">
              <i class="fa fa-plus"></i> Add Ingredient
            </button>
          </div>

          <div class="tile-footer mt-4">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i> Save Recipe
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
$(document).ready(function() {
    let recipeIngredientIndex = {{ (optional($foodItem->recipe)->recipeIngredients && $foodItem->recipe->recipeIngredients->count() > 0) ? $foodItem->recipe->recipeIngredients->count() : 1 }};

    // Add recipe ingredient
    $('#addRecipeIngredient').on('click', function() {
        const template = $('.recipe-ingredient-row').first();
        const newRow = template.clone(true, true);
        
        newRow.attr('data-index', recipeIngredientIndex);
        newRow.find('.remove-recipe-ingredient').show();
        
        // Update all input names and reset values
        newRow.find('input, select').each(function() {
            const $this = $(this);
            let name = $this.attr('name');
            if (name) {
                name = name.replace(/recipe\[ingredients\]\[\d+\]/, 'recipe[ingredients][' + recipeIngredientIndex + ']');
                $this.attr('name', name);
                
                // Reset values based on input type
                if ($this.is('input[type="number"]')) {
                    $this.val('');
                } else if ($this.is('input[type="text"]')) {
                    $this.val('');
                } else if ($this.is('select.recipe-ingredient-select')) {
                    $this.val('').attr('data-unit', 'g');
                } else if ($this.is('select.recipe-unit-select')) {
                    $this.val('g'); // Default to 'g'
                } else if ($this.is('select')) {
                    $this.val('');
                }
            }
        });
        
        // Clear any validation errors
        newRow.find('.is-invalid').removeClass('is-invalid');
        newRow.find('.invalid-feedback').remove();

        $('#recipeIngredientsContainer').append(newRow);
        recipeIngredientIndex++;
    });

    // Remove recipe ingredient
    $(document).on('click', '.remove-recipe-ingredient', function() {
        $(this).closest('.recipe-ingredient-row').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Auto-select unit based on selected ingredient
    $(document).on('change', '.recipe-ingredient-select', function() {
        const $row = $(this).closest('.recipe-ingredient-row');
        const selectedOption = $(this).find('option:selected');
        const ingredientUnit = selectedOption.data('ingredient-unit');
        const $unitSelect = $row.find('.recipe-unit-select');
        
        if (ingredientUnit) {
            // Try to find exact match first
            let found = false;
            $unitSelect.find('option').each(function() {
                const optionValue = $(this).val();
                if (optionValue === ingredientUnit || optionValue.toLowerCase() === ingredientUnit.toLowerCase()) {
                    $unitSelect.val(optionValue);
                    found = true;
                    return false;
                }
            });
            
            // If not found, try common variations
            if (!found) {
                const unitMap = {
                    'kg': 'kg', 'kilogram': 'kg', 'kilograms': 'kg',
                    'g': 'g', 'gram': 'g', 'grams': 'g',
                    'liter': 'liter', 'litre': 'liter', 'liters': 'liter', 'litres': 'liter', 'l': 'liter',
                    'ml': 'ml', 'milliliter': 'ml', 'millilitre': 'ml', 'milliliters': 'ml', 'millilitres': 'ml',
                    'piece': 'piece', 'pieces': 'piece', 'pcs': 'piece',
                    'pack': 'pack', 'packs': 'pack', 'package': 'pack', 'packages': 'pack',
                    'box': 'box', 'boxes': 'box',
                    'bottle': 'bottle', 'bottles': 'bottle',
                    'bag': 'bag', 'bags': 'bag',
                    'can': 'can', 'cans': 'can'
                };
                
                const normalizedUnit = ingredientUnit.toLowerCase().trim();
                if (unitMap[normalizedUnit]) {
                    $unitSelect.val(unitMap[normalizedUnit]);
                }
            }
        }
    });
});
</script>
@endpush

