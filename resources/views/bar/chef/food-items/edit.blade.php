@extends('layouts.dashboard')

@section('title', 'Edit Food Item')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cutlery"></i> Edit Food Item</h1>
    <p>Update food item information</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.food-items') }}">Food Items</a></li>
    <li class="breadcrumb-item">Edit Food Item</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Food Item Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.chef.food-items.update', $foodItem) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Food Name *</label>
                <input class="form-control @error('name') is-invalid @enderror" 
                       type="text" 
                       name="name" 
                       value="{{ old('name', $foodItem->name) }}" 
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
                       value="{{ old('variant_name', $foodItem->variant_name) }}" 
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
                       value="{{ old('price', $foodItem->price) }}" 
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
                       value="{{ old('prep_time_minutes', $foodItem->prep_time_minutes) }}" 
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
                @if($foodItem->image)
                  <div class="mb-2">
                    <img src="{{ asset('storage/' . $foodItem->image) }}" alt="{{ $foodItem->name }}" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                    <p class="text-muted mt-1"><small>Current image</small></p>
                  </div>
                @endif
                <input class="form-control @error('image') is-invalid @enderror" 
                       type="file" 
                       name="image" 
                       accept="image/*"
                       id="foodImage">
                <small class="form-text text-muted">Upload new image to replace current (JPG, PNG, GIF - Max 2MB)</small>
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
                       value="{{ old('sort_order', $foodItem->sort_order) }}" 
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
                      placeholder="Food description (optional)">{{ old('description', $foodItem->description) }}</textarea>
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
                     {{ old('is_available', $foodItem->is_available) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_available">
                Available for ordering
              </label>
            </div>
          </div>

          <hr class="my-4">

          <!-- Recipe Section -->
          <div class="recipe-section">
            <h4 class="mb-3"><i class="fa fa-book"></i> Recipe (Optional)</h4>
            <p class="text-muted mb-3">Link a recipe to this food item to track ingredient usage automatically.</p>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">Recipe Name</label>
                  <input class="form-control" 
                         type="text" 
                         name="recipe[name]" 
                         value="{{ old('recipe.name', $foodItem->recipe->name ?? $foodItem->name) }}" 
                         placeholder="e.g., Grilled Chicken Recipe">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label class="control-label">Prep Time (minutes)</label>
                  <input class="form-control" 
                         type="number" 
                         name="recipe[prep_time_minutes]" 
                         value="{{ old('recipe.prep_time_minutes', $foodItem->recipe->prep_time_minutes ?? '') }}" 
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
                         value="{{ old('recipe.cook_time_minutes', $foodItem->recipe->cook_time_minutes ?? '') }}" 
                         min="0"
                         placeholder="20">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="control-label">Recipe Instructions</label>
              <textarea class="form-control" 
                        name="recipe[instructions]" 
                        rows="3" 
                        placeholder="Step-by-step cooking instructions (optional)">{{ old('recipe.instructions', optional($foodItem->recipe)->instructions ?? '') }}</textarea>
            </div>

            <div class="form-group">
              <label class="control-label">Recipe Ingredients</label>
              <div id="recipeIngredientsContainer">
                @if(optional($foodItem->recipe)->recipeIngredients && $foodItem->recipe->recipeIngredients->count() > 0)
                  @foreach($foodItem->recipe->recipeIngredients as $index => $recipeIngredient)
                    <div class="recipe-ingredient-row mb-2 p-2 border rounded" data-index="{{ $index }}">
                      <div class="row">
                        <div class="col-md-4">
                          <select class="form-control" name="recipe[ingredients][{{ $index }}][ingredient_id]" required>
                            <option value="">Select Ingredient</option>
                            @foreach($ingredients as $ingredient)
                              <option value="{{ $ingredient->id }}" 
                                {{ old("recipe.ingredients.{$index}.ingredient_id", $recipeIngredient->ingredient_id) == $ingredient->id ? 'selected' : '' }}>
                                {{ $ingredient->name }} ({{ $ingredient->unit }})
                              </option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-3">
                          <input class="form-control" 
                                 type="number" 
                                 name="recipe[ingredients][{{ $index }}][quantity_required]" 
                                 value="{{ old("recipe.ingredients.{$index}.quantity_required", $recipeIngredient->quantity_required) }}" 
                                 step="0.01"
                                 min="0"
                                 placeholder="Quantity"
                                 required>
                        </div>
                        <div class="col-md-3">
                          <input class="form-control" 
                                 type="text" 
                                 name="recipe[ingredients][{{ $index }}][unit]" 
                                 value="{{ old("recipe.ingredients.{$index}.unit", $recipeIngredient->unit ?? 'g') }}" 
                                 placeholder="Unit (g, kg, ml, etc.)"
                                 required>
                        </div>
                        <div class="col-md-2">
                          <button type="button" class="btn btn-sm btn-danger remove-recipe-ingredient">
                            <i class="fa fa-times"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  @endforeach
                @else
                  <div class="recipe-ingredient-row mb-2 p-2 border rounded" data-index="0">
                    <div class="row">
                      <div class="col-md-4">
                        <select class="form-control" name="recipe[ingredients][0][ingredient_id]">
                          <option value="">Select Ingredient</option>
                          @foreach($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-3">
                        <input class="form-control" 
                               type="number" 
                               name="recipe[ingredients][0][quantity_required]" 
                               step="0.01"
                               min="0"
                               placeholder="Quantity">
                      </div>
                      <div class="col-md-3">
                        <input class="form-control" 
                               type="text" 
                               name="recipe[ingredients][0][unit]" 
                               value="g"
                               placeholder="Unit (g, kg, ml, etc.)">
                      </div>
                      <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-danger remove-recipe-ingredient" style="display: none;">
                          <i class="fa fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                @endif
              </div>
              <button type="button" class="btn btn-info btn-sm mt-2" id="addRecipeIngredient">
                <i class="fa fa-plus"></i> Add Ingredient
              </button>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i> Update Food Item
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

  // Recipe ingredients management
  $(document).ready(function() {
    let recipeIngredientIndex = {{ ($foodItem->recipe && $foodItem->recipe->recipeIngredients && $foodItem->recipe->recipeIngredients->count() > 0) ? $foodItem->recipe->recipeIngredients->count() : 1 }};

    // Add recipe ingredient
    $('#addRecipeIngredient').on('click', function() {
      const template = $('.recipe-ingredient-row').first();
      const newRow = template.clone(true, true);
      
      newRow.attr('data-index', recipeIngredientIndex);
      newRow.find('.remove-recipe-ingredient').show();
      
      // Update all input names
      newRow.find('input, select').each(function() {
        const $this = $(this);
        let name = $this.attr('name');
        if (name) {
          name = name.replace(/recipe\[ingredients\]\[\d+\]/, 'recipe[ingredients][' + recipeIngredientIndex + ']');
          $this.attr('name', name);
          if ($this.is('input[type="number"], input[type="text"]')) {
            $this.val('');
          } else if ($this.is('select')) {
            $this.val('');
          }
        }
      });

      $('#recipeIngredientsContainer').append(newRow);
      recipeIngredientIndex++;
    });

    // Remove recipe ingredient
    $(document).on('click', '.remove-recipe-ingredient', function() {
      $(this).closest('.recipe-ingredient-row').remove();
    });
  });
</script>
@endpush





