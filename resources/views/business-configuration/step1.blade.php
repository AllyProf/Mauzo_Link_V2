<form action="{{ route('business-configuration.step1') }}" method="POST">
  @csrf
  <h3 class="mb-4">Step 1: Business Information</h3>
  
  <div class="form-group">
    <label for="business_name">Business Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('business_name') is-invalid @enderror" 
           id="business_name" name="business_name" 
           value="{{ old('business_name', auth()->user()->business_name) }}" required>
    @error('business_name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="form-group">
    <label for="phone">Phone Number <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
           id="phone" name="phone" 
           value="{{ old('phone', auth()->user()->phone) }}" required>
    @error('phone')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="form-group">
    <label for="address">Address <span class="text-danger">*</span></label>
    <textarea class="form-control @error('address') is-invalid @enderror" 
              id="address" name="address" rows="2" required>{{ old('address', auth()->user()->address) }}</textarea>
    @error('address')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label for="city">City <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('city') is-invalid @enderror" 
               id="city" name="city" 
               value="{{ old('city', auth()->user()->city) }}" required>
        @error('city')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label for="country">Country <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('country') is-invalid @enderror" 
               id="country" name="country" 
               value="{{ old('country', auth()->user()->country ?? 'Tanzania') }}" required>
        @error('country')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>

  <div class="mt-4">
    <button type="submit" class="btn btn-primary">
      Next Step <i class="fa fa-arrow-right"></i>
    </button>
  </div>
</form>




