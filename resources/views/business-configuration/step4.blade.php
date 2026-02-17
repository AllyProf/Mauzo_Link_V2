<form action="{{ route('business-configuration.step4') }}" method="POST">
  @csrf
  <h3 class="mb-4">Step 4: System Settings</h3>
  
  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label for="currency">Currency <span class="text-danger">*</span></label>
        <select class="form-control" id="currency" name="currency" required>
          <option value="TSh" {{ $settings['currency'] === 'TSh' ? 'selected' : '' }}>TSh (Tanzanian Shilling)</option>
          <option value="USD" {{ $settings['currency'] === 'USD' ? 'selected' : '' }}>USD (US Dollar)</option>
          <option value="EUR" {{ $settings['currency'] === 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label for="timezone">Timezone <span class="text-danger">*</span></label>
        <select class="form-control" id="timezone" name="timezone" required>
          <option value="Africa/Dar_es_Salaam" {{ $settings['timezone'] === 'Africa/Dar_es_Salaam' ? 'selected' : '' }}>Africa/Dar es Salaam (EAT)</option>
          <option value="UTC" {{ $settings['timezone'] === 'UTC' ? 'selected' : '' }}>UTC</option>
        </select>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="tax_enabled" name="tax_enabled" value="1"
                 {{ $settings['tax_enabled'] ? 'checked' : '' }}>
          <label class="form-check-label" for="tax_enabled">
            Enable Tax
          </label>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label for="tax_rate">Tax Rate (%)</label>
        <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
               value="{{ $settings['tax_rate'] ?? 0 }}" min="0" max="100" step="0.01">
      </div>
    </div>
  </div>

  <div class="mt-4">
    <a href="{{ route('business-configuration.step3') }}" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Previous
    </a>
    <button type="submit" class="btn btn-primary">
      Next Step <i class="fa fa-arrow-right"></i>
    </button>
  </div>
</form>




