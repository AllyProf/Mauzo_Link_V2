<form action="{{ route('business-configuration.step2') }}" method="POST">
  @csrf
  <h3 class="mb-4">Step 2: Select Business Type(s)</h3>
  <p class="text-muted mb-4">Select one or more business types that match your business. The system will generate menus based on your selection.</p>
  
  <div class="row">
    @foreach($businessTypes as $businessType)
    <div class="col-md-4 mb-3">
      <div class="card h-100">
        <div class="card-body text-center">
          <input type="checkbox" 
                 class="business-type-checkbox" 
                 name="business_types[]" 
                 value="{{ $businessType->id }}" 
                 id="business_type_{{ $businessType->id }}"
                 {{ in_array($businessType->id, $selectedTypes) ? 'checked' : '' }}>
          <label for="business_type_{{ $businessType->id }}" class="card-link" style="cursor: pointer; width: 100%;">
            <i class="fa {{ $businessType->icon }} fa-3x mb-2" style="color: #940000;"></i>
            <h5 class="card-title">{{ $businessType->name }}</h5>
            <p class="card-text text-muted small">{{ $businessType->description }}</p>
          </label>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  <div class="alert alert-info mt-4">
    <i class="fa fa-info-circle"></i> <strong>Note:</strong> The first business type you select will be set as your primary business type.
  </div>

  <div class="mt-4">
    <a href="{{ route('business-configuration.step1') }}" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Previous
    </a>
    <button type="submit" class="btn btn-primary">
      Next Step <i class="fa fa-arrow-right"></i>
    </button>
  </div>
</form>


