@extends('layouts.dashboard')

@section('title', 'Edit Supplier')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-truck"></i> Edit Supplier</h1>
    <p>Update supplier information</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.suppliers.index') }}">Suppliers</a></li>
    <li class="breadcrumb-item">Edit Supplier</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Supplier Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.suppliers.update', $supplier) }}">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Company Name *</label>
                <input class="form-control @error('company_name') is-invalid @enderror" 
                       type="text" 
                       name="company_name" 
                       value="{{ old('company_name', $supplier->company_name) }}" 
                       placeholder="Enter company name" 
                       required>
                @error('company_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Contact Person</label>
                <input class="form-control @error('contact_person') is-invalid @enderror" 
                       type="text" 
                       name="contact_person" 
                       value="{{ old('contact_person', $supplier->contact_person) }}" 
                       placeholder="Enter contact person name">
                @error('contact_person')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Phone Number *</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">+255</span>
                  </div>
                  <input class="form-control @error('phone') is-invalid @enderror" 
                         type="text" 
                         name="phone" 
                         value="{{ old('phone', $supplier->phone) }}" 
                         placeholder="e.g., 710490428" 
                         required>
                </div>
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Format: +255710490428</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Email Address</label>
                <input class="form-control @error('email') is-invalid @enderror" 
                       type="email" 
                       name="email" 
                       value="{{ old('email', $supplier->email) }}" 
                       placeholder="Enter email address">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">City</label>
                <select class="form-control @error('city') is-invalid @enderror" name="city">
                  <option value="">Select City</option>
                  <option value="Dar es Salaam" {{ old('city', $supplier->city) == 'Dar es Salaam' ? 'selected' : '' }}>Dar es Salaam</option>
                  <option value="Arusha" {{ old('city', $supplier->city) == 'Arusha' ? 'selected' : '' }}>Arusha</option>
                  <option value="Mwanza" {{ old('city', $supplier->city) == 'Mwanza' ? 'selected' : '' }}>Mwanza</option>
                  <option value="Dodoma" {{ old('city', $supplier->city) == 'Dodoma' ? 'selected' : '' }}>Dodoma</option>
                  <option value="Mbeya" {{ old('city', $supplier->city) == 'Mbeya' ? 'selected' : '' }}>Mbeya</option>
                  <option value="Morogoro" {{ old('city', $supplier->city) == 'Morogoro' ? 'selected' : '' }}>Morogoro</option>
                  <option value="Tanga" {{ old('city', $supplier->city) == 'Tanga' ? 'selected' : '' }}>Tanga</option>
                  <option value="Zanzibar" {{ old('city', $supplier->city) == 'Zanzibar' ? 'selected' : '' }}>Zanzibar</option>
                  <option value="Kigoma" {{ old('city', $supplier->city) == 'Kigoma' ? 'selected' : '' }}>Kigoma</option>
                  <option value="Mtwara" {{ old('city', $supplier->city) == 'Mtwara' ? 'selected' : '' }}>Mtwara</option>
                  <option value="Tabora" {{ old('city', $supplier->city) == 'Tabora' ? 'selected' : '' }}>Tabora</option>
                  <option value="Iringa" {{ old('city', $supplier->city) == 'Iringa' ? 'selected' : '' }}>Iringa</option>
                  <option value="Sumbawanga" {{ old('city', $supplier->city) == 'Sumbawanga' ? 'selected' : '' }}>Sumbawanga</option>
                  <option value="Musoma" {{ old('city', $supplier->city) == 'Musoma' ? 'selected' : '' }}>Musoma</option>
                  <option value="Bukoba" {{ old('city', $supplier->city) == 'Bukoba' ? 'selected' : '' }}>Bukoba</option>
                  <option value="Singida" {{ old('city', $supplier->city) == 'Singida' ? 'selected' : '' }}>Singida</option>
                  <option value="Shinyanga" {{ old('city', $supplier->city) == 'Shinyanga' ? 'selected' : '' }}>Shinyanga</option>
                  <option value="Lindi" {{ old('city', $supplier->city) == 'Lindi' ? 'selected' : '' }}>Lindi</option>
                  <option value="Songe" {{ old('city', $supplier->city) == 'Songe' ? 'selected' : '' }}>Songe</option>
                  <option value="Moshi" {{ old('city', $supplier->city) == 'Moshi' ? 'selected' : '' }}>Moshi</option>
                  <option value="Tukuyu" {{ old('city', $supplier->city) == 'Tukuyu' ? 'selected' : '' }}>Tukuyu</option>
                  <option value="Bagamoyo" {{ old('city', $supplier->city) == 'Bagamoyo' ? 'selected' : '' }}>Bagamoyo</option>
                  <option value="Kibaha" {{ old('city', $supplier->city) == 'Kibaha' ? 'selected' : '' }}>Kibaha</option>
                  <option value="Korogwe" {{ old('city', $supplier->city) == 'Korogwe' ? 'selected' : '' }}>Korogwe</option>
                  <option value="Same" {{ old('city', $supplier->city) == 'Same' ? 'selected' : '' }}>Same</option>
                  <option value="Babati" {{ old('city', $supplier->city) == 'Babati' ? 'selected' : '' }}>Babati</option>
                  <option value="Other" {{ old('city', $supplier->city) == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('city')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Address</label>
                <textarea class="form-control @error('address') is-invalid @enderror" 
                          name="address" 
                          rows="3" 
                          placeholder="Enter full address">{{ old('address', $supplier->address) }}</textarea>
                @error('address')
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
                          placeholder="Any additional notes about this supplier">{{ old('notes', $supplier->notes) }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <div class="form-check">
                  <input class="form-check-input" 
                         type="checkbox" 
                         name="is_active" 
                         value="1" 
                         id="is_active" 
                         {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">
                    Active Supplier
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i>Update Supplier
            </button>
            <a class="btn btn-secondary" href="{{ route('bar.suppliers.index') }}">
              <i class="fa fa-fw fa-lg fa-times-circle"></i>Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
