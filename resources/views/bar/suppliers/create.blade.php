@extends('layouts.dashboard')

@section('title', ($type === 'food' ? 'Add Food Supplier' : ($type === 'beverage' ? 'Add Beverage Supplier' : 'Add Supplier')))

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-truck"></i> {{ $type === 'food' ? 'Add Food Supplier' : ($type === 'beverage' ? 'Add Beverage Supplier' : 'Add Supplier') }}</h1>
    <p>{{ $type === 'food' ? 'Register a new food ingredient supplier' : ($type === 'beverage' ? 'Register a new beverage supplier' : 'Register a new supplier') }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.suppliers.index') }}">Suppliers</a></li>
    <li class="breadcrumb-item">Add Supplier</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Supplier Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.suppliers.store') }}">
          @csrf

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Supplier Type *</label>
                @php
                  $selectedType = old('supplier_type', $type ?? 'general');
                @endphp
                <select class="form-control @error('supplier_type') is-invalid @enderror" name="supplier_type" required>
                  <option value="food" {{ $selectedType == 'food' ? 'selected' : '' }}>Food Supplier</option>
                  <option value="beverage" {{ $selectedType == 'beverage' ? 'selected' : '' }}>Beverage Supplier</option>
                  <option value="general" {{ $selectedType == 'general' ? 'selected' : '' }}>General Supplier</option>
                </select>
                @error('supplier_type')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Company Name *</label>
                <input class="form-control @error('company_name') is-invalid @enderror" 
                       type="text" 
                       name="company_name" 
                       value="{{ old('company_name') }}" 
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
                       value="{{ old('contact_person') }}" 
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
                         value="{{ old('phone', '') }}" 
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
                       value="{{ old('email') }}" 
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
                  <option value="Dar es Salaam" {{ old('city') == 'Dar es Salaam' ? 'selected' : '' }}>Dar es Salaam</option>
                  <option value="Arusha" {{ old('city') == 'Arusha' ? 'selected' : '' }}>Arusha</option>
                  <option value="Mwanza" {{ old('city') == 'Mwanza' ? 'selected' : '' }}>Mwanza</option>
                  <option value="Dodoma" {{ old('city') == 'Dodoma' ? 'selected' : '' }}>Dodoma</option>
                  <option value="Mbeya" {{ old('city') == 'Mbeya' ? 'selected' : '' }}>Mbeya</option>
                  <option value="Morogoro" {{ old('city') == 'Morogoro' ? 'selected' : '' }}>Morogoro</option>
                  <option value="Tanga" {{ old('city') == 'Tanga' ? 'selected' : '' }}>Tanga</option>
                  <option value="Zanzibar" {{ old('city') == 'Zanzibar' ? 'selected' : '' }}>Zanzibar</option>
                  <option value="Kigoma" {{ old('city') == 'Kigoma' ? 'selected' : '' }}>Kigoma</option>
                  <option value="Mtwara" {{ old('city') == 'Mtwara' ? 'selected' : '' }}>Mtwara</option>
                  <option value="Tabora" {{ old('city') == 'Tabora' ? 'selected' : '' }}>Tabora</option>
                  <option value="Iringa" {{ old('city') == 'Iringa' ? 'selected' : '' }}>Iringa</option>
                  <option value="Sumbawanga" {{ old('city') == 'Sumbawanga' ? 'selected' : '' }}>Sumbawanga</option>
                  <option value="Musoma" {{ old('city') == 'Musoma' ? 'selected' : '' }}>Musoma</option>
                  <option value="Bukoba" {{ old('city') == 'Bukoba' ? 'selected' : '' }}>Bukoba</option>
                  <option value="Singida" {{ old('city') == 'Singida' ? 'selected' : '' }}>Singida</option>
                  <option value="Shinyanga" {{ old('city') == 'Shinyanga' ? 'selected' : '' }}>Shinyanga</option>
                  <option value="Lindi" {{ old('city') == 'Lindi' ? 'selected' : '' }}>Lindi</option>
                  <option value="Songe" {{ old('city') == 'Songe' ? 'selected' : '' }}>Songe</option>
                  <option value="Moshi" {{ old('city') == 'Moshi' ? 'selected' : '' }}>Moshi</option>
                  <option value="Tukuyu" {{ old('city') == 'Tukuyu' ? 'selected' : '' }}>Tukuyu</option>
                  <option value="Bagamoyo" {{ old('city') == 'Bagamoyo' ? 'selected' : '' }}>Bagamoyo</option>
                  <option value="Kibaha" {{ old('city') == 'Kibaha' ? 'selected' : '' }}>Kibaha</option>
                  <option value="Korogwe" {{ old('city') == 'Korogwe' ? 'selected' : '' }}>Korogwe</option>
                  <option value="Same" {{ old('city') == 'Same' ? 'selected' : '' }}>Same</option>
                  <option value="Babati" {{ old('city') == 'Babati' ? 'selected' : '' }}>Babati</option>
                  <option value="Other" {{ old('city') == 'Other' ? 'selected' : '' }}>Other</option>
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
                          placeholder="Enter full address">{{ old('address') }}</textarea>
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
                          placeholder="Any additional notes about this supplier">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="tile-footer">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-fw fa-lg fa-check-circle"></i>Save Supplier
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
